<?php
/**
 * @var \Core\RenderInterface $render
 * @var \Core\Session\SessionInterface $session
 * @var \Core\Auth\AuthInterface $auth
 * @var array $employees
 * @var array $productReports
 * @var array $products
 * @var array $services
 * @var array $reports
 * @var string $reportType
 * @var string $selectedId
 * @var string $selectedStartDate
 * @var string $selectedEndDate
 */


?>

<?php $render->component('dashboard_header'); ?>
<?php $render->component('menu_sidebar'); ?>

<div class="page-content-container">
    <div class="page-content">
        <?php $render->component('pagecontent_header'); ?>

        <div class="page-content-body">
            <div class="tabs-container">
                <div class="tabs">
                    <div class="tab active" data-tab="create-report" onclick="switchTab('create-report')">Создать отчет</div>
                    <div class="tab" data-tab="reports" onclick="switchTab('reports')">Отчеты</div>
                    <div class="tab" id="lastActionsTab" data-tab="last-actions" onclick="switchTab('last-actions')">Последние действия</div>
                </div>
            </div>

            <div class="tab-content" id="create-reportContainer">
                <div class="create-report-tab-container">
                    <label class="financial-accounting-first-label">Создание отчета</label>
                    <div id="formMessage" style="color: red; display: none;"></div>

                    <div class="create-report-container" id="createReport">
                        <div class="financial-accounting-first__create">
                            <form class="financial-accounting-first__create-forms" id="reportForm" method="POST" action="/admin/dashboard/reports/getReport">
                                <ul class="financial-accounting-first__create-first-column">
                                    <li>
                                        <select class="financial-accounting-first__create-select" name="report_type" id="report_type">
                                            <option value="product" <?= ($reportType ?? 'product') === 'product' ? 'selected' : '' ?>>Отчет по продуктам</option>
                                            <option value="service" <?= ($reportType ?? 'product') === 'service' ? 'selected' : '' ?>>Отчет по услугам</option>
                                        </select>
                                    </li>
                                    <li>
                                        <input type="date" name="start_date" value="<?= htmlspecialchars($selectedStartDate !== '' ? $selectedStartDate : date('Y-m-d')) ?>" placeholder="Дата начала">
                                    </li>
                                </ul>
                                <ul class="financial-accounting-first__create-second-column">
                                    <li id="product_select_container" style="display: <?= ($reportType ?? 'product') === 'product' ? 'block' : 'none' ?>;">
                                        <select class="financial-accounting-first__create-select" name="product_id">
                                            <option value="">Все продукты</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= htmlspecialchars($product['id']) ?>" <?= ($selectedId ?? '') === $product['id'] && ($reportType ?? 'product') === 'product' ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </li>
                                    <li id="service_select_container" style="display: <?= ($reportType ?? 'product') === 'service' ? 'block' : 'none' ?>;">
                                        <select class="financial-accounting-first__create-select" name="service_id">
                                            <option value="">Все услуги</option>
                                            <?php if (empty($services)): ?>
                                                <option value="" disabled>Нет доступных услуг</option>
                                            <?php else: ?>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?= htmlspecialchars($service['id']) ?>" <?= ($selectedId ?? '') === $service['id'] && ($reportType ?? 'product') === 'service' ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($service['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </li>
                                    <li>
                                        <input type="date" name="end_date" value="<?= htmlspecialchars($selectedEndDate ?? '') ?>" placeholder="Дата конца">
                                    </li>
                                </ul>
                                <div class="financial-accounting-first__buttons">
                                    <button type="submit" class="financial-accounting-first__button-save">Сформировать</button>
                                    <button type="button" class="financial-accounting-first__button-clear" onclick="clearForm()">Очистить</button>
                                </div>
                            </form>
                            <?php if (!empty($reports)): ?>
                                <form action="/admin/dashboard/reports/export" method="POST" id="exportForm" style="margin-top:1em;">
                                    <input type="hidden" name="report_type" value="<?= htmlspecialchars($reportType ?? 'product') ?>">
                                    <input type="hidden" name="<?= ($reportType ?? 'product') === 'service' ? 'service_id' : 'product_id' ?>" value="<?= htmlspecialchars($selectedId ?? '') ?>">
                                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($selectedStartDate ?? '') ?>">
                                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($selectedEndDate ?? '') ?>">
                                    <button type="submit" class="financial-accounting-first__button-export">
                                        Экспортировать в Excel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="financial-accounting-first__list-container">
                        <label class="financial-accounting-first__list-label">Создан отчет</label>
                        <div class="financial-accounting-first__list">
                            <table id="reportTable">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div id="reportSums" style="font-weight: 500; display: flex; justify-content: space-between;" >
                                        <?php if (!empty($reports) && isset($totals)): ?>
                                            <div style="margin-right: 20px;">
                                                <strong>Всего наличными:</strong> <?= number_format($totals['total_cash'], 2) ?> ₽
                                            </div>
                                            <div style="margin-right: 20px;">
                                                <strong>Всего картой:</strong> <?= number_format($totals['total_card'], 2) ?> ₽
                                            </div>
                                            <div>
                                                <strong>Общая сумма:</strong> <?= number_format($totals['total_amount'], 2) ?> ₽
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div id="reportControls" style="margin-left: 24px;">
                                            <input type="text" id="reportSearchInput" placeholder="Поиск по отчету..." style="padding: 8px 12px; width: 300px; border: 1px solid var(--dark-hover); border-radius: 8px; background-color: var(--dark-bg); color: var(--white);">
                                    </div>
                                </div>
                                
                                
                                <thead>
                                    <tr>
                                        <?php if (($reportType ?? 'product') === 'service'): ?>
                                            <th>Марка машины</th>
                                            <th>Номер</th>
                                            <th>Наименование услуги</th> <!-- Заменяем на "Наименование услуги" -->
                                            <th>Класс автомобиля</th> <!-- Добавляем новый столбец -->
                                            <th>Дата время</th>
                                            <th>Тип оплаты</th>
                                            <th>Сумма нал.</th>
                                            <th>Сумма безнал.</th>
                                            <th>Наценка</th>
                                            <th>Сумма</th>
                                        <?php else: ?>
                                            <th>Наименование</th>
                                            <th>Кол-во</th>
                                            <th>Цена</th>
                                            <th>Сумма нал.</th>
                                            <th>Сумма безнал.</th>
                                            <th>Сумма</th>
                                            <th>Сотрудник</th>
                                            <th>Дата</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="reportBody">
                                                                            <?php if (empty($reports)): ?>
                                            <tr>
                                                <td colspan="<?= ($reportType ?? 'product') === 'service' ? 10 : 8 ?>">Выберите параметры и нажмите "Сформировать" для отображения отчета</td>
                                            </tr>
                                    <?php else: ?>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <?php if (($reportType ?? 'product') === 'service'): ?>
                                                    <td><?= htmlspecialchars($report['car_brand'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['car_number'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['car_class'] ?? '') ?></td> <!-- Новый столбец с классом -->
                                                    <td><?= htmlspecialchars($report['sale_date'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['payment_method'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['cash'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['card'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['markup'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['total'] ?? '') ?></td>
                                                <?php else: ?>
                                                    <td><?= htmlspecialchars($report['product_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['quantity'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['price'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['cash'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['card'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['total'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['employee_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($report['sale_date'] ?? '') ?></td>
                                                <?php endif; ?>
                                                <?php


                                                
                                                ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="reportsContainer" style="display: none;">
                <div class="reports-tab-container">
                    <label class="financial-accounting-first-label">Фильтрация отчетов</label>
                    <div class="reports-container" id="createReport">
                        <div class="financial-accounting-first__create">
                            <form class="financial-accounting-first__create-forms">
                                <ul class="financial-accounting-first__create-first-column">
                                    <li>
                                        <select class="financial-accounting-first__create-select">
                                            <option disabled selected>Все отчеты</option>
                                        </select>
                                    </li>
                                    <li>
                                        <input type="date" placeholder="Дата начала">
                                    </li>
                                </ul>
                                <ul class="financial-accounting-first__create-second-column">
                                    <li>
                                        <select class="financial-accounting-first__create-select">
                                            <option disabled selected>Все</option>
                                        </select>
                                    </li>
                                    <li><input type="date" placeholder="Дата конца"></li>
                                </ul>
                            </form>
                            <div class="financial-accounting-first__buttons">
                                <button class="financial-accounting-first__button-save">Сохранить</button>
                                <button class="financial-accounting-first__button-clear">Очистить</button>
                            </div>
                        </div>
                    </div>
                    <div class="financial-accounting-first__list-container">
                        <label class="financial-accounting-first__list-label">Создан отчет</label>
                        <div class="report-search-results">
                            <div class="report">
                                <div class="report-header">
                                    <div class="report-title">Отчет №12</div>
                                    <span class="report-date">Создан: 13.09.2024</span>
                                </div>
                                <div class="report-body">
                                    <div class="report-type"><span class="report-type-label">Тип:</span> Отчет по сотрудникам</div>
                                    <div class="report-selection"><span class="report-selection-label">Выборка:</span> Все сотрудники</div>
                                    <div class="report-dates">
                                        <span class="report-period">13.07.2024 / 28.09.2024</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="last-actionsContainer" style="display: none;">
                <div class="financial-accounting-first__list">
                    <table id="lastActionsTable">
                        <thead>
                            <tr>
                                <th>ID Сотрудника</th>
                                <th>Тип действия</th>
                                <th>Информация</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody id="lastActionsBody">
                            <!-- JS вставит сюда <tr>...<tr/> -->
                        </tbody>
                    </table>
                    <div id="lastActionsLoader" style="display: none;">Загрузка последних действий…</div>
                    <div id="lastActionsError" style="color: red; display: none;"></div>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    function formatCurrency(value) {
        return Number(value).toFixed(2) + ' ₽';
    }

    function calculateReportSums() {
        // Проверяем, есть ли уже данные из PHP
        const existingSums = document.querySelector('#reportSums div');
        console.log('Existing sums element:', existingSums);
        console.log('Existing sums text:', existingSums?.textContent);
        
        if (existingSums && existingSums.textContent.includes('Всего наличными:')) {
            console.log('Data from PHP found, skipping JavaScript calculation');
            return; // Данные уже есть из PHP, не перезаписываем
        }

        console.log('No PHP data found, calculating with JavaScript...');

        const rows = document.querySelectorAll('#reportTable tbody tr');
        let cashTotal = 0, cardTotal = 0, grandTotal = 0;

        const headers = Array.from(document.querySelectorAll('#reportTable thead th')).map(h => h.textContent.trim());
        const isServiceReport = headers.includes('Марка машины');

        rows.forEach(row => {
            if (row.style.display === 'none') return; // пропускаем скрытые строки

            const cells = row.querySelectorAll('td');
            if (!cells.length || cells[0].textContent.includes('Выберите параметры')) return;

            let cash = 0, card = 0, total = 0;

            try {
                if (isServiceReport) {
                    cash = parseFloat(cells[6]?.innerText.replace(',', '.')) || 0;
                    card = parseFloat(cells[7]?.innerText.replace(',', '.')) || 0;
                    total = parseFloat(cells[8]?.innerText.replace(',', '.')) || 0;
                } else {
                    cash = parseFloat(cells[3]?.innerText.replace(',', '.')) || 0;
                    card = parseFloat(cells[4]?.innerText.replace(',', '.')) || 0;
                    total = parseFloat(cells[5]?.innerText.replace(',', '.')) || 0;
                }
            } catch {}

            cashTotal += cash;
            cardTotal += card;
            grandTotal += total;
        });

        console.log('JavaScript calculated totals:', { cashTotal, cardTotal, grandTotal });

        const reportSums = document.getElementById('reportSums');
        reportSums.innerHTML = `
            <div style="margin-left: 12px;">Всего наличными: ${formatCurrency(cashTotal)}</div>
            <div style="margin-left: 12px;">Всего картой: ${formatCurrency(cardTotal)}</div>
            <div style="margin-left: 12px;">Общая сумма: ${formatCurrency(grandTotal)}</div>
        `;
    }

    document.addEventListener('DOMContentLoaded', calculateReportSums);
</script>

<script>
    function filterReportRows() {
        const input = document.getElementById('reportSearchInput');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('#reportTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });

        // Пересчитываем суммы только по видимым строкам, если нет данных из PHP
        const existingSums = document.querySelector('#reportSums div');
        if (!existingSums || !existingSums.textContent.includes('Всего наличными:')) {
            calculateReportSums();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        calculateReportSums();
        const searchInput = document.getElementById('reportSearchInput');
        searchInput.addEventListener('input', filterReportRows);
    });
</script>


<script>
    // Обработка табов
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.getElementById(tabName + 'Container').style.display = 'block';
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const tab = document.getElementById('lastActionsTab');
        const tbody = document.getElementById('lastActionsBody');
        const loader = document.getElementById('lastActionsLoader');
        const errorBox = document.getElementById('lastActionsError');

        tab.addEventListener('click', async () => {
            switchTab('last-actions');

            if (tbody.children.length > 0) return;

            loader.style.display = 'block';
            errorBox.style.display = 'none';

            try {
                const response = await fetch('/admin/dashboard/reports/get-last-actions');
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const actions = await response.json();
                loader.style.display = 'none';

                if (!actions.length) {
                    tbody.innerHTML = '<tr><td colspan="4">Нет записей</td></tr>';
                    return;
                }

                const rows = actions.map(act => {
                    const employee = act.actor_name || act.actor
                    const type = act.action_name;

                    let infoList = '';
                    try {
                        const infoObj = typeof act.action_info === 'string' ?
                            JSON.parse(act.action_info) :
                            act.action_info;
                        infoList = Object.entries(infoObj)
                            .map(([key, val]) => `<li><strong>${key}:</strong> ${val}</li>`)
                            .join('');
                    } catch {
                        infoList = `<li>${act.action_info}</li>`;
                    }

                    const infoCell = `
          <details>
            <summary>Показать детали</summary>
            <ul>${infoList}</ul>
          </details>`;

                    const date = new Date(act.action_date).toLocaleString();

                    return `
          <tr>
            <td>${employee}</td>
            <td>${type}</td>
            <td>${infoCell}</td>
            <td>${date}</td>
          </tr>`;
                }).join('');

                tbody.innerHTML = rows;
            } catch (err) {
                loader.style.display = 'none';
                errorBox.textContent = 'Ошибка загрузки: ' + err.message;
                errorBox.style.display = 'block';
            }
        });
    });
</script>

<?php $render->component('dashboard_footer'); ?>