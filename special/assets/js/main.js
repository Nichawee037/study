/**
 * ระบบขอเปิดกลุ่มเรียนกรณีพิเศษ — Client-side Script
 */

'use strict';

// ─── 1. ระบบตรวจสอบความถูกต้องของฟอร์ม (Form Validation) ──────────────────
(function () {
    const inputForms = document.querySelectorAll('form.needs-validation');

    inputForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // ตรวจสอบและแสดงสถานะเมื่อหลุดโฟกัส (blur event)
        form.querySelectorAll('input, select, textarea').forEach(function (inputElem) {
            inputElem.addEventListener('blur', function () {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    });
})();

// ─── 2. ระบบนับจำนวนตัวอักษรสำหรับเหตุผลความจำเป็น ───────────────────────
(function () {
    const reasonTextarea = document.getElementById('request_reason');
    const charCounter    = document.getElementById('char-count');
    if (!reasonTextarea || !charCounter) return;

    const MIN_REQUIRED_CHARACTERS = 20;

    function refreshCount() {
        const textLength = reasonTextarea.value.length;
        charCounter.textContent = textLength;
        charCounter.style.color = textLength < MIN_REQUIRED_CHARACTERS ? '#ef4444' : '#10b981';
    }

    reasonTextarea.addEventListener('input', refreshCount);
    refreshCount();
})();

// ─── 3. ระบบปิดกล่องข้อความแจ้งเตือนอัตโนมัติ (Auto-dismiss Alerts) ──────────
(function () {
    document.querySelectorAll('.alert-dismissible[data-auto-dismiss]').forEach(function (alertElem) {
        const dismissDelay = parseInt(alertElem.dataset.autoDismiss, 10) || 4000;
        setTimeout(function () {
            const instance = bootstrap.Alert.getOrCreateInstance(alertElem);
            if (instance) {
                instance.close();
            }
        }, dismissDelay);
    });
})();