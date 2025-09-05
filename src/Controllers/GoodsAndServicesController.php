<?php
namespace Source\Controllers;

// Отключаем вывод ошибок для AJAX методов
if (isset($_POST['operation_type'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

use Core\Controller\Controller;
use Exception;
use FPDF;
use Source\Events\LogActionEvent;
use Source\Listeners\LogActionListener;
use Source\Services\CompanyService;
use Source\Services\ProductService;
use Source\Services\ServiceService;
use Source\Services\WarehouseService;
use Source\Services\CompanyTypeService;
use Source\Services\SupplierService;
use Source\Services\CarClassesService;
use Source\Services\ClientService;

class GoodsAndServicesController extends Controller
{

    public function index()
    {
        // Если это POST запрос, обрабатываем его
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleServiceSalesPost();
            return;
        }

        $company_service = new CompanyService($this->getDatabase());
        $company_type_service = new CompanyTypeService($this->getDatabase());
        $suppliers_service = new SupplierService($this->getDatabase());
        $warehouse_service = new WarehouseService($this->getDatabase());
        $product_service = new ProductService($this->getDatabase());
        $service_service = new ServiceService($this->getDatabase());
        $car_classes_service = new CarClassesService($this->getDatabase()); // Добавлен сервис

        $field_error_event = $this->FieldErrorEventDispath('error', 'error', 'error');
        $error_array = $field_error_event->getPayload();

        $this->render('/admin/dashboard/goods_and_services', [
            'company_service' => $company_service,
            'company_type_service' => $company_type_service,
            'warehouse_service' => $warehouse_service,
            'product_service' => $product_service,
            'service_service' => $service_service,
            'suppliers_service' => $suppliers_service,
            'car_classes_service' => $car_classes_service // Добавлено в массив данных
        ]);
    }

    /**
     * Обработка POST запросов для страницы service_sales
     */
    private function handleServiceSalesPost()
    {
        // Определяем тип операции по скрытому полю или другим признакам
        $operationType = $this->request()->input('operation_type') ?? '';
        
        if ($operationType === 'service') {
            $this->addNewServiceSale();
        } elseif ($operationType === 'product') {
            $this->addNewProductSale();
        } else {
            // Если тип операции не указан, пытаемся определить по содержимому формы
            if ($this->request()->input('services')) {
                $this->addNewServiceSale();
            } elseif ($this->request()->input('products')) {
                $this->addNewProductSale();
            } else {
                $this->session()->set('error', 'Не удалось определить тип операции.');
                $this->redirect('/admin/dashboard/service_sales');
            }
        }
    }

    public function addNewGood()
    {
        $this->getEventManager()->addListener('log.action', new LogActionListener());
        $labels = [
            'name' => 'Наименование',
            'amount' => 'Количество',
            'created_at' => 'Дата создания',
            'unit_measurement' => 'Ед. изм.',
            'purchase_price' => 'Цена закупки',
            'sale_price' => 'Цена продажи',
            'supplier_id' => 'Поставщик',
            'warehouse_id' => 'Склад',
        ];

        $validation = $this->request()->validate([
            'name' => ['required'],
            'amount' => ['required'],
            'created_at' => ['required'],
            'unit_measurement' => ['required'],
            'purchase_price' => ['required'],
            'sale_price' => ['required'],
            'supplier_id' => ['required'],
            'warehouse_id' => ['required']
        ], $labels);

        if (!$validation) {
            foreach ($this->request()->errors() as $field => $errors) {
                $this->session()->set($field, $errors);
            }
            $this->redirect('/admin/dashboard/goods_and_services');
            return;
        }

        $existingProduct = $this->getDatabase()->first_found_in_db('Product', [
            'name' => $this->request()->input('name'),
            'warehouse_id' => $this->request()->input('warehouse_id')
        ]);

        if ($existingProduct) {
            $this->getDatabase()->update('Product', [
                'amount' => $existingProduct['amount'] + $this->request()->input('amount')
            ], [
                'id' => $existingProduct['id']
            ]);
        } else {
            $this->getDatabase()->insert('Product', [
                'name' => $this->request()->input('name'),
                'amount' => $this->request()->input('amount'),
                'created_at' => $this->request()->input('created_at'),
                'unit_measurement' => $this->request()->input('unit_measurement'),
                'purchase_price' => $this->request()->input('purchase_price'),
                'sale_price' => $this->request()->input('sale_price'),
                'supplier_id' => $this->request()->input('supplier_id'),
                'warehouse_id' => $this->request()->input('warehouse_id'),
                'description' => $this->request()->input('description')
            ]);

            $payload = [
                'action_name' => 'Добавление товара',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Товар' => $this->request()->input('name'),
                    'Количество' => $this->request()->input('amount'),
                    'Склад' => $this->request()->input('warehouse_id'),
                    'Поставщик' => $this->request()->input('supplier_id'),
                    'Ед. изм.' => $this->request()->input('unit_measurement'),
                    'Дата создания' => $this->request()->input('created_at'),
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " . $this->getAuth()->getUser()->username() . " " . $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);
        }

        $this->redirect('/admin/dashboard/goods_and_services');
    }


    public function addNewService()
    {
        $this->getEventManager()->addListener('log.action', new LogActionListener());
        $labels = [
            'name' => 'Наименование',
            'price' => 'Цена',
            'category' => 'Категория'
        ];

        $validation = $this->request()->validate([
            'name' => ['required'],
            'price' => ['required'],
            'category' => ['required']
        ], $labels);

        if (!$validation) {
            foreach ($this->request()->errors() as $field => $errors) {
                $this->session()->set($field, $errors);
            }
            $this->redirect('/admin/dashboard/goods_and_services');
            return;
        }

        $name = $this->request()->input('name');
        $category = $this->request()->input('category');

        // Проверка на уникальность наименования в рамках категории
        $existingService = $this->getDatabase()->first_found_in_db('Service', [
            'name' => $name,
            'category' => $category
        ]);

        if ($existingService) {
            $this->session()->set('error', 'Услуга с таким наименованием уже существует в данном классе.');
            $this->redirect('/admin/dashboard/goods_and_services');
            return;
        }

        $this->getDatabase()->insert('Service', [
            'name' => $name,
            'description' => $this->request()->input('description'),
            'price' => $this->request()->input('price'),
            'category' => $category
        ]);

        $payload = [
            'action_name' => 'Добавление услуги',
            'actor_id' => $this->getAuth()->getUser()->id(),
            'action_info' => [
                'Услуга' => $name,
                'Цена' => $this->request()->input('price'),
                'Категория' => $category,
                'Описание' => $this->request()->input('description'),
                'Пользователь' => $this->getAuth()->getRole()->name() . " " . $this->getAuth()->getUser()->username() . " " . $this->getAuth()->getUser()->lastname()
            ]
        ];
        $event = new LogActionEvent($payload);
        $this->getEventManager()->dispatch($event);
        $this->redirect('/admin/dashboard/goods_and_services');
    }


    public function addNewProductSale()
    {
        $this->getEventManager()->addListener('log.action', new LogActionListener());
        $validation = $this->request()->validate([
            'payment_type' => ['required']
        ], [
            'payment_type' => 'Тип оплаты'
        ]);

        if (!$validation) {
            $this->session()->set('error', 'Не выбран тип оплаты.');
            $this->redirect('/admin/dashboard/service_sales');
            return;
        }

        $lines = $this->request()->input('products');
        if (!is_array($lines) || empty($lines)) {
            $this->session()->set('error', 'Не выбраны товары.');
            $this->redirect('/admin/dashboard/service_sales');
            return;
        }

        $paymentType = $this->request()->input('payment_type');
        $operatorName = $this->getAuth()->getUser()->username(); // Получаем имя текущего оператора
        $grandTotal = 0;
        $receivedAmount = (float)($this->request()->input('received_amount') ?? 0);

        // Инициализация чека
        $checkNumber = date('YmdHis');
        $cash = 0;
        $card = 0;
        $items = [];

        foreach ($lines as $line) {
            $productWarehouseVal = $line['product_warehouse'] ?? null;
            $amount = (int)($line['amount'] ?? 0);

            if (!$productWarehouseVal || $amount < 1) {
                continue;
            }

            list($productId, $warehouseId) = explode('_', $productWarehouseVal);

            $productRow = $this->getDatabase()->first_found_in_db('Product', [
                'id' => $productId,
                'warehouse_id' => $warehouseId
            ]);

            if (!$productRow) {
                continue;
            }

            $price = (float)$productRow['sale_price'];
            $lineTotal = $price * $amount;
            $grandTotal += $lineTotal;

            // Уменьшаем остаток товара
            $newAmount = max(0, $productRow['amount'] - $amount);
            $this->getDatabase()->update('Product', ['amount' => $newAmount], [
                'id' => $productId,
                'warehouse_id' => $warehouseId
            ]);

            $items[] = [
                'name' => $productRow['name'],
                'quantity' => $amount,
                'price' => $price,
                'total' => $lineTotal
            ];
        }

        if ($paymentType === 'cash') {
            $cash = $grandTotal;
        } elseif ($paymentType === 'card') {
            $card = $grandTotal;
        } elseif ($paymentType === 'cash_card') {
            $cash = (float)($this->request()->input('cash_amount') ?? 0);
            $card = (float)($this->request()->input('card_amount') ?? 0);
        }
        
        // Рассчитываем сдачу
        $changeAmount = max(0, $receivedAmount - $grandTotal);

        try {
            $checkId = $this->createCheck([
                'check_number' => $checkNumber,
                'date' => date('Y-m-d H:i:s'),
                'total' => $grandTotal,
                'cash' => $cash,
                'card' => $card,
                'discount' => 0,
                'operator_name' => $operatorName,
                'car_number' => null,
                'change_amount' => $changeAmount,
                'report_type' => 'product',
                'car_model' => null,
                'car_brand' => null,
                'payment_status' => 'pending'
            ]);

            $this->addCheckItems($checkId, $items);

            // Печать чека
            $payload = [
                'action_name' => 'Продажа товара',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Ссылка на чек' => '/admin/dashboard/check/preview/' . $checkId,
                    'Кассир' => $this->getAuth()->getRole()->name() . " " . $this->getAuth()->getUser()->username() . " " . $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);
            $this->redirect('/admin/dashboard/check/preview/' . $checkId);
        } catch (Exception $e) {
            $this->session()->set('error', 'Ошибка при создании чека: ' . $e->getMessage());
            $this->redirect('/admin/dashboard/service_sales');
        }
    }

    public function addNewServiceSale()
    {
        $this->getEventManager()->addListener('log.action', new LogActionListener());
        $validation = $this->request()->validate([
            'employee_id' => ['required'],
            'state_number' => ['required'],
            'payment_type' => ['required']
        ], [
            'employee_id' => 'Сотрудник',
            'state_number' => 'Гос. номер',
            'payment_type' => 'Тип оплаты'
        ]);

        if (!$validation) {
            $this->session()->set('error', 'Не заполнены обязательные поля.');
            $this->redirect('/admin/dashboard/service_sales');
            return;
        }

        $serviceLines = $this->request()->input('services');
        if (!is_array($serviceLines) || empty($serviceLines)) {
            $this->session()->set('error', 'Не выбрано ни одной услуги.');
            $this->redirect('/admin/dashboard/service_sales');
            return;
        }

        // Обработка данных клиента
        $clientLastName = $this->request()->input('client_last_name');
        $clientFirstName = $this->request()->input('client_first_name');
        $clientPatronymic = $this->request()->input('client_patronymic');
        $clientPhone = $this->request()->input('client_phone');
        
        // Очищаем телефон от маски
        $clientPhone = preg_replace('/[^0-9]/', '', $clientPhone);
        if (strlen($clientPhone) === 11 && substr($clientPhone, 0, 1) === '7') {
            $clientPhone = substr($clientPhone, 1);
        }
        $clientPhone = '+7' . $clientPhone;

        // Получаем данные из формы
        $stateNumber = $this->request()->input('state_number');
        $newClassId = $this->request()->input('class_id');

        // Ищем существующего клиента по номеру машины
        $existingClient = null;
        $sql = "SELECT DISTINCT c.id, c.last_name, c.name AS first_name, c.patronymic, c.phone
                FROM Client c
                JOIN Client_cars cc ON c.id = cc.client_id
                JOIN Car car ON cc.car_id = car.id
                WHERE car.state_number = ?";
        $clients = $this->getDatabase()->query($sql, [$stateNumber]);
        
        if ($clients && count($clients) > 0) {
            $existingClient = $clients[0];
        }

        // Создаем или обновляем клиента
        $clientService = new ClientService($this->getDatabase());
        $clientId = null;
        
        if ($existingClient) {
            // Обновляем существующего клиента, если ФИО изменились
            $needUpdate = false;
            $updateData = [];
            
            if ($existingClient['last_name'] !== $clientLastName) {
                $updateData['last_name'] = $clientLastName;
                $needUpdate = true;
            }
            if ($existingClient['first_name'] !== $clientFirstName) {
                $updateData['name'] = $clientFirstName;
                $needUpdate = true;
            }
            if ($existingClient['patronymic'] !== $clientPatronymic) {
                $updateData['patronymic'] = $clientPatronymic;
                $needUpdate = true;
            }
            
            if ($needUpdate) {
                $this->getDatabase()->update('Client', $updateData, ['id' => $existingClient['id']]);
            }
            $clientId = $existingClient['id'];
        } else {
            // Создаем нового клиента
            $clientId = $this->getDatabase()->insert('Client', [
                'last_name' => $clientLastName,
                'name' => $clientFirstName,
                'patronymic' => $clientPatronymic,
                'phone' => $clientPhone
            ]);
        }

        // Поиск или создание машины
        $car = $this->getDatabase()->first_found_in_db('Car', ['state_number' => $stateNumber]);

        if (!$car) {
            $carId = $this->getDatabase()->insert('Car', [
                'state_number' => $stateNumber,
                'car_brand' => $this->request()->input('car_brand'),
                'car_model' => null,
                'class_id' => $newClassId
            ]);
            $car = $this->getDatabase()->first_found_in_db('Car', ['id' => $carId]);
            
            // Связываем клиента с машиной через таблицу Client_cars
            if ($clientId) {
                $this->getDatabase()->insert('Client_cars', [
                    'client_id' => $clientId,
                    'car_id' => $carId
                ]);
            }
        } else {
            $carId = $car['id'];
            // Проверяем и обновляем класс, если он изменился
            if ($car['class_id'] != $newClassId) {
                $this->getDatabase()->update('Car', ['class_id' => $newClassId], ['id' => $carId]);
                $car['class_id'] = $newClassId; // Обновляем данные в $car для последующих расчетов
            }
            
            // Проверяем, связан ли клиент с этой машиной
            if ($clientId) {
                $existingLink = $this->getDatabase()->first_found_in_db('Client_cars', [
                    'client_id' => $clientId,
                    'car_id' => $carId
                ]);
                
                if (!$existingLink) {
                    // Связываем клиента с машиной
                    $this->getDatabase()->insert('Client_cars', [
                        'client_id' => $clientId,
                        'car_id' => $carId
                    ]);
                }
            }
        }

        $employeeId = $this->request()->input('employee_id');
        $paymentType = $this->request()->input('payment_type');
        $operatorName = $this->getAuth()->getUser()->username();
        $grandTotal = 0;
        $items = [];
        $markup = (float)($this->request()->input('markup') ?? 0);
        $receivedAmount = (float)($this->request()->input('received_amount') ?? 0);

        foreach ($serviceLines as $line) {
            $servId = $line['service_id'] ?? null;
            if (!$servId) continue;

            $serviceRow = $this->getDatabase()->first_found_in_db('Service', ['id' => $servId]);
            if (!$serviceRow) continue;

            $price = (float)$serviceRow['price'];
            $grandTotal += $price;

            $items[] = [
                'name' => $serviceRow['name'],
                'quantity' => 1,
                'price' => $price,
                'total' => $price
            ];
        }

        // Добавляем наценку к общей сумме
        $grandTotal += $markup;
        
        // Рассчитываем сдачу с учетом финальной суммы
        $changeAmount = max(0, $receivedAmount - $grandTotal);

        // Определяем суммы оплаты ДО вставки Service_Sale
        $cash = 0;
        $card = 0;
        if ($paymentType === 'cash') {
            $cash = $grandTotal;
        } elseif ($paymentType === 'card') {
            $card = $grandTotal;
        } elseif ($paymentType === 'cash_card') {
            $cash = (float)($this->request()->input('cash_amount') ?? 0);
            $card = (float)($this->request()->input('card_amount') ?? 0);
        }

        // Вставляем Service_Sale для каждой услуги
        foreach ($serviceLines as $line) {
            $servId = $line['service_id'] ?? null;
            if (!$servId) continue;
            $serviceRow = $this->getDatabase()->first_found_in_db('Service', ['id' => $servId]);
            if (!$serviceRow) continue;
            $price = (float)$serviceRow['price'];
            $this->getDatabase()->insert('Service_Sale', [
                'service_id' => $servId,
                'employee_id' => $employeeId,
                'car_id' => $carId,
                'total_amount' => $price,
                'payment_method' => $paymentType,
                'markup' => $markup,
                'sale_date' => date('Y-m-d H:i:s')
            ]);
        }

        try {
            $checkId = $this->createCheck([
                'check_number' => 'CHK' . time(),
                'date' => date('Y-m-d H:i:s'),
                'total' => $grandTotal,
                'cash' => $cash,
                'card' => $card,
                'discount' => 0,
                'operator_name' => $operatorName,
                'car_number' => $car['state_number'],
                'change_amount' => $changeAmount,
                'report_type' => 'service',
                'car_model' => $car['car_model'],
                'car_brand' => $car['car_brand'],
                'markup' => $markup,
                'payment_status' => 'pending'
            ]);

            $this->addCheckItems($checkId, $items);

            $payload = [
                'action_name' => 'Продажа услуги',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Ссылка на чек' => '/admin/dashboard/check/preview/' . $checkId,
                    'Кассир' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);
            $this->redirect('/admin/dashboard/check/preview/' . $checkId);
        } catch (Exception $e) {
            $this->session()->set('error', 'Ошибка при создании чека: ' . $e->getMessage());
            $this->redirect('/admin/dashboard/service_sales');
        }
    }

    public function previewCheck($checkId)
    {
        try {
            // Получаем данные чека из таблицы checks
            $check = $this->getDatabase()->first_found_in_db('checks', ['id' => $checkId]);
            if (!$check) {
                $this->session()->set('error', 'Чек не найден.');
                dd($this->session()->get('error'));
                return;
            }

            // Получаем данные позиций из таблицы check_items
            $checkItems = $this->getDatabase()->get('check_items', ['check_id' => $checkId]);

            // Для услуг получаем данные о наценке
            $markupData = [];
            if ($check['report_type'] === 'service') {
                // Получаем данные о наценке из Service_Sale
                $serviceSales = $this->getDatabase()->query("
                    SELECT Service.name, Service_Sale.markup
                    FROM Service_Sale 
                    JOIN Service ON Service_Sale.service_id = Service.id
                    JOIN Car ON Service_Sale.car_id = Car.id
                    WHERE Car.state_number = :car_number 
                    AND DATE(Service_Sale.sale_date) = DATE(:sale_date)
                ", [
                    'car_number' => $check['car_number'],
                    'sale_date' => $check['date']
                ]);
                
                foreach ($serviceSales as $sale) {
                    $markupData[$sale['name']] = $sale['markup'];
                }
            }

            // Передаем данные чека и позиций в вид
            $this->render('/admin/preview_check', [
                'check' => $check,
                'items' => $checkItems,
                'markupData' => $markupData,
                'auth' => $this->getAuth()
            ]);
        } catch (Exception $e) {
            $this->session()->set('error', 'Ошибка при загрузке чека: ' . $e->getMessage());
            dd($this->session()->get('error'));
        }
    }


    private function createCheck(array $data): int
    {
        try {
            return $this->getDatabase()->insert('checks', $data);
        } catch (Exception $e) {
            $this->session()->set('error', 'Ошибка создания чека: ' . $e->getMessage());
            throw $e;
        }
    }

    private function addCheckItems(int $checkId, array $items): void
    {
        foreach ($items as $item) {
            try {
                $this->getDatabase()->insert('check_items', [
                    'check_id' => $checkId,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total']
                ]);
            } catch (Exception $e) {
                $this->session()->set('error', 'Ошибка добавления позиции в чек: ' . $e->getMessage());
                throw $e;
            }
        }
    }



    public function editProduct()
    {
        $id = $this->request()->input('id');
        $data = [
            'name' => $this->request()->input('name'),
            'amount' => $this->request()->input('amount'),
            'created_at' => $this->request()->input('created_at'),
            'unit_measurement' => $this->request()->input('unit_measurement'),
            'purchase_price' => $this->request()->input('purchase_price'),
            'sale_price' => $this->request()->input('sale_price'),
            'supplier_id' => $this->request()->input('supplier_id'),
            'warehouse_id' => $this->request()->input('warehouse_id'),
            'description' => $this->request()->input('description')
        ];

        $this->getDatabase()->update('Product', $data, ['id' => $id]);
        $this->redirect('/admin/dashboard/goods_and_services');
    }

    public function deleteProduct()
    {
        ob_start(); // Начать буферизацию вывода
        header('Content-Type: application/json'); // Установить заголовок JSON
        try {
            // Получить и залогировать сырое тело запроса
            $rawInput = file_get_contents('php://input');
            error_log('deleteProduct raw input: ' . $rawInput);

            // Декодировать JSON
            $input = json_decode($rawInput, true);
            $id = $input['id'] ?? null;

            if (!$id) {
                error_log('deleteProduct: ID not provided');
                echo json_encode(['status' => 'error', 'message' => 'ID товара не указан']);
                ob_end_flush();
                return;
            }

            error_log('deleteProduct: Processing ID ' . $id);

            // Проверка существования товара
            $product = $this->getDatabase()->first_found_in_db('Product', ['id' => $id]);
            if (!$product) {
                error_log('deleteProduct: Product ID ' . $id . ' not found');
                echo json_encode(['status' => 'error', 'message' => 'Товар не найден']);
                ob_end_flush();
                return;
            }

            // Удаление связанных записей в check_items (если связь через name)
            // Замените 'name' на актуальный столбец, если связь другая
            $this->getDatabase()->delete('check_items', ['name' => $product['name']]);
            error_log('deleteProduct: Deleted related check_items for product name ' . $product['name']);

            // Удаление товара
            $result = $this->getDatabase()->delete('Product', ['id' => $id]);
            if ($result === false) {
                throw new Exception('Не удалось удалить товар');
            }
            error_log('deleteProduct: Successfully deleted product ID ' . $id);

            // Логирование действия
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Удаление товара',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Товар' => $product['name'],
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log('deleteProduct error for ID ' . ($id ?? 'unknown') . ': ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при удалении товара: ' . $e->getMessage()]);
        }
        ob_end_flush();
    }

    public function editService()
    {
        $this->getEventManager()->addListener('log.action', new LogActionListener());
        $id = $this->request()->input('id');
        $labels = [
            'name' => 'Наименование',
            'price' => 'Цена',
            'category' => 'Категория'
        ];

        $validation = $this->request()->validate([
            'name' => ['required'],
            'price' => ['required'],
            'category' => ['required']
        ], $labels);

        if (!$validation) {
            foreach ($this->request()->errors() as $field => $errors) {
                $this->session()->set($field, $errors);
            }
            $this->redirect('/admin/dashboard/goods_and_services');
            return;
        }

        $data = [
            'name' => $this->request()->input('name'),
            'price' => $this->request()->input('price'),
            'category' => $this->request()->input('category'),
            'description' => $this->request()->input('description')
        ];

        try {
            $result = $this->getDatabase()->update('Service', $data, ['id' => $id]);
            if ($result === false) {
                throw new Exception('Не удалось обновить услугу в базе данных');
            }

            // Логирование успешного редактирования
            $payload = [
                'action_name' => 'Редактирование услуги',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Услуга' => $this->request()->input('name'),
                    'Цена' => $this->request()->input('price'),
                    'Категория' => $this->request()->input('category'),
                    'Описание' => $this->request()->input('description'),
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            $this->session()->set('success', 'Услуга успешно обновлена'); // Добавляем сообщение об успехе
            $this->redirect('/admin/dashboard/goods_and_services');
        } catch (Exception $e) {
            $this->session()->set('error', 'Ошибка при обновлении услуги: ' . $e->getMessage());
            $this->redirect('/admin/dashboard/goods_and_services');
        }
    }

    public function deleteService()
    {
        ob_start(); // Начать буферизацию вывода
        header('Content-Type: application/json'); // Установить заголовок JSON
        try {
            // Получить и залогировать сырое тело запроса
            $rawInput = file_get_contents('php://input');
            error_log('deleteService raw input: ' . $rawInput);

            // Декодировать JSON
            $input = json_decode($rawInput, true);
            $id = $input['id'] ?? null;

            if (!$id) {
                error_log('deleteService: ID not provided');
                echo json_encode(['status' => 'error', 'message' => 'ID услуги не указан']);
                ob_end_flush();
                return;
            }

            error_log('deleteService: Processing ID ' . $id);

            // Проверка существования услуги
            $service = $this->getDatabase()->first_found_in_db('Service', ['id' => $id]);
            if (!$service) {
                error_log('deleteService: Service ID ' . $id . ' not found');
                echo json_encode(['status' => 'error', 'message' => 'Услуга не найдена']);
                ob_end_flush();
                return;
            }

            // Удаляем связанные записи в таблице Task
            $deletedTasks = $this->getDatabase()->delete('Task', ['service_id' => $id]);
            error_log('deleteService: Deleted related Tasks for service ID ' . $id . ': ' . $deletedTasks);

            // Удаление связанных записей в Service_Sale
            $this->getDatabase()->delete('Service_Sale', ['service_id' => $id]);
            error_log('deleteService: Deleted related Service_Sale for ID ' . $id);

            // Удаление услуги
            $result = $this->getDatabase()->delete('Service', ['id' => $id]);
            if ($result === false) {
                throw new Exception('Не удалось удалить услугу');
            }
            error_log('deleteService: Successfully deleted service ID ' . $id);

            // Логирование действия
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Удаление услуги',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'Услуга' => $service['name'],
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log('deleteService error for ID ' . ($id ?? 'unknown') . ': ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при удалении услуги: ' . $e->getMessage()]);
        }
        ob_end_flush();
    }

    /**
     * Получить клиента по номеру машины (AJAX)
     */
    public function getClientByCarNumber()
    {
        $state_number = $_GET['state_number'] ?? '';
        if (empty($state_number)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Номер машины не передан']);
            exit;
        }

        $service = new ClientService($this->getDatabase());
        
        // Ищем клиента по номеру машины
        $sql = "SELECT DISTINCT c.id, c.last_name, c.name AS first_name, c.patronymic, c.phone
                FROM Client c
                JOIN Client_cars cc ON c.id = cc.client_id
                JOIN Car car ON cc.car_id = car.id
                WHERE car.state_number = ?";
        
        $clients = $this->getDatabase()->query($sql, [$state_number]);
        
        header('Content-Type: application/json');
        if ($clients && count($clients) > 0) {
            // Берем первого клиента (если несколько машин у одного клиента)
            $client = $clients[0];
            echo json_encode([
                'success' => true, 
                'client' => [
                    'id' => $client['id'],
                    'last_name' => $client['last_name'],
                    'first_name' => $client['first_name'],
                    'patronymic' => $client['patronymic'],
                    'phone' => $client['phone']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'client' => null]);
        }
        exit;
    }

    /**
     * Создание продажи со статусом "pending" (AJAX)
     */
    public function createPendingSale()
    {
        // Очищаем любой предыдущий вывод
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        try {
            // Логируем все полученные данные
            error_log('createPendingSale called. POST data: ' . print_r($_POST, true));
            file_put_contents('./debug.log', date('Y-m-d H:i:s') . ' - createPendingSale called with POST: ' . print_r($_POST, true) . "\n", FILE_APPEND);
            
            $operationType = $_POST['operation_type'] ?? '';
            error_log('Operation type: ' . $operationType);
            file_put_contents('./debug.log', date('Y-m-d H:i:s') . ' - Operation type: ' . $operationType . "\n", FILE_APPEND);
            
            if ($operationType === 'service') {
                // Создаем pending продажу услуг
                error_log('Creating pending service sale');
                $result = $this->createPendingServiceSale();
            } elseif ($operationType === 'product') {
                // Создаем pending продажу товаров
                error_log('Creating pending product sale');
                $result = $this->createPendingProductSale();
            } else {
                error_log('Invalid operation type: ' . $operationType);
                $response = json_encode(['success' => false, 'message' => 'Неверный тип операции: ' . $operationType]);
                error_log('Response: ' . $response);
                echo $response;
                exit;
            }
            
            error_log('Create pending sale result: ' . print_r($result, true));
            
            if ($result['success']) {
                $response = json_encode(['success' => true, 'sale' => $result['sale']]);
                error_log('Success response: ' . $response);
                echo $response;
            } else {
                $response = json_encode(['success' => false, 'message' => $result['message']]);
                error_log('Error response: ' . $response);
                echo $response;
            }
        } catch (Exception $e) {
            error_log('Exception in createPendingSale: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            $response = json_encode(['success' => false, 'message' => 'Ошибка при создании продажи: ' . $e->getMessage()]);
            error_log('Exception response: ' . $response);
            echo $response;
        }
        exit;
    }
    
    /**
     * Подтверждение платежа (AJAX)
     */
    public function confirmPayment()
    {
        header('Content-Type: application/json');
        
        try {
            $saleId = $_POST['sale_id'] ?? null;
            $type = $_POST['type'] ?? '';
            
            if (!$saleId || !$type) {
                echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
                exit;
            }
            
            if ($type === 'service') {
                $result = $this->confirmServicePayment($saleId);
            } elseif ($type === 'product') {
                $result = $this->confirmProductPayment($saleId);
            } else {
                echo json_encode(['success' => false, 'message' => 'Неверный тип операции']);
                exit;
            }
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Платеж подтвержден',
                    'check_url' => $result['check_url']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при подтверждении платежа: ' . $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Отклонение платежа (AJAX)
     */
    public function rejectPayment()
    {
        header('Content-Type: application/json');
        
        try {
            $saleId = $_POST['sale_id'] ?? null;
            $type = $_POST['type'] ?? '';
            $reason = $_POST['reason'] ?? 'Отклонено пользователем';
            
            if (!$saleId || !$type) {
                echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
                exit;
            }
            
            if ($type === 'service') {
                $result = $this->rejectServicePayment($saleId, $reason);
            } elseif ($type === 'product') {
                $result = $this->rejectProductPayment($saleId, $reason);
            } else {
                echo json_encode(['success' => false, 'message' => 'Неверный тип операции']);
                exit;
            }
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Платеж отклонен']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при отклонении платежа: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Обновление статуса платежа
     */
    public function updatePaymentStatus()
    {
        header('Content-Type: application/json');
        
        try {
            // Получаем данные из JSON в теле запроса
            $input = json_decode(file_get_contents('php://input'), true);
            
            $saleId = $input['sale_id'] ?? null;
            $status = $input['status'] ?? null;
            $type = $input['type'] ?? '';
            
            if (!$saleId || !$status || !$type) {
                echo json_encode(['success' => false, 'message' => 'Не все параметры предоставлены']);
                exit;
            }
            
            // Обновляем статус в таблице checks
            $result = $this->getDatabase()->update('checks', [
                'payment_status' => $status
            ], ['id' => $saleId]);

            if ($result === false) {
                echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении статуса']);
                exit;
            }

            // Логируем изменение статуса
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Обновление статуса платежа',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $saleId,
                    'Новый статус' => $status,
                    'Тип операции' => $type,
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            echo json_encode(['success' => true, 'message' => 'Статус успешно обновлен']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении статуса: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Отмена операции (AJAX)
     */
    public function cancelOperation()
    {
        header('Content-Type: application/json');
        
        try {
            $checkId = $_POST['check_id'] ?? null;
            $reason = $_POST['reason'] ?? 'Отмена операции';
            
            if (!$checkId) {
                echo json_encode(['success' => false, 'message' => 'ID чека не указан']);
                exit;
            }

            // Получаем данные чека
            $check = $this->getDatabase()->first_found_in_db('checks', ['id' => $checkId]);
            if (!$check) {
                echo json_encode(['success' => false, 'message' => 'Чек не найден']);
                exit;
            }

            // Обновляем статус платежа
            $this->getDatabase()->update('checks', [
                'payment_status' => 'cancelled'
            ], ['id' => $checkId]);

            // Если это продажа товаров, возвращаем товары на склад
            if ($check['report_type'] === 'product') {
                $checkItems = $this->getDatabase()->get('check_items', ['check_id' => $checkId]);
                
                foreach ($checkItems as $item) {
                    // Находим товар по названию и возвращаем количество
                    $product = $this->getDatabase()->first_found_in_db('Product', ['name' => $item['name']]);
                    if ($product) {
                        $newAmount = $product['amount'] + $item['quantity'];
                        $this->getDatabase()->update('Product', [
                            'amount' => $newAmount
                        ], ['id' => $product['id']]);
                    }
                }
            }

            // Логируем отмену
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Отмена операции',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $checkId,
                    'Причина' => $reason,
                    'Тип операции' => $check['report_type'],
                    'Сумма' => $check['total'],
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            echo json_encode(['success' => true, 'message' => 'Операция успешно отменена']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при отмене операции: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // ================================
    // Приватные методы для управления платежами
    // ================================
    
    /**
     * Создание pending продажи услуг
     */
    private function createPendingServiceSale()
    {
        try {
            // Валидация
            $validation = $this->request()->validate([
                'employee_id' => ['required'],
                'state_number' => ['required'],
                'payment_type' => ['required']
            ], [
                'employee_id' => 'Сотрудник',
                'state_number' => 'Гос. номер',
                'payment_type' => 'Тип оплаты'
            ]);

            if (!$validation) {
                return ['success' => false, 'message' => 'Не заполнены обязательные поля.'];
            }

            $serviceLines = $this->request()->input('services');
            if (!is_array($serviceLines) || empty($serviceLines)) {
                return ['success' => false, 'message' => 'Не выбрано ни одной услуги.'];
            }

            // Обработка данных клиента и машины (копируем логику из addNewServiceSale)
            $clientId = $this->processClientAndCar();
            if (!$clientId) {
                return ['success' => false, 'message' => 'Ошибка при обработке данных клиента/машины'];
            }

            $employeeId = $this->request()->input('employee_id');
            $paymentType = $this->request()->input('payment_type');
            $operatorName = $this->getAuth()->getUser()->username();
            $grandTotal = 0;
            $items = [];
            $markup = (float)($this->request()->input('markup') ?? 0);
            $receivedAmount = (float)($this->request()->input('received_amount') ?? 0);

            foreach ($serviceLines as $line) {
                $servId = $line['service_id'] ?? null;
                if (!$servId) continue;

                $serviceRow = $this->getDatabase()->first_found_in_db('Service', ['id' => $servId]);
                if (!$serviceRow) continue;

                $price = (float)$serviceRow['price'];
                $grandTotal += $price;

                $items[] = [
                    'name' => $serviceRow['name'],
                    'quantity' => 1,
                    'price' => $price,
                    'total' => $price
                ];
            }

            // Добавляем наценку к общей сумме
            $grandTotal += $markup;
            
            // Рассчитываем сдачу
            $changeAmount = max(0, $receivedAmount - $grandTotal);

            // Определяем суммы оплаты
            $cash = 0;
            $card = 0;
            if ($paymentType === 'cash') {
                $cash = $grandTotal;
            } elseif ($paymentType === 'card') {
                $card = $grandTotal;
            } elseif ($paymentType === 'cash_card') {
                $cash = (float)($this->request()->input('cash_amount') ?? 0);
                $card = (float)($this->request()->input('card_amount') ?? 0);
            }

            // Создаем чек со статусом pending
            $checkId = $this->createCheck([
                'check_number' => 'CHK' . time(),
                'date' => date('Y-m-d H:i:s'),
                'total' => $grandTotal,
                'cash' => $cash,
                'card' => $card,
                'discount' => 0,
                'operator_name' => $operatorName,
                'car_number' => $this->request()->input('state_number'),
                'change_amount' => $changeAmount,
                'report_type' => 'service',
                'car_model' => null,
                'car_brand' => $this->request()->input('car_brand'),
                'markup' => $markup,
                'payment_status' => 'pending'
            ]);

            $this->addCheckItems($checkId, $items);

            // Создаем Service_Sale записи
            $car = $this->getDatabase()->first_found_in_db('Car', ['state_number' => $this->request()->input('state_number')]);
            if (!$car) {
                return ['success' => false, 'message' => 'Машина не найдена'];
            }
            
            foreach ($serviceLines as $line) {
                $servId = $line['service_id'] ?? null;
                if (!$servId) continue;
                
                $this->getDatabase()->insert('Service_Sale', [
                    'service_id' => $servId,
                    'employee_id' => $employeeId,
                    'car_id' => $car['id'],
                    'total_amount' => $grandTotal,
                    'payment_method' => $paymentType,
                    'markup' => $markup,
                    'sale_date' => date('Y-m-d H:i:s')
                ]);
            }

            $result = [
                'success' => true,
                'sale' => [
                    'id' => $checkId,
                    'type' => 'service',
                    'total' => $grandTotal
                ]
            ];
            
            error_log('createPendingServiceSale returning: ' . print_r($result, true));
            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при создании продажи: ' . $e->getMessage()];
        }
    }
    
    /**
     * Создание pending продажи товаров
     */
    private function createPendingProductSale()
    {
        try {
            $validation = $this->request()->validate([
                'payment_type' => ['required']
            ], [
                'payment_type' => 'Тип оплаты'
            ]);

            if (!$validation) {
                return ['success' => false, 'message' => 'Не выбран тип оплаты.'];
            }

            $lines = $this->request()->input('products');
            if (!is_array($lines) || empty($lines)) {
                return ['success' => false, 'message' => 'Не выбраны товары.'];
            }

            $paymentType = $this->request()->input('payment_type');
            $operatorName = $this->getAuth()->getUser()->username();
            $grandTotal = 0;
            $markup = (float)($this->request()->input('markup') ?? 0);
            $receivedAmount = (float)($this->request()->input('received_amount') ?? 0);
            $items = [];

            foreach ($lines as $line) {
                $productWarehouseVal = $line['product_warehouse'] ?? null;
                $amount = (int)($line['amount'] ?? 0);

                if (!$productWarehouseVal || $amount < 1) {
                    continue;
                }

                list($productId, $warehouseId) = explode('_', $productWarehouseVal);

                $productRow = $this->getDatabase()->first_found_in_db('Product', [
                    'id' => $productId,
                    'warehouse_id' => $warehouseId
                ]);

                if (!$productRow) {
                    continue;
                }

                $price = (float)$productRow['sale_price'];
                $lineTotal = $price * $amount;
                $grandTotal += $lineTotal;

                $items[] = [
                    'name' => $productRow['name'],
                    'quantity' => $amount,
                    'price' => $price,
                    'total' => $lineTotal
                ];
            }

            // Добавляем наценку к общей сумме
            $grandTotal += $markup;

            if ($paymentType === 'cash') {
                $cash = $grandTotal;
                $card = 0;
            } elseif ($paymentType === 'card') {
                $cash = 0;
                $card = $grandTotal;
            } elseif ($paymentType === 'cash_card') {
                $cash = (float)($this->request()->input('cash_amount') ?? 0);
                $card = (float)($this->request()->input('card_amount') ?? 0);
            }
            
            // Рассчитываем сдачу
            $changeAmount = max(0, $receivedAmount - $grandTotal);

            // Создаем чек со статусом pending
            $checkId = $this->createCheck([
                'check_number' => date('YmdHis'),
                'date' => date('Y-m-d H:i:s'),
                'total' => $grandTotal,
                'cash' => $cash,
                'card' => $card,
                'discount' => 0,
                'operator_name' => $operatorName,
                'car_number' => null,
                'change_amount' => $changeAmount,
                'report_type' => 'product',
                'car_model' => null,
                'car_brand' => null,
                'markup' => $markup,
                'payment_status' => 'pending'
            ]);

            $this->addCheckItems($checkId, $items);

            $result = [
                'success' => true,
                'sale' => [
                    'id' => $checkId,
                    'type' => 'product',
                    'total' => $grandTotal
                ]
            ];
            
            error_log('createPendingProductSale returning: ' . print_r($result, true));
            return $result;

        } catch (Exception $e) {
            error_log('Exception in createPendingProductSale: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return ['success' => false, 'message' => 'Ошибка при создании продажи: ' . $e->getMessage()];
        }
    }
    
    /**
     * Подтверждение платежа за услуги
     */
    private function confirmServicePayment($saleId)
    {
        try {
            // Обновляем статус платежа
            $this->getDatabase()->update('checks', [
                'payment_status' => 'completed'
            ], ['id' => $saleId]);

            // Логируем подтверждение
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Подтверждение платежа за услуги',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $saleId,
                    'Тип операции' => 'service',
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            return [
                'success' => true,
                'check_url' => '/admin/dashboard/check/preview/' . $saleId
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при подтверждении платежа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Подтверждение платежа за товары
     */
    private function confirmProductPayment($saleId)
    {
        try {
            // Обновляем статус платежа
            $this->getDatabase()->update('checks', [
                'payment_status' => 'completed'
            ], ['id' => $saleId]);

            // Уменьшаем остатки товаров на складе
            $checkItems = $this->getDatabase()->get('check_items', ['check_id' => $saleId]);
            foreach ($checkItems as $item) {
                $product = $this->getDatabase()->first_found_in_db('Product', ['name' => $item['name']]);
                if ($product) {
                    $newAmount = max(0, $product['amount'] - $item['quantity']);
                    $this->getDatabase()->update('Product', ['amount' => $newAmount], ['id' => $product['id']]);
                }
            }

            // Логируем подтверждение
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Подтверждение платежа за товары',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $saleId,
                    'Тип операции' => 'product',
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            return [
                'success' => true,
                'check_url' => '/admin/dashboard/check/preview/' . $saleId
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при подтверждении платежа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отклонение платежа за услуги
     */
    private function rejectServicePayment($saleId, $reason)
    {
        try {
            // Обновляем статус платежа
            $this->getDatabase()->update('checks', [
                'payment_status' => 'rejected'
            ], ['id' => $saleId]);

            // Логируем отклонение
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Отклонение платежа за услуги',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $saleId,
                    'Причина' => $reason,
                    'Тип операции' => 'service',
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при отклонении платежа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отклонение платежа за товары
     */
    private function rejectProductPayment($saleId, $reason)
    {
        try {
            // Обновляем статус платежа
            $this->getDatabase()->update('checks', [
                'payment_status' => 'rejected'
            ], ['id' => $saleId]);

            // Логируем отклонение
            $this->getEventManager()->addListener('log.action', new LogActionListener());
            $payload = [
                'action_name' => 'Отклонение платежа за товары',
                'actor_id' => $this->getAuth()->getUser()->id(),
                'action_info' => [
                    'ID чека' => $saleId,
                    'Причина' => $reason,
                    'Тип операции' => 'product',
                    'Пользователь' => $this->getAuth()->getRole()->name() . " " .
                        $this->getAuth()->getUser()->username() . " " .
                        $this->getAuth()->getUser()->lastname()
                ]
            ];
            $event = new LogActionEvent($payload);
            $this->getEventManager()->dispatch($event);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Ошибка при отклонении платежа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Обработка клиента и машины (вынесено в отдельный метод)
     */
    private function processClientAndCar()
    {
        try {
            // Обработка данных клиента
            $clientLastName = $this->request()->input('client_last_name');
            $clientFirstName = $this->request()->input('client_first_name');
            $clientPatronymic = $this->request()->input('client_patronymic');
            $clientPhone = $this->request()->input('client_phone');
            
            // Очищаем телефон от маски
            $clientPhone = preg_replace('/[^0-9]/', '', $clientPhone);
            if (strlen($clientPhone) === 11 && substr($clientPhone, 0, 1) === '7') {
                $clientPhone = substr($clientPhone, 1);
            }
            $clientPhone = '+7' . $clientPhone;

            $stateNumber = $this->request()->input('state_number');
            $newClassId = $this->request()->input('class_id');

            // Ищем существующего клиента по номеру машины
            $existingClient = null;
            $sql = "SELECT DISTINCT c.id, c.last_name, c.name AS first_name, c.patronymic, c.phone
                    FROM Client c
                    JOIN Client_cars cc ON c.id = cc.client_id
                    JOIN Car car ON cc.car_id = car.id
                    WHERE car.state_number = ?";
            $clients = $this->getDatabase()->query($sql, [$stateNumber]);
            
            if ($clients && count($clients) > 0) {
                $existingClient = $clients[0];
            }

            // Создаем или обновляем клиента
            $clientId = null;
            
            if ($existingClient) {
                // Обновляем существующего клиента, если ФИО изменились
                $needUpdate = false;
                $updateData = [];
                
                if ($existingClient['last_name'] !== $clientLastName) {
                    $updateData['last_name'] = $clientLastName;
                    $needUpdate = true;
                }
                if ($existingClient['first_name'] !== $clientFirstName) {
                    $updateData['name'] = $clientFirstName;
                    $needUpdate = true;
                }
                if ($existingClient['patronymic'] !== $clientPatronymic) {
                    $updateData['patronymic'] = $clientPatronymic;
                    $needUpdate = true;
                }
                
                if ($needUpdate) {
                    $this->getDatabase()->update('Client', $updateData, ['id' => $existingClient['id']]);
                }
                $clientId = $existingClient['id'];
            } else {
                // Создаем нового клиента
                $clientId = $this->getDatabase()->insert('Client', [
                    'last_name' => $clientLastName,
                    'name' => $clientFirstName,
                    'patronymic' => $clientPatronymic,
                    'phone' => $clientPhone
                ]);
            }

            // Поиск или создание машины
            $car = $this->getDatabase()->first_found_in_db('Car', ['state_number' => $stateNumber]);

            if (!$car) {
                $carId = $this->getDatabase()->insert('Car', [
                    'state_number' => $stateNumber,
                    'car_brand' => $this->request()->input('car_brand'),
                    'car_model' => null,
                    'class_id' => $newClassId
                ]);
                $car = $this->getDatabase()->first_found_in_db('Car', ['id' => $carId]);
                
                // Связываем клиента с машиной через таблицу Client_cars
                if ($clientId) {
                    $this->getDatabase()->insert('Client_cars', [
                        'client_id' => $clientId,
                        'car_id' => $carId
                    ]);
                }
            } else {
                $carId = $car['id'];
                // Проверяем и обновляем класс, если он изменился
                if ($car['class_id'] != $newClassId) {
                    $this->getDatabase()->update('Car', ['class_id' => $newClassId], ['id' => $carId]);
                    $car['class_id'] = $newClassId;
                }
                
                // Проверяем, связан ли клиент с этой машиной
                if ($clientId) {
                    $existingLink = $this->getDatabase()->first_found_in_db('Client_cars', [
                        'client_id' => $clientId,
                        'car_id' => $carId
                    ]);
                    
                    if (!$existingLink) {
                        // Связываем клиента с машиной
                        $this->getDatabase()->insert('Client_cars', [
                            'client_id' => $clientId,
                            'car_id' => $carId
                        ]);
                    }
                }
            }

            return $clientId;

        } catch (Exception $e) {
            return false;
        }
    }
}
