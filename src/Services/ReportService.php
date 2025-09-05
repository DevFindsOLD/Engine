<?php

namespace Source\Services;

use Core\Database\DatabaseInterface;
use Source\Models\ProductReport;
use Source\Models\ServiceReport;

class ReportService
{
    public function __construct(private DatabaseInterface $db) {}

    public function generateProductReport(array $filters): array
    {
        $productId = $filters['product_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = "
        SELECT 
            check_items.name AS product_name,
            check_items.quantity AS quantity,
            check_items.price AS price,
            CASE 
                WHEN checks.card > 0 AND checks.cash <= 0 THEN check_items.total
                ELSE 0
            END AS card,
            CASE 
                WHEN checks.cash > 0 AND checks.card <= 0 THEN check_items.total
                ELSE 0
            END AS cash,
            check_items.total AS total,
            users.username AS employee_name,
            checks.date AS sale_date
        FROM checks
        JOIN check_items ON check_items.check_id = checks.id
        JOIN users ON checks.operator_name COLLATE utf8mb4_unicode_ci = users.username COLLATE utf8mb4_unicode_ci
        JOIN Product ON check_items.name COLLATE utf8mb4_unicode_ci = Product.name COLLATE utf8mb4_unicode_ci
        WHERE checks.report_type = 'product' AND checks.payment_status = 'completed'
    ";

        $params = [];
        if ($productId) {
            $query .= " AND Product.id = :product_id";
            $params['product_id'] = $productId;
        }
        if ($startDate && $endDate) {
            $query .= " AND checks.date BETWEEN :start_date AND :end_date";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $query .= " ORDER BY checks.date DESC";

        try {
            $reports = $this->db->query($query, $params);
            error_log('Product report query executed. Rows returned: ' . count($reports));
            if (!empty($reports)) {
                error_log('Sample product data: ' . json_encode($reports[0]));
            }
        } catch (\Exception $e) {
            error_log('SQL Error: ' . $e->getMessage());
            return [];
        }

        $reports = array_map(function ($report) {
            return new ProductReport(
                $report['product_name'],
                $report['quantity'],
                $report['price'],
                $report['card'],
                $report['cash'],
                $report['total'],
                $report['employee_name'],
                $report['sale_date']
            );
        }, $reports);

        return $reports;
    }

    public function generateServiceReport(array $filters): array
    {
        $serviceId = $filters['service_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = "
            SELECT DISTINCT
                Service.name AS name,
                checks.car_brand AS car_brand,
                checks.car_number AS car_number,
                Car_Classes.name AS car_class, -- Добавляем имя класса
                checks.date AS sale_date,
                checks.card AS card,
                checks.cash AS cash,
                CASE 
                    WHEN checks.cash > 0 AND checks.card <= 0 THEN 'cash'
                    WHEN checks.card > 0 AND checks.cash <= 0 THEN 'card'
                    WHEN checks.cash > 0 AND checks.card > 0 THEN 'cash_card'
                    ELSE 'unknown'
                END AS payment_method,
                (check_items.total + COALESCE(checks.markup, 0.00)) AS total,
                COALESCE(checks.markup, 0.00) AS markup
            FROM checks
            JOIN check_items ON check_items.check_id = checks.id
            JOIN Service ON check_items.name COLLATE utf8mb4_unicode_ci = Service.name COLLATE utf8mb4_unicode_ci
            LEFT JOIN Car ON checks.car_number = Car.state_number -- Присоединяем таблицу Car
            LEFT JOIN Car_Classes ON Car.class_id = Car_Classes.id -- Присоединяем таблицу Car_Classes
            LEFT JOIN Service_Sale ON Service_Sale.service_id = Service.id 
                AND Service_Sale.car_id = Car.id 
                AND DATE(Service_Sale.sale_date) = DATE(checks.date)
            WHERE checks.report_type = 'service' AND checks.payment_status = 'completed'
        ";

        $params = [];
        if ($serviceId) {
            $query .= " AND Service.id = :service_id";
            $params['service_id'] = $serviceId;
        }
        if ($startDate && $endDate) {
            $query .= " AND checks.date BETWEEN :start_date AND :end_date";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }

        $query .= " ORDER BY checks.date DESC";

        try {
            $reports = $this->db->query($query, $params);
            error_log('Service report query executed. Rows returned: ' . count($reports));
            if (!empty($reports)) {
                error_log('Sample service data: ' . json_encode($reports[0]));
            }
            if (empty($reports)) {
                error_log('No data returned for service report. Query: ' . $query . ' Params: ' . json_encode($params));
            }
        } catch (\Exception $e) {
            error_log('SQL Error: ' . $e->getMessage());
            return [];
        }
        
        $reports = array_map(function ($report) {
            return new ServiceReport(
                $report['name'],
                $report['car_brand'] ?? '',
                $report['car_number'] ?? '',
                $report['car_class'] ?? 'Не указан', // Передаем класс или значение по умолчанию
                $report['sale_date'],
                $report['payment_method'],
                $report['total'] ?? 0.0,
                $report['card'] ?? 0.0,
                $report['cash'] ?? 0.0,
                $report['markup'] ?? 0.0
            );
        }, $reports);
        return $reports;
    }

    public function calculateProductReportTotals(array $reports): array
    {
        $totalCash = 0;
        $totalCard = 0;
        $totalAmount = 0;

        foreach ($reports as $report) {
            $cash = $report->cash();
            $card = $report->card();
            $total = $report->total();
            
            $totalCash += $cash;
            $totalCard += $card;
            $totalAmount += $total;
            
            // Отладочная информация
            error_log("Product Report: Cash={$cash}, Card={$card}, Total={$total}, Product={$report->productName()}");
        }

        error_log("Product Totals: Cash={$totalCash}, Card={$totalCard}, Amount={$totalAmount}");

        return [
            'total_cash' => $totalCash,
            'total_card' => $totalCard,
            'total_amount' => $totalAmount
        ];
    }

    public function calculateServiceReportTotals(array $reports): array
    {
        $totalCash = 0;
        $totalCard = 0;
        $totalAmount = 0;

        foreach ($reports as $report) {
            $cash = $report->cash();
            $card = $report->card();
            $total = $report->total();
            
            $totalCash += $cash;
            $totalCard += $card;
            $totalAmount += $total;
            
            // Отладочная информация
            error_log("Service Report: Cash={$cash}, Card={$card}, Total={$total}, Service={$report->serviceName()}");
        }

        error_log("Service Totals: Cash={$totalCash}, Card={$totalCard}, Amount={$totalAmount}");

        return [
            'total_cash' => $totalCash,
            'total_card' => $totalCard,
            'total_amount' => $totalAmount
        ];
    }
}