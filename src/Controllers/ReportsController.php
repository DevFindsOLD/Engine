<?php

namespace Source\Controllers;

use Core\Controller\Controller;
use Source\Events\LogActionEvent;
use Source\Listeners\LogActionListener;
use Source\Services\ReportService;
use Source\Services\EmployeeService;
use Source\Services\ProductService;
use Source\Services\ServiceService;
use Source\Services\ExcelExporter;
use Source\Services\LogService;

class ReportsController extends Controller
{
    public function index()
    {
        $employeeService = new EmployeeService($this->getDatabase());
        $productService = new ProductService($this->getDatabase());
        $serviceService = new ServiceService($this->getDatabase());

        $employees = $employeeService->getAllFromDB();
        $products = $productService->getAllFromDBAllSuppliers();
        $services = $serviceService->getAllFromDBAsArray();

        $this->render('/admin/dashboard/reports', [
            'employees' => $employees,
            'products' => $products,
            'services' => $services,
            'productReports' => [],
            'reports' => [],
            'reportType' => 'product',
            'selectedId' => '',
            'selectedStartDate' => '',
            'selectedEndDate' => '',
            'totals' => null,
        ]);
    }

    public function getReport()
    {
        $reportType = $_POST['report_type'] ?? 'product';
        $selectedId = $reportType === 'service' ? ($_POST['service_id'] ?? '') : ($_POST['product_id'] ?? '');
        $selectedStartDate = $_POST['start_date'] ?? '';
        $selectedEndDate = $_POST['end_date'] ?? '';

        $filters = [
            'product_id' => $reportType === 'product' ? $selectedId : null,
            'service_id' => $reportType === 'service' ? $selectedId : null,
            'start_date' => $selectedStartDate,
            'end_date' => $selectedEndDate,
        ];

        $this->getEventManager()->addListener('log.action', new LogActionListener());

        $reportService = new ReportService($this->getDatabase());
        $reports = $reportType === 'service'
            ? $reportService->generateServiceReport($filters)
            : $reportService->generateProductReport($filters);

        // Подсчет итоговых сумм
        $totals = $reportType === 'service'
            ? $reportService->calculateServiceReportTotals($reports)
            : $reportService->calculateProductReportTotals($reports);

        // Отладочная информация
        error_log("Controller totals for {$reportType}: " . json_encode($totals));

        $formattedReports = array_map(function ($r) use ($reportType) {
            if ($reportType === 'service') {
                return [
                    'name' => htmlspecialchars($r->serviceName()),
                    'car_class' => htmlspecialchars($r->carClass() ?? 'Не указан'), // Добавляем класс автомобиля
                    'car_brand' => htmlspecialchars($r->carBrand()),
                    'car_number' => htmlspecialchars($r->carNumber()),
                    'sale_date' => htmlspecialchars($r->saleDate()),
                    'payment_method' => htmlspecialchars($r->paymentMethod()),
                    'cash' => number_format($r->cash(), 2),
                    'card' => number_format($r->card(), 2),
                    'markup' => number_format($r->markup(), 2),
                    'total' => number_format($r->total(), 2),
                ];
            }
            return [
                'product_name' => htmlspecialchars($r->productName()),
                'quantity' => $r->quantity(),
                'price' => number_format($r->price(), 2),
                'cash' => number_format($r->cash(), 2),
                'card' => number_format($r->card(), 2),
                'total' => number_format($r->total(), 2),
                'employee_name' => htmlspecialchars($r->employeeName()),
                'sale_date' => htmlspecialchars($r->saleDate()),
            ];
        }, $reports);

        $employeeService = new EmployeeService($this->getDatabase());
        $productService = new ProductService($this->getDatabase());
        $serviceService = new ServiceService($this->getDatabase());

        $employees = $employeeService->getAllFromDB();
        $products = $productService->getAllFromDBAllSuppliers();
        $services = $serviceService->getAllFromDBAsArray();

        $payload = [
            'action_name' => 'Создан отчет',
            'actor_id' => $this->getAuth()->getUser()->id(),
            'action_info' => [
                'Отчет' => $reportType,
                'Дата создания' => date('Y-m-d H:i:s'),
                'Фильтры' => json_encode($filters),
                'Количество записей' => count($formattedReports),
                'Тип пользователя' => $this->getAuth()->getRole()->name(),
                'ID пользователя' => $this->getAuth()->getUser()->id(),
                'Пользователь' => $this->getAuth()->getRole()->name() . " " . $this->getAuth()->getUser()->username() . " " . $this->getAuth()->getUser()->lastname()
            ]
        ];
        $event = new LogActionEvent($payload);
        $this->getEventManager()->dispatch($event);

        $this->render('/admin/dashboard/reports', [
            'employees' => $employees,
            'products' => $products,
            'services' => $services,
            'productReports' => $reportType === 'product' ? $formattedReports : [],
            'reports' => $formattedReports,
            'reportType' => $reportType,
            'selectedId' => $selectedId,
            'selectedStartDate' => $selectedStartDate,
            'selectedEndDate' => $selectedEndDate,
            'totals' => $totals,
        ]);
    }

    public function getLastActions()
    {
        $logService = new LogService($this->getDatabase());
        return  $logService->getLastActions();
    }

    public function exportReport()
    {
        try {
            $reportType = $_POST['report_type'] ?? 'product';
            $filters = [
                'product_id' => $reportType === 'product' ? ($_POST['product_id'] ?? null) : null,
                'service_id' => $reportType === 'service' ? ($_POST['service_id'] ?? null) : null,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
            ];

            $reportService = new ReportService($this->getDatabase());
            $reports = $reportType === 'service'
                ? $reportService->generateServiceReport($filters)
                : $reportService->generateProductReport($filters);

            // Подсчет итоговых сумм
            $totals = $reportType === 'service'
                ? $reportService->calculateServiceReportTotals($reports)
                : $reportService->calculateProductReportTotals($reports);

            // Отладочная информация
            error_log("Controller totals for {$reportType}: " . json_encode($totals));

            $rows = array_map(function ($r) use ($reportType) {
                if ($reportType === 'service') {
                    return [
                        'name' => $r->serviceName(),
                        'car_class' => $r->carClass() ?? 'Не указан', // Добавляем класс автомобиля
                        'car_brand' => $r->carBrand(),
                        'car_number' => $r->carNumber(),
                        'sale_date' => $r->saleDate(),
                        'payment_method' => $r->paymentMethod(),
                        'cash' => $r->cash(),
                        'card' => $r->card(),
                        'markup' => $r->markup(),
                        'total' => $r->total(),
                    ];
                }
                return [
                    'product_name' => $r->productName(),
                    'quantity' => $r->quantity(),
                    'price' => $r->price(),
                    'cash' => $r->cash(),
                    'card' => $r->card(),
                    'total' => $r->total(),
                    'employee_name' => $r->employeeName(),
                    'sale_date' => $r->saleDate(),
                ];
            }, $reports);

            // Добавляем итоговые строки
            $rows[] = []; // Пустая строка
            if ($reportType === 'service') {
                $rows[] = [
                    'name' => 'ИТОГО:',
                    'car_class' => '',
                    'car_brand' => '',
                    'car_number' => '',
                    'sale_date' => '',
                    'payment_method' => '',
                    'cash' => $totals['total_cash'],
                    'card' => $totals['total_card'],
                    'total' => $totals['total_amount'],
                ];
            } else {
                $rows[] = [
                    'product_name' => 'ИТОГО:',
                    'quantity' => '',
                    'price' => '',
                    'cash' => $totals['total_cash'],
                    'card' => $totals['total_card'],
                    'total' => $totals['total_amount'],
                    'employee_name' => '',
                    'sale_date' => '',
                ];
            }

            $this->getEventManager()->addListener('log.action', new LogActionListener());

            $columns = $reportType === 'service' ? [
                ['header' => 'Наименование услуги', 'key' => 'name', 'format' => 'string'], // Заменяем на "Наименование услуги"
                ['header' => 'Класс автомобиля', 'key' => 'car_class', 'format' => 'string'], // Добавляем новый столбец
                ['header' => 'Марка машины', 'key' => 'car_brand', 'format' => 'string'],
                ['header' => 'Номер', 'key' => 'car_number', 'format' => 'string'],
                ['header' => 'Дата', 'key' => 'sale_date', 'format' => 'string'],
                ['header' => 'Тип оплаты', 'key' => 'payment_method', 'format' => 'string'],
                ['header' => 'Сумма нал.', 'key' => 'cash', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сумма безнал.', 'key' => 'card', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Наценка', 'key' => 'markup', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сумма', 'key' => 'total', 'format' => 'number', 'number_format' => '#,##0.00'],
            ] : [
                ['header' => 'Товар', 'key' => 'product_name', 'format' => 'string'],
                ['header' => 'Кол-во', 'key' => 'quantity', 'format' => 'number', 'number_format' => '0'],
                ['header' => 'Цена', 'key' => 'price', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сумма нал.', 'key' => 'cash', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сумма безнал.', 'key' => 'card', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сумма', 'key' => 'total', 'format' => 'number', 'number_format' => '#,##0.00'],
                ['header' => 'Сотрудник', 'key' => 'employee_name', 'format' => 'string'],
                ['header' => 'Дата', 'key' => 'sale_date', 'format' => 'string'],
            ];

            $exporter = new ExcelExporter(
                $reportType . '_report_' . date('Y-m-d_H-i-s'),
                $columns,
                $rows
            );
            $payload = [
                'action_name' => 'Экспорт отчета',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Отчет' => $reportType,
                    'Фильтры' => json_encode($filters),
                    'Дата экспорта' => date('Y-m-d H:i:s'),
                    'IP' => $_SERVER['REMOTE_ADDR'],
                    'Браузер' => $_SERVER['HTTP_USER_AGENT'],
                    'Операционная система' => php_uname(),
                    'Тип экспорта' => 'Excel',
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " . $this->getAuth()->getUser()->username() . " " . $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);
            $exporter->export();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->redirect('404');
        }
    }
}
