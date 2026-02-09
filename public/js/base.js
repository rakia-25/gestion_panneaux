/**
 * Scripts globaux : sidebar, scroll erreur formulaire, validation champs (number, tel, gps)
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar sur mobile
    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // Scroll vers la première erreur de formulaire
    var firstError = document.querySelector('.form-group-modern.has-error, .form-check-wrapper.has-error');
    if (firstError) {
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Champs number : empêcher saisie non numérique, valider paste
    var numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function(input) {
        var pattern = input.getAttribute('pattern');
        var step = input.getAttribute('step');
        var allowDecimal = step && parseFloat(step) < 1;

        input.addEventListener('keypress', function(e) {
            if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            if (allowDecimal && (e.keyCode === 190 || e.keyCode === 110 || e.keyCode === 188)) {
                if (this.value.indexOf('.') !== -1 || this.value.indexOf(',') !== -1) {
                    e.preventDefault();
                }
                return;
            }
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });

        input.addEventListener('paste', function(e) {
            var paste = (e.clipboardData || window.clipboardData).getData('text');
            if (allowDecimal) {
                if (!/^\d+(\.\d+)?$/.test(paste.replace(',', '.'))) {
                    e.preventDefault();
                }
            } else {
                if (!/^\d+$/.test(paste)) {
                    e.preventDefault();
                }
            }
        });

        input.addEventListener('input', function(e) {
            if (allowDecimal) {
                this.value = this.value.replace(/[^0-9.,]/g, '').replace(',', '.');
                var parts = this.value.split('.');
                if (parts.length > 2) {
                    this.value = parts[0] + '.' + parts.slice(1).join('');
                }
            } else {
                this.value = this.value.replace(/[^0-9]/g, '');
            }
        });
    });

    // Champs téléphone
    var telInputs = document.querySelectorAll('input[inputmode="tel"]');
    telInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+\s\-\(\)]/g, '');
        });
    });

    // Champs GPS (latitude,longitude)
    var gpsInputs = document.querySelectorAll('input[data-format="gps"]');
    var gpsPattern = /^-?([0-8]?[0-9](\.[0-9]{1,6})?|90(\.0{1,6})?),-?([0-1]?[0-7]?[0-9](\.[0-9]{1,6})?|180(\.0{1,6})?)$/;
    gpsInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            var value = this.value.trim();
            var formGroup = this.closest('.mb-3');
            if (formGroup) {
                var helpMsg = formGroup.querySelector('.gps-validation-msg');
                if (!helpMsg) {
                    helpMsg = document.createElement('div');
                    helpMsg.className = 'gps-validation-msg form-text';
                    formGroup.appendChild(helpMsg);
                }
                if (value === '') {
                    helpMsg.textContent = '';
                    helpMsg.className = 'gps-validation-msg form-text';
                } else if (gpsPattern.test(value)) {
                    helpMsg.textContent = '✓ Format valide';
                    helpMsg.className = 'gps-validation-msg form-text text-success';
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    helpMsg.textContent = 'Format attendu: latitude,longitude (ex: 13.5123,2.1098)';
                    helpMsg.className = 'gps-validation-msg form-text text-danger';
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            }
        });

        input.addEventListener('blur', function(e) {
            var value = this.value.trim();
            if (value && !gpsPattern.test(value)) {
                this.classList.add('is-invalid');
            }
        });

        input.addEventListener('keypress', function(e) {
            var allowedChars = /[0-9.,\-]/;
            var key = String.fromCharCode(e.keyCode || e.which);
            if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            if (!allowedChars.test(key)) {
                e.preventDefault();
            }
        });
    });
});
