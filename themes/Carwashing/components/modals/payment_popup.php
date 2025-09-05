<div class="modal-popup" id="paymentPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999;">
    <div class="modal-popup-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1E2129; padding: 20px; border-radius: 12px; max-width: 400px; width: 100%;">
        <div class="modal-popup-header">
            <h2 id="paymentPopupTitle" style="color: #fff; margin: 0; font-size: 20px;">Подтверждение</h2>
            <span class="car-class-close-button" onclick="closePaymentPopup()" style="position: absolute; right: 20px; top: 20px; cursor: pointer; color: #fff; font-size: 24px;">×</span>
        </div>
        <div class="modal-popup-body">
            <div id="paymentPopupMessage" style="margin: 20px 0; font-size: 16px; color: #fff; text-align: center;">
                Оплата прошла?
            </div>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 20px;">
                <button id="paymentPopupOkBtn" class="payment-popup-button" style="width: 200px; background-color: #707FDD; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">Оплачено</button>
                <button id="paymentPopupCancelBtn" class="payment-popup-button" style="width: 200px; background-color: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">Отклонено</button>
            </div>
        </div>
    </div>
</div>
<script>
function openPaymentPopup({title, message, onOk, onCancel}) {
    document.getElementById('paymentPopupTitle').innerText = title || 'Подтверждение';
    document.getElementById('paymentPopupMessage').innerText = message || 'Оплата прошла?';
    document.getElementById('paymentPopup').style.display = 'block';
    
    document.getElementById('paymentPopupOkBtn').onclick = function() {
        closePaymentPopup();
        if (typeof onOk === 'function') {
            // Обновляем статус на completed
            document.querySelector('.payment-status').classList.remove('pending');
            document.querySelector('.payment-status').classList.add('completed');
            document.querySelector('.payment-status').innerText = 'Оплачено';
            onOk();
        }
    };
    
    document.getElementById('paymentPopupCancelBtn').onclick = function() {
        closePaymentPopup();
        if (typeof onCancel === 'function') {
            // Обновляем статус на canceled
            document.querySelector('.payment-status').classList.remove('pending');
            document.querySelector('.payment-status').classList.add('rejected');
            document.querySelector('.payment-status').innerText = 'Отклонено';
            onCancel();
        }
    };

    // Закрытие по клику на фон
    document.getElementById('paymentPopup').onclick = function(e) {
        if (e.target === this) {
            closePaymentPopup();
            if (typeof onCancel === 'function') onCancel();
        }
    };

    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentPopup();
            if (typeof onCancel === 'function') onCancel();
        }
    });
}

function closePaymentPopup() {
    document.getElementById('paymentPopup').style.display = 'none';
    // Удаляем поле для причины отклонения, если оно было добавлено
    const reasonInput = document.getElementById('paymentPopupInputReason');
    if (reasonInput) reasonInput.remove();
}
</script>
