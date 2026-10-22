/**
 * Portal Informasi Peserta — Client-Side Scripts
 * Validasi form, interaksi card, dan UX enhancements
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─── Form Validation ─────────────────────────────
    const form = document.getElementById('registerForm');
    if (form) {
        initFormValidation(form);
    }

    // ─── Info Cards Accordion ────────────────────────
    const cards = document.querySelectorAll('.info-card-header');
    if (cards.length) {
        initAccordion(cards);
    }
});

/**
 * Inisialisasi validasi formulir registrasi.
 */
function initFormValidation(form) {
    const fields = {
        nama: form.querySelector('#nama'),
        departemen: form.querySelector('#departemen'),
        no_hp: form.querySelector('#no_hp'),
        consent: form.querySelector('#consent'),
    };

    const submitBtn = form.querySelector('#submitBtn');

    // Validasi real-time pada blur
    if (fields.nama) {
        fields.nama.addEventListener('blur', function () {
            validateField(this, validateNama);
        });
        fields.nama.addEventListener('input', function () {
            clearFieldError(this);
        });
    }

    if (fields.departemen) {
        fields.departemen.addEventListener('change', function () {
            validateField(this, validateDepartemen);
        });
    }

    if (fields.no_hp) {
        fields.no_hp.addEventListener('blur', function () {
            validateField(this, validatePhone);
        });
        fields.no_hp.addEventListener('input', function () {
            clearFieldError(this);
            // Hanya izinkan angka, +, -, spasi
            this.value = this.value.replace(/[^0-9+\-\s()]/g, '');
        });
    }

    // Submit handler
    form.addEventListener('submit', function (e) {
        let isValid = true;

        if (!validateField(fields.nama, validateNama)) isValid = false;
        if (!validateField(fields.departemen, validateDepartemen)) isValid = false;
        if (!validateField(fields.no_hp, validatePhone)) isValid = false;

        if (fields.consent && !fields.consent.checked) {
            showFieldError(fields.consent, 'Anda harus menyetujui pemrosesan data.');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll ke error pertama
            const firstError = form.querySelector('.is-error, .field-error.show');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Loading state
        if (submitBtn) {
            submitBtn.classList.add('is-loading');
            submitBtn.disabled = true;
        }
    });
}

/**
 * Validasi satu field dan tampilkan error jika perlu.
 */
function validateField(field, validator) {
    if (!field) return true;
    const result = validator(field.value);
    if (result !== true) {
        showFieldError(field, result);
        return false;
    }
    clearFieldError(field);
    return true;
}

function validateNama(value) {
    value = value.trim();
    if (!value) return 'Nama lengkap wajib diisi.';
    if (value.length < 3) return 'Nama minimal 3 karakter.';
    if (value.length > 150) return 'Nama maksimal 150 karakter.';
    if (!/^[a-zA-Z\s'.,-]+$/.test(value)) return 'Nama hanya boleh mengandung huruf dan spasi.';
    return true;
}

function validateDepartemen(value) {
    if (!value || value === '') return 'Silakan pilih departemen.';
    return true;
}

function validatePhone(value) {
    value = value.replace(/[\s\-\.\(\)]/g, '');
    if (!value) return 'Nomor HP wajib diisi.';

    // User memasukkan tanpa 0 di depan (prefix +62 sudah ditampilkan)
    // Tambahkan 0 di depan untuk validasi
    var normalized = value;
    if (value.startsWith('+62')) {
        normalized = '0' + value.substring(3);
    } else if (value.startsWith('62') && value.length > 10) {
        normalized = '0' + value.substring(2);
    } else if (!value.startsWith('0')) {
        normalized = '0' + value;
    }

    if (!/^0\d{9,13}$/.test(normalized)) {
        return 'Nomor HP tidak valid. Masukkan 9–13 digit setelah +62.';
    }
    return true;
}

function showFieldError(field, message) {
    field.classList.add('is-error');
    const errorEl = field.closest('.form-group')?.querySelector('.field-error');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
}

function clearFieldError(field) {
    field.classList.remove('is-error');
    const errorEl = field.closest('.form-group')?.querySelector('.field-error');
    if (errorEl) {
        errorEl.classList.remove('show');
    }
}

/**
 * Inisialisasi accordion untuk info cards.
 */
function initAccordion(headers) {
    // Buka card pertama secara default
    const firstCard = headers[0]?.closest('.info-card');
    if (firstCard) {
        firstCard.classList.add('is-open');
        headers[0].setAttribute('aria-expanded', 'true');
    }

    headers.forEach(function (header) {
        header.addEventListener('click', function () {
            const card = this.closest('.info-card');
            const isOpen = card.classList.contains('is-open');

            // Close all
            document.querySelectorAll('.info-card.is-open').forEach(function (c) {
                c.classList.remove('is-open');
                c.querySelector('.info-card-header')?.setAttribute('aria-expanded', 'false');
            });

            // Toggle current
            if (!isOpen) {
                card.classList.add('is-open');
                this.setAttribute('aria-expanded', 'true');
                // Scroll into view if needed
                setTimeout(function () {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        });
    });
}
