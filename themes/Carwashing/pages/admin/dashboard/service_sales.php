<?php
use \Source\Models\Service;

/**
 * @var \Core\RenderInterface $render
 * @var \Core\Session\SessionInterface $session
 * @var \Core\Auth\AuthInterface $auth
 * @var \Source\Models\Service $service
 */

$user = $this->auth->getUser();
$services = $data['service']->getAllFromDB();
$users = $data['users']->getAllFromDB();

// Товары
$product_service = $data['products'];
$products = $product_service->getAllFromDBAllSuppliers();

// Склады
$warehouse_service = $data['warehouses'];
$warehouses = $warehouse_service->getAllFromDB();

// Классы автомобилей
$car_classes_service = $data['car_classes_service'];
$car_classes = $car_classes_service->getAllFromDB();

$services_array = $data['service']->getAllFromDBAsArray();
?>

<?php $render->component('dashboard_header'); ?>
<!-- Сайдбар с меню -->
<?php $render->component('menu_sidebar'); ?>
<!-- Подключаем попап для оплаты -->
<?php $render->component('modals/payment_popup'); ?>
<!-- Тело страницы -->
<!-- Контейнер с содержимым страницы -->
<div class="page-content-container">
    <!-- Содержимое страницы -->
    <div class="page-content">

        <?php $render->component('pagecontent_header'); ?>

        <!-- Содержимое страницы -->
        <div class=page-content-body>

            <div class="tabs-container">
                <div class="tabs">
                    <div class="tab active" data-tab="carWash" onclick="switchTab('carWash', 'tabs')">Автомойка</div>
                    <div class="tab" data-tab="cafe" onclick="switchTab('cafe')">Кафе</div>
                </div>
            </div>
            <script>
                const servicesJS = <?php echo json_encode($services_array, JSON_UNESCAPED_UNICODE); ?>;
                const carClassesJS = <?php echo isset($car_classes) ? json_encode($car_classes, JSON_UNESCAPED_UNICODE) : '[]'; ?>;
                const carsJS = <?php echo json_encode($data['cars'], JSON_UNESCAPED_UNICODE); ?>;
            </script>

            <form id="carWashContainer" action="/admin/dashboard/service_sales" method="post" class="tab-content" style="display: block;">
                <input type="hidden" name="operation_type" value="service">
                <!-- Блок с множеством услуг -->
                <div id="servicesLinesContainer" >
                    <!-- Одна строка (пример) -->
                    
                        <div class="service-line-service">
                            <label class="about-service-form-label">Класс автомобиля</label>
                            <select class="about-service-form" name="class_id" id="classSelect" required>
                                <option disabled selected>Выбрать класс</option>
                                <?php foreach ($car_classes as $class) : ?>
                                    <option value="<?= $class->id() ?>">
                                        <?= $class->name() ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    
                    <div class="service-line" data-index="0">
                        <div class="service-line-service">
                            <label>Услуга</label>
                            <select name="services[0][service_id]" class="serviceSelect about-service-form" style="width: 520px;"></select>
                        </div>
                        <div class="service-line-btn">
                            <label for="">ㅤ</label>
                            <button type="button" class="removeServiceBtn"><img src="/assets/themes/Carwashing/img/trash-icon.svg" alt=""></button>
                        </div>
                    </div>
                </div>

                <!-- Кнопка добавления новой строки (ещё одна услуга) -->
                <button type="button" id="addServiceBtn">Добавить услугу</button>
                <!-- Общие поля: сотрудник, номер машины, модель, марка, payment_type -->
                <div class="about-service-forms">
                    <ul class="about-service-forms-first-column">
                        <li>
                            <label class="about-service-form-label">Исполнитель</label>
                            <select class="about-service-form" name="employee_id" required>
                                <option disabled selected>Выбрать исполнителя</option>
                                <?php foreach ($users as $user_model) : ?>
                                    <option value="<?= $user_model->id() ?>"><?= $user_model->username() ?> <?= $user_model->lastname() ?></option>
                                <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label class="about-service-form-label">Наценка</label>
                            <input type="number" min="0" step="0.01" placeholder="0 руб" name="markup" id="markupInput">
                        </li>
                    </ul>
                    <ul class="about-service-forms-second-column">
                        <li>
                            <label class="about-service-form-label">Гос.номер автомобиля</label>
                            <select name="state_number" id="stateNumberInput" class="about-service-form" required></select>
                        </li>
                        <li>
                            <label class="about-service-form-label">Марка автомобиля</label>
                            <input type="text" name="car_brand" id="carBrandInput" placeholder="Ввести марку" required>
                        </li>
                        
                        
                        
                        
                    </ul>
                    <ul class="about-service-forms-third-column">
                        <li>
                            <label class="about-service-form-label">Фамилия клиента</label>
                            <input type="text" name="client_last_name" id="clientLastNameInput" class="about-service-form" placeholder="Ввести фамилию">
                        </li>
                        <li>
                            <label class="about-service-form-label">Имя клиента</label>
                            <input type="text" name="client_first_name" id="clientFirstNameInput" class="about-service-form" placeholder="Ввести имя" required>
                        </li>
                    </ul>
                    <ul class="about-service-forms-fourth-column">
                        <li>
                            <label class="about-service-form-label">Отчество клиента</label>
                            <input type="text" name="client_patronymic" id="clientPatronymicInput" class="about-service-form" placeholder="Ввести отчество">
                        </li>
                        <li>
                            <label class="about-service-form-label">Телефон клиента</label>
                            <input type="text" name="client_phone" id="clientPhoneInput" class="about-service-form" placeholder="+7 (___) ___-__-__" required>
                        </li>
                    </ul>
                    
                </div>

                <div class="payment-section" id="paymentSectionService">
                    <div class="payment-options">
                        <label>Выбрать расчет</label>
                        <fieldset>
                            <label>
                                <input value="cash" name="payment_type" type="radio" checked> Наличный
                            </label>
                            <label>
                                <input value="card" name="payment_type" type="radio"> Безналичный
                            </label>
                            <label>
                                <input value="cash_card" name="payment_type" type="radio"> Нал + безнал
                            </label>
                        </fieldset>
                    </div>
                    <div id="splitPaymentFieldsService" class="split-payment-fields" style="display:none;">
                        <ul>
                            <li>
                                <label class="about-service-form-label">Сумма наличными</label>
                                <input type="number" min="0" step="0.01" name="cash_amount" placeholder="0 руб">
                            </li>
                            <li>
                                <label class="about-service-form-label">Сумма безналичными</label>
                                <input type="number" min="0" step="0.01" name="card_amount" placeholder="0 руб">
                            </li>
                        </ul>
                    </div>
                    <div class="payment-calculations">
                        <div class="total-amount">
                            <label class="total-amount-label">Итоговая сумма</label>
                            <div name="total_amount_service" class="total-amount-value" id="serviceTotal">0 руб</div>
                        </div>
                        
                        <!-- Поле для ввода полученной суммы и расчета сдачи -->
                        <div class="received-amount">
                            <label class="about-service-form-label">Получено от клиента</label>
                            <input type="number" min="0" step="0.01" name="received_amount" id="receivedAmountService" placeholder="0 руб">
                        </div>
                        
                        <div class="change-amount">
                            <label class="total-amount-label">Сдача</label>
                            <div class="total-amount-value" id="serviceChange">0 руб</div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="save-button" id="paymentButton">Оплата</button>
            </form>
            
            <!-- Блок управления платежами (скрыт по умолчанию) -->
            <div id="paymentControlsService" class="payment-controls" style="display: none;">
                <div class="payment-status pending">Ожидание оплаты</div>
                <button type="button" class="confirm-payment" onclick="showPaymentPopup('service')">Оплачено</button>
                <button type="button" class="reject-payment" onclick="showPaymentPopup('service')">Отклонено</button>
            </div>

            <?php $render->component('modals/payment_popup'); ?>

            <script>
                const productsJS = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;
                
                // Обработчик кнопки "Оплата"
                document.getElementById('paymentButton').addEventListener('click', function(e) {
                    e.preventDefault(); // Отменяем отправку формы

                    // Собираем данные формы
                    const form = document.getElementById('carWashContainer');
                    const formData = new FormData(form);

                    // Отправляем запрос на создание pending продажи
                    fetch('/admin/dashboard/service_sales/createPendingSale', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Create pending sale response:', data);
                        if (data.success) {
                            // Сохраняем данные о продаже
                            pendingSaleData = data.sale;
                            // Показываем попап подтверждения
                            showPaymentPopup('service');
                        } else {
                            alert('Ошибка при создании продажи: ' + (data.message || 'Неизвестная ошибка'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Произошла ошибка при создании продажи');
                    });
                });

                // Показать попап оплаты
                function showPaymentPopup(type) {
                    openPaymentPopup({
                        title: 'Подтверждение',
                        message: 'Оплата прошла?',
                        onOk: function() {
                            // Обновляем статус на completed в БД
                            fetch('/goods-and-services/update-payment-status', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ 
                                    sale_id: pendingSaleData.id, 
                                    status: 'completed', 
                                    type: type 
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('Payment status updated to completed');
                                } else {
                                    console.error('Error updating payment status:', data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        },
                        onCancel: function() {
                            // Обновляем статус на canceled в БД
                            fetch('/goods-and-services/update-payment-status', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ 
                                    sale_id: pendingSaleData.id, 
                                    status: 'cancelled', 
                                    type: type 
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    console.log('Payment status updated to cancelled');
                                } else {
                                    console.error('Error updating payment status:', data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        }
                    });
                };
            </script>

            <form id="cafeContainer"
                action="/admin/dashboard/service_sales"
                method="post"
                style="display: block;"
                class="tab-content">
                <input type="hidden" name="operation_type" value="product">

                <div id="productLinesContainer">
                    <!-- Одна строка (изначальная) -->
                    <div class="product-line" data-index="0">
                        <div class="product-line-good">
                            <label>Товар</label>
                            <select name="products[0][product_warehouse]" class="productSelect" style="width: 320px;"></select>
                        </div>

                        <div class="product-line-count">
                            <label>Кол-во</label>
                            <input type="number" name="products[0][amount]" class="productAmountInput" value="1" min="1" style="width: 100px;">
                        </div>

                        <div class="product-line-btn">
                            <!-- Лейбл, где показываем "Всего на складе: XXX (ед. изм.)" -->
                            <label for="products[0][amount]" class="warehouseStockLabel">
                                Всего на складе:
                            </label>

                            <!-- Кнопка удаления позиции -->
                            <button type="button" class="removeLineBtn"><img src="/assets/themes/Carwashing/img/trash-icon.svg" alt=""></button>
                        </div>
                    </div>
                </div>

                <!-- Кнопка добавления новой строки (ещё один товар) -->
                <button type="button" id="addLineBtn">Добавить товар</button>

                <!-- Поле для наценки -->
                <div style="margin: 15px 0;">
                    <label class="about-service-form-label">Наценка</label>
                    <input type="number" min="0" step="0.01" placeholder="0 руб" name="markup" id="markupInputProduct">
                </div>

                <div class="payment-section" id="paymentSectionProduct">
                    <div class="payment-options">
                        <label>Выбрать расчет</label>
                        <fieldset>
                            <label>
                                <input value="cash" name="payment_type" type="radio" checked> Наличный
                            </label>
                            <label>
                                <input value="card" name="payment_type" type="radio"> Безналичный
                            </label>
                            <label>
                                <input value="cash_card" name="payment_type" type="radio"> Комбинированная
                            </label>
                        </fieldset>
                    </div>
                    <div id="splitPaymentFieldsProduct" class="split-payment-fields" style="display:none;">
                        <ul>
                            <li>
                                <label class="about-service-form-label">Сумма наличными</label>
                                <input type="number" min="0" step="0.01" name="cash_amount" placeholder="0 руб">
                            </li>
                            <li>
                                <label class="about-service-form-label">Сумма безналичными</label>
                                <input type="number" min="0" step="0.01" name="card_amount" placeholder="0 руб">
                            </li>
                        </ul>
                    </div>
                    <div class="payment-calculations">
                        <div class="total-amount">
                            <label>Итоговая сумма</label>
                            <div name="total_amount" class="total-amount-value" id="productTotal">0 руб</div>
                        </div>
                        
                        <!-- Поле для ввода полученной суммы и расчета сдачи -->
                        <div class="received-amount">
                            <label class="about-service-form-label">Получено от клиента</label>
                            <input type="number" min="0" step="0.01" name="received_amount" id="receivedAmountProduct" placeholder="0 руб">
                        </div>
                        
                        <div class="change-amount">
                            <label class="total-amount-label">Сдача</label>
                            <div class="total-amount-value" id="productChange">0 руб</div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="saveLineBtn">Оплата</button>
                
            </form>
            
            <!-- Блок управления платежами для товаров (скрыт по умолчанию) -->
            <div id="paymentControlsProduct" class="payment-controls" style="display: none;">
                <div class="payment-status pending">Ожидание оплаты</div>
                <button type="button" class="confirm-payment" onclick="confirmPayment('product')">Оплачено</button>
                <button type="button" class="reject-payment" onclick="rejectPayment('product')">Отклонено</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded fired');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Select2 loaded:', typeof $.fn.select2);
    console.log('Inputmask loaded:', typeof $.fn.inputmask);

    const serviceLinesContainer = document.getElementById('servicesLinesContainer');
    const addServiceBtn = document.getElementById('addServiceBtn');
    const serviceTotalElem = document.getElementById('serviceTotal');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const stateNumberInput = document.getElementById('stateNumberInput');
    const carBrandInput = document.getElementById('carBrandInput');
    const classSelect = document.getElementById('classSelect');
    const productLinesContainer = document.getElementById('productLinesContainer');
    const addProductBtn = document.getElementById('addLineBtn');
    const productTotalElem = document.getElementById('productTotal');
    const productSaveBtn = document.getElementById('saveLineBtn');
    let serviceLineIndex = 0;
    let productLineIndex = 0;

    // Проверка наличия элементов
    if (!serviceLinesContainer) console.error('servicesLinesContainer not found');
    if (!stateNumberInput) console.error('stateNumberInput not found');
    if (!classSelect) console.error('classSelect not found');
    if (!addServiceBtn) console.error('addServiceBtn not found');
    if (!serviceTotalElem) console.error('serviceTotalElem not found');
    if (!productLinesContainer) console.error('productLinesContainer not found');
    if (!addProductBtn) console.error('addProductBtn not found');
    if (!productTotalElem) console.error('productTotalElem not found');

    /* ================================
       Маска для гос. номера
       ================================ */
    if (stateNumberInput) {
        try {
            $(stateNumberInput).inputmask({
                mask: '[АВЕКМНОРСТУХ][0-9]{3}[АВЕКМНОРСТУХ]{2}[0-9]{0,3}',
                placeholder: 'А000АА00',
                showMaskOnHover: false,
                greedy: false,
                definitions: {
                    'A': {
                        validator: '[АВЕКМНОРСТУХ]',
                        casing: 'upper'
                    }
                },
                onBeforePaste: function(pastedValue) {
                    console.log('Pasted value:', pastedValue);
                    return pastedValue.toUpperCase();
                },
                onBeforeWrite: function(e, buffer, caretPos, opts) {
                    console.log('Input value:', buffer.join(''));
                    return true;
                }
            });
            console.log('Inputmask initialized for stateNumberInput');
        } catch (e) {
            console.error('Error initializing Inputmask:', e);
        }
    }

    /* ================================
       Маска для телефона клиента
       ================================ */
    const clientPhoneInput = document.getElementById('clientPhoneInput');
    if (clientPhoneInput) {
        try {
            $(clientPhoneInput).inputmask({
                mask: "+7 (999) 999-99-99",
                placeholder: "+7 (___) ___-__-__",
                showMaskOnHover: false,
                greedy: false
            });
            console.log('Inputmask initialized for clientPhoneInput');
        } catch (e) {
            console.error('Error initializing Inputmask for clientPhoneInput:', e);
        }
    }

    /* ================================
       Автозаполнение гос. номеров
       ================================ */
    if (stateNumberInput) {
        try {
            $(stateNumberInput).select2({
                ajax: {
                    url: '/admin/cars/autocomplete',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        console.log('Select2 search term:', params.term);
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        console.log('Autocomplete response:', data);
                        if (data.error) {
                            console.error('Server error:', data.error);
                            return { results: [] };
                        }
                        return {
                            results: data.map(car => ({
                                id: car.state_number,
                                text: car.state_number,
                                car_brand: car.car_brand || '',
                                class_id: car.class_id ? String(car.class_id) : ''
                            }))
                        };
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown, jqXHR.responseText);
                        return { results: [] };
                    }
                },
                placeholder: 'А000АА00',
                minimumInputLength: 2,
                allowClear: true,
                tags: true,
                createTag: function(params) {
                    const term = params.term.trim().toUpperCase().replace(/[^АВЕКМНОРСТУХ0-9]/g, '');
                    console.log('createTag term:', term);
                    const regex = /^[АВЕКМНОРСТУХ][0-9]{3}[АВЕКМНОРСТУХ]{2}[0-9]{0,3}$/;
                    if (!regex.test(term)) {
                        console.log('createTag failed validation:', term);
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        car_brand: '',
                        class_id: ''
                    };
                },
                width: '100%',
                language: {
                    inputTooShort: () => 'Пожалуйста, введите 2 символа',
                    noResults: () => 'Номер не найден',
                    errorLoading: () => 'Ошибка загрузки результатов'
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                console.log('Selected car:', data);
                stateNumberInput.value = data.id;
                carBrandInput.value = data.car_brand || '';
                classSelect.value = data.class_id || '';
                $(classSelect).trigger('change');
                
                // Автозаполнение клиента при выборе номера машины
                if (data.id) {
                    fetch('/admin/dashboard/service_sales/getClientByCarNumber?state_number=' + encodeURIComponent(data.id))
                        .then(response => response.json())
                        .then(clientData => {
                            if (clientData.success && clientData.client) {
                                const client = clientData.client;
                                document.getElementById('clientLastNameInput').value = client.last_name || '';
                                document.getElementById('clientFirstNameInput').value = client.first_name || '';
                                document.getElementById('clientPatronymicInput').value = client.patronymic || '';
                                document.getElementById('clientPhoneInput').value = client.phone || '';
                                // Делаем телефон readonly, если клиент найден
                                document.getElementById('clientPhoneInput').readOnly = true;
                            } else {
                                // Очищаем поля клиента, если клиент не найден
                                document.getElementById('clientLastNameInput').value = '';
                                document.getElementById('clientFirstNameInput').value = '';
                                document.getElementById('clientPatronymicInput').value = '';
                                document.getElementById('clientPhoneInput').value = '';
                                document.getElementById('clientPhoneInput').readOnly = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching client data:', error);
                            // Очищаем поля клиента в случае ошибки
                            document.getElementById('clientLastNameInput').value = '';
                            document.getElementById('clientFirstNameInput').value = '';
                            document.getElementById('clientPatronymicInput').value = '';
                            document.getElementById('clientPhoneInput').value = '';
                            document.getElementById('clientPhoneInput').readOnly = false;
                        });
                }
            }).on('select2:unselect', function() {
                carBrandInput.value = '';
                classSelect.value = '';
                $(classSelect).trigger('change');
                
                // Очищаем поля клиента при отмене выбора номера
                document.getElementById('clientLastNameInput').value = '';
                document.getElementById('clientFirstNameInput').value = '';
                document.getElementById('clientPatronymicInput').value = '';
                document.getElementById('clientPhoneInput').value = '';
                document.getElementById('clientPhoneInput').readOnly = false;
            });
            console.log('Select2 initialized for stateNumberInput');
        } catch (e) {
            console.error('Error initializing Select2 for stateNumberInput:', e);
        }
    }

    /* ================================
   Инициализация Select2 для classSelect
   ================================ */
if (classSelect) {
    try {
        $(classSelect).select2({
            placeholder: 'Выбрать класс',
            allowClear: true,
            width: 'auto',
            dropdownParent: $('#carWashContainer'),
            language: { noResults: () => 'Класс не найден' }
        }).on('change', () => {
            // Обновляем все .serviceSelect при изменении класса
            document.querySelectorAll('.service-line').forEach(line => {
                const selectEl = line.querySelector('.serviceSelect');
                $(selectEl).select2('destroy');
                fillServiceSelect(selectEl);
                $(selectEl).select2({
                    placeholder: 'Выбрать услугу',
                    allowClear: true,
                    width: 'auto',
                    dropdownParent: $(line),
                    language: { noResults: () => 'Услуга не найдена' }
                });
            });
            updateServiceTotal();
        }).val('').trigger('change'); // Изменение здесь
        console.log('Select2 initialized for classSelect');
    } catch (e) {
        console.error('Error initializing Select2 for classSelect:', e);
    }
}

    document.getElementById('clearClassBtn')?.addEventListener('click', () => {
        $(classSelect).val('').trigger('change');
    });

    /* ================================
       Логика для услуг (Автомойка)
       ================================ */
    function fillServiceSelect(selectEl) {
        console.log('Filling service select, servicesJS:', servicesJS);
        selectEl.innerHTML = '<option disabled selected>Выбрать услугу</option>';
        if (!Array.isArray(servicesJS)) {
            console.error('servicesJS is not an array:', servicesJS);
            return;
        }
        const selectedClass = classSelect?.value; // Используем ID класса как фильтр
        servicesJS.forEach(srv => {
            // Проверяем, что category совпадает с selectedClass или показываем все, если класс не выбран
            if (!selectedClass || String(srv.category) === selectedClass) {
                const opt = document.createElement('option');
                opt.value = srv.id;
                opt.textContent = srv.name;
                opt.dataset.price = srv.price;
                selectEl.appendChild(opt);
            }
        });
        console.log('Service select options:', selectEl.innerHTML);
    }

    function initializeServiceLine(lineDiv, idx) {
        const selectEl = lineDiv.querySelector('.serviceSelect');
        if (!selectEl) {
            console.error('serviceSelect not found in lineDiv:', lineDiv);
            return;
        }
        const errorMsg = document.createElement('div');
        errorMsg.className = 'input-error';
        errorMsg.style.color = 'red';
        lineDiv.appendChild(errorMsg);

        fillServiceSelect(selectEl);
        try {
            $(selectEl).select2({
                placeholder: 'Выбрать услугу',
                allowClear: true,
                width: 'auto',
                dropdownParent: $(lineDiv),
                language: { noResults: () => 'Услуга не найдена' }
            }).on('change', () => {
                validateServiceLine();
                updateServiceTotal();
            });
            console.log('Select2 initialized for serviceSelect:', selectEl);
        } catch (e) {
            console.error('Error initializing Select2 for serviceSelect:', e);
        }

        const removeBtn = lineDiv.querySelector('.removeServiceBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                try {
                    $(selectEl).select2('destroy');
                } catch (e) {
                    console.error('Error destroying Select2:', e);
                }
                lineDiv.remove();
                updateServiceTotal();
            });
        } else {
            console.error('removeServiceBtn not found in lineDiv:', lineDiv);
        }

        function validateServiceLine() {
            const opt = selectEl.selectedOptions[0];
            errorMsg.textContent = opt ? '' : 'Выберите услугу';
        }
    }

    function updateServiceTotal() {
        let total = 0;
        document.querySelectorAll('.service-line').forEach(line => {
            const opt = line.querySelector('.serviceSelect')?.selectedOptions[0];
            if (!opt) return;
            const basePrice = parseFloat(opt.dataset.price) || 0;
            total += basePrice;
            console.log('Service:', opt.textContent, 'Base Price:', basePrice);
        });

        // Добавляем наценку
        const markupInput = document.getElementById('markupInput');
        const markup = parseFloat(markupInput?.value || 0);
        total += markup;

        total = Math.round(total * 100) / 100;
        if (serviceTotalElem) {
            serviceTotalElem.textContent = `${total} руб`;
        }
        if (totalAmountInput) {
            totalAmountInput.value = total;
            console.log('Total Amount Input:', totalAmountInput.value);
        }
    }

    function addServiceLine() {
        const idx = serviceLineIndex++;
        const div = document.createElement('div');
        div.className = 'service-line';
        div.dataset.index = idx;
        div.innerHTML = `
            <div class="service-line-service">
                <label>Услуга</label>
                <select name="services[${idx}][service_id]" class="serviceSelect"></select>
            </div>
            <div class="service-line-btn">
                <label>ㅤ</label>
                <button type="button" class="removeServiceBtn">
                    <img src="/assets/themes/Carwashing/img/trash-icon.svg" alt="Удалить">
                </button>
            </div>`;
        serviceLinesContainer.appendChild(div);
        console.log('Added service line:', div);
        initializeServiceLine(div, idx);
        updateServiceTotal();
    }

    if (serviceLinesContainer && addServiceBtn) {
        try {
            addServiceBtn.addEventListener('click', addServiceLine);
            const firstServ = serviceLinesContainer.querySelector('.service-line');
            if (firstServ) {
                console.log('Found existing service line:', firstServ);
                initializeServiceLine(firstServ, 0);
                serviceLineIndex = 1;
            } else {
                console.log('No existing service line, adding new one');
                addServiceLine();
            }
        } catch (e) {
            console.error('Error setting up service lines:', e);
        }
    }

    /* ================================
       Логика для товаров (Кафе)
       ================================ */
    function fillProductSelect(selectEl) {
        console.log('Filling product select, productsJS:', productsJS);
        selectEl.innerHTML = '<option disabled selected>Выбрать товар</option>';
        if (!Array.isArray(productsJS)) {
            console.error('productsJS is not an array:', productsJS);
            return;
        }
        productsJS.forEach(prod => {
            const opt = document.createElement('option');
            opt.value = `${prod.id}_${prod.warehouse_id}`;
            opt.textContent = `${prod.name} [склад ${prod.warehouse_id}]`;
            opt.dataset.price = prod.sale_price;
            opt.dataset.amount = prod.amount;
            opt.dataset.unit = prod.unit_measurement;
            selectEl.appendChild(opt);
        });
        console.log('Product select options:', selectEl.innerHTML);
    }

    function initializeProductLine(lineDiv, idx) {
        const selectEl = lineDiv.querySelector('.productSelect');
        const inputEl = lineDiv.querySelector('.productAmountInput');
        const stockLabel = lineDiv.querySelector('.warehouseStockLabel');
        if (!selectEl || !inputEl || !stockLabel) {
            console.error('productSelect, productAmountInput, or warehouseStockLabel not found in lineDiv:', lineDiv);
            return;
        }
        const errorMsg = document.createElement('div');
        errorMsg.className = 'input-error';
        errorMsg.style.color = 'red';
        inputEl.parentNode.appendChild(errorMsg);

        fillProductSelect(selectEl);
        try {
            $(selectEl).select2({
                placeholder: 'Выбрать товар',
                allowClear: true,
                width: '400px',
                dropdownParent: $(lineDiv),
                language: { noResults: () => 'Товар не найден' }
            }).on('change', () => {
                validateProductLine();
                updateProductTotal();
            });
            console.log('Select2 initialized for productSelect:', selectEl);
        } catch (e) {
            console.error('Error initializing Select2 for productSelect:', e);
        }

        inputEl.addEventListener('input', () => {
            inputEl.value = inputEl.value.replace(/[^\d]/g, '');
            validateProductLine();
            updateProductTotal();
        });

        const removeBtn = lineDiv.querySelector('.removeLineBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                try {
                    $(selectEl).select2('destroy');
                } catch (e) {
                    console.error('Error destroying Select2:', e);
                }
                lineDiv.remove();
                updateProductTotal();
            });
        } else {
            console.error('removeLineBtn not found in lineDiv:', lineDiv);
        }

        function validateProductLine() {
            const opt = selectEl.selectedOptions[0];
            if (!opt) {
                stockLabel.textContent = 'Всего на складе: ---';
                errorMsg.textContent = 'Выберите товар';
                if (productSaveBtn) disableBtn(productSaveBtn, 'Выберите товар');
                return;
            }
            const stock = parseInt(opt.dataset.amount, 10);
            const unit = opt.dataset.unit;
            stockLabel.textContent = `Всего на складе: ${stock} (${unit})`;

            let val = parseInt(inputEl.value, 10);
            if (isNaN(val) || val < 1) {
                errorMsg.textContent = 'Минимум 1';
                if (productSaveBtn) disableBtn(productSaveBtn, 'Минимум 1');
                return;
            }
            if (val > stock) {
                errorMsg.textContent = `Максимум ${stock}`;
                inputEl.value = stock;
                if (productSaveBtn) disableBtn(productSaveBtn, `Максимум ${stock}`);
                return;
            }
            errorMsg.textContent = '';
            if (productSaveBtn) enableBtn(productSaveBtn);
        }

        function disableBtn(btn, text) {
            btn.disabled = true;
            btn.textContent = text;
            btn.style.cursor = 'not-allowed';
            btn.style.backgroundColor = '#D33B4C';
        }

        function enableBtn(btn, text = 'Сохранить') {
            btn.disabled = false;
            btn.textContent = text;
            btn.style.cursor = 'pointer';
            btn.style.backgroundColor = '#707FDD';
        }
    }

    function updateProductTotal() {
        let total = 0;
        document.querySelectorAll('.product-line').forEach(line => {
            const sel = line.querySelector('.productSelect');
            const inp = line.querySelector('.productAmountInput');
            const opt = sel?.selectedOptions[0];
            if (!opt) return;
            total += (parseFloat(opt.dataset.price) || 0) * (parseInt(inp.value, 10) || 0);
        });
        
        total = Math.round(total * 100) / 100;
        if (productTotalElem) {
            productTotalElem.textContent = `${total} руб`;
        }
    }

    function addProductLine() {
        const idx = productLineIndex++;
        const div = document.createElement('div');
        div.className = 'product-line';
        div.dataset.index = idx;
        div.innerHTML = `
            <div class="product-line-good">
                <label>Товар</label>
                <select name="products[${idx}][product_warehouse]" class="productSelect"></select>
            </div>
            <div class="product-line-count">
                <label>Кол-во</label>
                <input type="number" name="products[${idx}][amount]" class="productAmountInput" value="1" min="1" style="width: 100px;">
            </div>
            <div class="product-line-btn">
                <label class="warehouseStockLabel">Всего на складе:</label>
                <button type="button" class="removeLineBtn">
                    <img src="/assets/themes/Carwashing/img/trash-icon.svg" alt="Удалить">
                </button>
            </div>`;
        productLinesContainer.appendChild(div);
        console.log('Added product line:', div);
        initializeProductLine(div, idx);
        updateProductTotal();
    }

    if (productLinesContainer && addProductBtn) {
        try {
            addProductBtn.addEventListener('click', addProductLine);
            const firstProd = productLinesContainer.querySelector('.product-line');
            if (firstProd) {
                console.log('Found existing product line:', firstProd);
                initializeProductLine(firstProd, 0);
                productLineIndex = 1;
            } else {
                console.log('No existing product line, adding new one');
                addProductLine();
            }
        } catch (e) {
            console.error('Error setting up product lines:', e);
        }
    }

    // ================================
    // Split Payment (нал + безнал)
    // ================================
    function setupSplitPayment(paymentSectionId, splitFieldsId, totalElemId, formId) {
        const section = document.getElementById(paymentSectionId);
        const splitFields = document.getElementById(splitFieldsId);
        const totalElem = document.getElementById(totalElemId);
        const form = document.getElementById(formId);
        if (!section || !splitFields || !totalElem || !form) return;
        const cashInput = splitFields.querySelector('input[name="cash_amount"]');
        const cardInput = splitFields.querySelector('input[name="card_amount"]');
        if (!cashInput || !cardInput) return;

        function getTotal() {
            return parseFloat((totalElem.textContent || '0').replace(/[^\d.]/g, '')) || 0;
        }

        function showSplit(show) {
            splitFields.style.display = show ? 'block' : 'none';
            if (!show) {
                cashInput.value = '';
                cardInput.value = '';
            }
        }

        // Показывать/скрывать поля при выборе типа оплаты
        section.querySelectorAll('input[name="payment_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                showSplit(this.value === 'cash_card');
            });
        });
        // При загрузке страницы
        const checkedRadio = section.querySelector('input[name="payment_type"]:checked');
        showSplit(checkedRadio && checkedRadio.value === 'cash_card');

        // Валидация и автозаполнение
        function validateAndSync(e) {
            const total = getTotal();
            let cash = parseFloat(cashInput.value) || 0;
            let card = parseFloat(cardInput.value) || 0;
            if (e && e.target === cashInput) {
                if (cash > total) cash = total;
                if (cash < 0) cash = 0;
                cashInput.value = cash;
                cardInput.value = (total - cash).toFixed(2);
            } else if (e && e.target === cardInput) {
                if (card > total) card = total;
                if (card < 0) card = 0;
                cardInput.value = card;
                cashInput.value = (total - card).toFixed(2);
            }
            if (parseFloat(cashInput.value) < 0) cashInput.value = 0;
            if (parseFloat(cardInput.value) < 0) cardInput.value = 0;
            if (parseFloat(cashInput.value) > total) cashInput.value = total;
            if (parseFloat(cardInput.value) > total) cardInput.value = total;
            if ((parseFloat(cashInput.value) + parseFloat(cardInput.value)).toFixed(2) != total.toFixed(2)) {
                cashInput.style.borderColor = '#D33B4C';
                cardInput.style.borderColor = '#D33B4C';
            } else {
                cashInput.style.borderColor = '';
                cardInput.style.borderColor = '';
            }
        }
        cashInput.addEventListener('input', validateAndSync);
        cardInput.addEventListener('input', validateAndSync);
        // При изменении итоговой суммы тоже обновлять split
        const observer = new MutationObserver(validateAndSync);
        observer.observe(totalElem, { childList: true, subtree: true });

        form.addEventListener('submit', function(e) {
            const selected = section.querySelector('input[name="payment_type"]:checked');
            if (selected && selected.value === 'cash_card') {
                const total = getTotal();
                const cash = parseFloat(cashInput.value) || 0;
                const card = parseFloat(cardInput.value) || 0;
                if ((cash + card).toFixed(2) != total.toFixed(2)) {
                    e.preventDefault();
                    alert('Сумма по налу и безналу должна быть равна итоговой сумме!');
                    cashInput.style.borderColor = '#D33B4C';
                    cardInput.style.borderColor = '#D33B4C';
                }
            }
        });
    }
    setupSplitPayment('paymentSectionService', 'splitPaymentFieldsService', 'serviceTotal', 'carWashContainer');
    setupSplitPayment('paymentSectionProduct', 'splitPaymentFieldsProduct', 'productTotal', 'cafeContainer');

    // Обработчик события для поля наценки услуг
    const markupInput = document.getElementById('markupInput');
    if (markupInput) {
        markupInput.addEventListener('input', () => {
            updateServiceTotal();
        });
    }

    // ================================
    // Расчет сдачи
    // ================================
    function setupChangeCalculation(receivedInputId, totalElemId, changeElemId) {
        const receivedInput = document.getElementById(receivedInputId);
        const totalElem = document.getElementById(totalElemId);
        const changeElem = document.getElementById(changeElemId);
        
        if (!receivedInput || !totalElem || !changeElem) return;
        
        function calculateChange() {
            const total = parseFloat((totalElem.textContent || '0').replace(/[^\d.]/g, '')) || 0;
            const received = parseFloat(receivedInput.value) || 0;
            const change = Math.max(0, received - total);
            
            changeElem.textContent = `${change.toFixed(2)} руб`;
            
            // Валидация
            if (received < total) {
                receivedInput.style.borderColor = '#D33B4C';
                receivedInput.title = 'Полученная сумма меньше итоговой!';
            } else {
                receivedInput.style.borderColor = '';
                receivedInput.title = '';
            }
            
            // Обновляем стиль кнопки сохранения
            const form = receivedInput.closest('form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    if (received < total) {
                        submitBtn.disabled = true;
                        submitBtn.style.backgroundColor = '#D33B4C';
                        submitBtn.style.cursor = 'not-allowed';
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.style.backgroundColor = '#707FDD';
                        submitBtn.style.cursor = 'pointer';
                    }
                }
            }
        }
        
        receivedInput.addEventListener('input', calculateChange);
        
        // При изменении итоговой суммы тоже пересчитывать сдачу
        const observer = new MutationObserver(calculateChange);
        observer.observe(totalElem, { childList: true, subtree: true });
    }
    
    // Настройка расчета сдачи для услуг и товаров
    setupChangeCalculation('receivedAmountService', 'serviceTotal', 'serviceChange');
    setupChangeCalculation('receivedAmountProduct', 'productTotal', 'productChange');

    // ================================
    // Валидация формы перед отправкой
    // ================================
    
    // Для формы услуг
    const carWashForm = document.getElementById('carWashContainer');
    if (carWashForm) {
        carWashForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Предотвращаем стандартную отправку
            
            const receivedAmount = parseFloat(document.getElementById('receivedAmountService').value) || 0;
            const total = parseFloat((document.getElementById('serviceTotal').textContent || '0').replace(/[^\d.]/g, '')) || 0;
            
            if (receivedAmount < total) {
                alert('Полученная сумма не может быть меньше итоговой суммы!');
                document.getElementById('receivedAmountService').style.borderColor = '#D33B4C';
                return false;
            }
            
            // Проверяем, что выбрана хотя бы одна услуга
            const selectedServices = document.querySelectorAll('.service-line .serviceSelect option:checked');
            if (selectedServices.length === 0) {
                alert('Выберите хотя бы одну услугу!');
                return false;
            }
            
            // Скрываем форму и показываем блок управления платежами
            carWashForm.style.display = 'none';
            document.getElementById('paymentControlsService').style.display = 'block';
            
            // Создаем продажу со статусом "pending"
            createPendingSale('service');
        });
    }
    
    // Для формы товаров
    const cafeForm = document.getElementById('cafeContainer');
    if (cafeForm) {
        cafeForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Предотвращаем стандартную отправку
            
            const receivedAmount = parseFloat(document.getElementById('receivedAmountProduct').value) || 0;
            const total = parseFloat((document.getElementById('productTotal').textContent || '0').replace(/[^\d.]/g, '')) || 0;
            
            if (receivedAmount < total) {
                alert('Полученная сумма не может быть меньше итоговой суммы!');
                document.getElementById('receivedAmountProduct').style.borderColor = '#D33B4C';
                return false;
            }
            
            // Проверяем, что выбран хотя бы один товар
            const selectedProducts = document.querySelectorAll('.product-line .productSelect option:checked');
            if (selectedProducts.length === 0) {
                alert('Выберите хотя бы один товар!');
                return false;
            }
            
            // Скрываем форму и показываем блок управления платежами
            cafeForm.style.display = 'none';
            document.getElementById('paymentControlsProduct').style.display = 'block';
            
            // Создаем продажу со статусом "pending"
            createPendingSale('product');
        });
    }

    // ================================
    // Функции управления платежами
    // ================================
    
    let pendingSaleData = null;
    
    // Создание продажи со статусом "pending"
    function createPendingSale(type) {
        console.log('createPendingSale called with type:', type);
        const formData = new FormData();
        
        if (type === 'service') {
            const form = document.getElementById('carWashContainer');
            if (!form) {
                console.error('carWashContainer form not found');
                alert('Ошибка: форма не найдена');
                return;
            }
            
            const formDataObj = new FormData(form);
            
            // Проверяем обязательные поля
            const requiredFields = ['employee_id', 'state_number', 'payment_type'];
            for (let field of requiredFields) {
                if (!formDataObj.get(field)) {
                    console.error('Required field missing:', field);
                    alert(`Не заполнено поле: ${field}`);
                    return;
                }
            }
            
            // Добавляем все данные формы
            for (let [key, value] of formDataObj.entries()) {
                formData.append(key, value);
                console.log('Service form data:', key, '=', value);
            }
            
            // Добавляем тип операции
            formData.append('operation_type', 'service');
            
            // Добавляем данные о наценке и полученной сумме
            const markup = document.getElementById('markupInput') ? parseFloat(document.getElementById('markupInput').value) || 0 : 0;
            const receivedAmount = parseFloat(document.getElementById('receivedAmountService').value) || 0;
            formData.append('markup', markup);
            formData.append('received_amount', receivedAmount);
            
            console.log('Service markup:', markup, 'receivedAmount:', receivedAmount);
            
        } else if (type === 'product') {
            const form = document.getElementById('cafeContainer');
            if (!form) {
                console.error('cafeContainer form not found');
                alert('Ошибка: форма не найдена');
                return;
            }
            
            const formDataObj = new FormData(form);
            
            // Добавляем все данные формы
            for (let [key, value] of formDataObj.entries()) {
                formData.append(key, value);
                console.log('Product form data:', key, '=', value);
            }
            
            // Добавляем тип операции
            formData.append('operation_type', 'product');
            
            // Добавляем данные о наценке и полученной сумме
            const markup = document.getElementById('markupInputProduct') ? parseFloat(document.getElementById('markupInputProduct').value) || 0 : 0;
            const receivedAmount = parseFloat(document.getElementById('receivedAmountProduct').value) || 0;
            formData.append('markup', markup);
            formData.append('received_amount', receivedAmount);
            
            console.log('Product markup:', markup, 'receivedAmount:', receivedAmount);
        }
        
        // Логируем все данные формы перед отправкой
        console.log('FormData before sending:');
        for (let [key, value] of formData.entries()) {
            console.log(key, '=', value);
        }
        
        // Отправляем данные на сервер для создания pending продажи
        fetch('/admin/dashboard/service_sales/createPendingSale', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Получаем текст ответа для отладки
            return response.text().then(text => {
                console.log('Raw response text:', text);
                
                // Пытаемся распарсить JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON data:', data);
                    return data;
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response text that failed to parse:', text);
                    throw new Error(`Ошибка парсинга JSON: ${jsonError.message}. Ответ сервера: ${text.substring(0, 200)}`);
                }
            });
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                pendingSaleData = data.sale;
                console.log('Pending sale created:', pendingSaleData);
            } else {
                console.error('Server error:', data.message);
                alert('Ошибка при создании продажи: ' + (data.message || 'Неизвестная ошибка'));
                // Возвращаем форму обратно
                if (type === 'service') {
                    document.getElementById('carWashContainer').style.display = 'block';
                    document.getElementById('paymentControlsService').style.display = 'none';
                    pendingSaleData = null;
                } else {
                    document.getElementById('cafeContainer').style.display = 'block';
                    document.getElementById('paymentControlsProduct').style.display = 'none';
                    pendingSaleData = null;
                }
            }
        })
        .catch(error => {
            console.error('Error creating pending sale:', error);
            alert('Ошибка при создании продажи: ' + error.message);
            // Возвращаем форму обратно при ошибке
            if (type === 'service') {
                document.getElementById('carWashContainer').style.display = 'block';
                document.getElementById('paymentControlsService').style.display = 'none';
            } else {
                document.getElementById('cafeContainer').style.display = 'block';
                document.getElementById('paymentControlsProduct').style.display = 'none';
            }
            pendingSaleData = null;
        });
    }
    
    // Обновляем функцию confirmPayment для работы без перехода на другую страницу
    function confirmPayment(type) {
        if (!pendingSaleData) {
            openPaymentPopup({
                title: 'Ошибка',
                message: 'Данные продажи не найдены',
                onOk: null,
                onCancel: null
            });
            return;
        }

        openPaymentPopup({
            title: 'Подтверждение оплаты',
            message: 'Клиент оплатил услугу?',
            onOk: function() {
                fetch('/admin/dashboard/service_sales/updatePaymentStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ sale_id: pendingSaleData.id, status: 'completed', type: type })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.payment-status').classList.remove('pending');
                        document.querySelector('.payment-status').classList.add('completed');
                        document.querySelector('.payment-status').innerText = 'Оплачено';
                    } else {
                        openPaymentPopup({
                            title: 'Ошибка',
                            message: 'Ошибка при обновлении статуса оплаты: ' + data.message,
                            onOk: null,
                            onCancel: null
                        });
                    }
                })
                .catch(error => {
                    console.error('Error updating payment status:', error);
                    openPaymentPopup({
                        title: 'Ошибка',
                        message: 'Ошибка при обновлении статуса оплаты',
                        onOk: null,
                        onCancel: null
                    });
                });
            },
            onCancel: null
        });
    }
    
    // Обновляем функцию rejectPayment для работы без перехода на другую страницу
    function rejectPayment(type) {
        if (!pendingSaleData) {
            openPaymentPopup({
                title: 'Ошибка',
                message: 'Данные продажи не найдены',
                onOk: null,
                onCancel: null
            });
            return;
        }

        openPaymentPopup({
            title: 'Отклонение оплаты',
            message: 'Вы уверены, что хотите отклонить оплату?',
            onOk: function() {
                fetch('/admin/dashboard/service_sales/updatePaymentStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ sale_id: pendingSaleData.id, status: 'canceled', type: type })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.payment-status').classList.remove('pending');
                        document.querySelector('.payment-status').classList.add('rejected');
                        document.querySelector('.payment-status').innerText = 'Отклонено';
                    } else {
                        openPaymentPopup({
                            title: 'Ошибка',
                            message: 'Ошибка при обновлении статуса оплаты: ' + data.message,
                            onOk: null,
                            onCancel: null
                        });
                    }
                })
                .catch(error => {
                    console.error('Error updating payment status:', error);
                    openPaymentPopup({
                        title: 'Ошибка',
                        message: 'Ошибка при обновлении статуса оплаты',
                        onOk: null,
                        onCancel: null
                    });
                });
            },
            onCancel: null
        });
        // Добавить поле для причины, если это отклонение
        setTimeout(function() {
            if (!document.getElementById('paymentPopupInputReason')) {
                var input = document.createElement('input');
                input.type = 'text';
                input.id = 'paymentPopupInputReason';
                input.placeholder = 'Причина отклонения';
                input.style = 'width: 90%; margin: 10px auto; display: block; padding: 8px; border-radius: 6px; border: 1px solid #ccc;';
                var msg = document.getElementById('paymentPopupMessage');
                if (msg) msg.appendChild(input);
            }
        }, 100);
    }

    // Отключаем стандартное поведение кнопки "Оплата" и вызываем попап
    const paymentButton = document.getElementById('paymentButton');
    if (paymentButton) {
        paymentButton.addEventListener('click', function(event) {
            event.preventDefault(); // Отключаем отправку формы
            openPaymentPopup({
                title: 'Подтверждение оплаты',
                message: 'Вы уверены, что хотите продолжить оплату?',
                onOk: function() {
                    // Здесь можно добавить логику для обработки оплаты
                    console.log('Оплата подтверждена');
                },
                onCancel: function() {
                    console.log('Оплата отменена');
                }
            });
        });
    }
});
</script>

<?php $render->component('dashboard_footer'); ?>