/**
 * Pastimes - Pre-loved Brands. New Stories.
 * Main JavaScript File
 * WEDE6021 POE
 */

// ============================================================
// DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    initPasswordToggle();
    initPhotoUpload();
    initSizeButtons();
    initPriceRange();
    initQtyControls();
    initCartCheckboxes();
    initCharCounter();
    initStickyNav();
    initMobileMenu();
    initGalleryThumbs();
    initAddressSelect();
    initDropdowns();
    initFormValidation();
});

// ============================================================
// PASSWORD VISIBILITY TOGGLE
// ============================================================
function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.input-wrapper').querySelector('input');
            if (!input) return;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            // Toggle icon
            this.innerHTML = isText
                ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>`
                : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/></svg>`;
        });
    });
}

// ============================================================
// PHOTO UPLOAD ZONE
// ============================================================
function initPhotoUpload() {
    const uploadZone = document.getElementById('photo-upload-zone');
    const fileInput = document.getElementById('photo-input');
    const slotsContainer = document.querySelector('.photo-slots');

    if (!uploadZone || !fileInput) return;

    // Click to open file dialog
    uploadZone.addEventListener('click', () => fileInput.click());

    // Drag and drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });

    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const files = Array.from(e.dataTransfer.files).filter(f => f.type.match(/image\/(jpeg|jpg|png|webp)/));
        previewPhotos(files, slotsContainer);
    });

    fileInput.addEventListener('change', () => {
        const files = Array.from(fileInput.files);
        previewPhotos(files, slotsContainer);
    });

    // Individual slot clicks
    if (slotsContainer) {
        slotsContainer.querySelectorAll('.photo-slot').forEach(slot => {
            slot.addEventListener('click', () => fileInput.click());
        });
    }
}

function previewPhotos(files, container) {
    if (!container) return;
    const slots = container.querySelectorAll('.photo-slot');
    files.slice(0, slots.length).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            slots[i].innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    });
}

// ============================================================
// SIZE BUTTONS (Browse filter)
// ============================================================
function initSizeButtons() {
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            // Toggle within same group
            const group = this.closest('.size-grid');
            if (group) {
                group.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            }
            this.classList.toggle('active');
        });
    });
}

// ============================================================
// PRICE RANGE SLIDER
// ============================================================
function initPriceRange() {
    const slider = document.getElementById('price-range');
    const display = document.getElementById('price-display');
    if (!slider || !display) return;

    slider.addEventListener('input', function () {
        display.textContent = `R${Number(this.value).toLocaleString()}`;
    });
}

// ============================================================
// QUANTITY CONTROLS (Cart)
// ============================================================
function initQtyControls() {
    document.querySelectorAll('.qty-control').forEach(control => {
        const minusBtn = control.querySelector('.qty-minus');
        const plusBtn = control.querySelector('.qty-plus');
        const display = control.querySelector('.qty-num');
        const hiddenInput = control.querySelector('input[type="hidden"]');

        if (!display) return;
        let qty = parseInt(display.textContent) || 1;

        if (minusBtn) {
            minusBtn.addEventListener('click', () => {
                if (qty > 1) {
                    qty--;
                    display.textContent = qty;
                    if (hiddenInput) hiddenInput.value = qty;
                    updateCartTotal();
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', () => {
                qty++;
                display.textContent = qty;
                if (hiddenInput) hiddenInput.value = qty;
                updateCartTotal();
            });
        }
    });
}

// ============================================================
// CART TOTALS UPDATE
// ============================================================
function updateCartTotal() {
    // Handled server-side; JS for immediate feedback only
    const items = document.querySelectorAll('.cart-item');
    let subtotal = 0;

    items.forEach(item => {
        const priceEl = item.querySelector('.cart-item-price');
        const qtyEl = item.querySelector('.qty-num');
        const checkEl = item.querySelector('.cart-item-check');

        if (priceEl && qtyEl) {
            const isChecked = !checkEl || checkEl.checked;
            if (isChecked) {
                const price = parseFloat(priceEl.dataset.price || 0);
                const qty = parseInt(qtyEl.textContent) || 1;
                subtotal += price * qty;
            }
        }
    });

    const subtotalEl = document.querySelector('.subtotal-amount');
    if (subtotalEl) {
        subtotalEl.textContent = `R${subtotal.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
    }
}

// ============================================================
// CART CHECKBOXES (Select All)
// ============================================================
function initCartCheckboxes() {
    const selectAll = document.getElementById('select-all');
    const itemChecks = document.querySelectorAll('.cart-item-check');

    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        itemChecks.forEach(cb => {
            cb.checked = this.checked;
        });
        updateCartTotal();
    });

    itemChecks.forEach(cb => {
        cb.addEventListener('change', updateCartTotal);
    });
}

// ============================================================
// CHARACTER COUNTER (Description)
// ============================================================
function initCharCounter() {
    document.querySelectorAll('[data-maxlength]').forEach(el => {
        const max = parseInt(el.dataset.maxlength);
        const counterId = el.dataset.counter;
        const counter = counterId ? document.getElementById(counterId) : null;

        el.addEventListener('input', function () {
            const len = this.value.length;
            if (counter) counter.textContent = `${len}/${max}`;
            if (len > max) {
                this.value = this.value.substring(0, max);
                if (counter) counter.textContent = `${max}/${max}`;
            }
        });
    });
}

// ============================================================
// STICKY NAV SHADOW
// ============================================================
function initStickyNav() {
    const nav = document.querySelector('.navbar');
    if (!nav) return;
    window.addEventListener('scroll', () => {
        nav.style.boxShadow = window.scrollY > 10
            ? '0 4px 20px rgba(8,43,89,0.12)'
            : '0 2px 8px rgba(8,43,89,0.08)';
    });
}

// ============================================================
// MOBILE MENU TOGGLE
// ============================================================
function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (!hamburger || !navLinks) return;

    hamburger.addEventListener('click', () => {
        const isOpen = navLinks.style.display === 'flex';
        navLinks.style.display = isOpen ? '' : 'flex';
        navLinks.style.flexDirection = 'column';
        navLinks.style.position = 'absolute';
        navLinks.style.top = '100%';
        navLinks.style.left = '0';
        navLinks.style.right = '0';
        navLinks.style.background = '#fff';
        navLinks.style.padding = '16px';
        navLinks.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
        navLinks.style.zIndex = '999';
        if (isOpen) navLinks.removeAttribute('style');
    });
}

// ============================================================
// GALLERY THUMBNAIL SWITCHING (Item Page)
// ============================================================
function initGalleryThumbs() {
    const thumbs = document.querySelectorAll('.gallery-thumb');
    const mainImg = document.querySelector('.gallery-main img');
    if (!thumbs.length || !mainImg) return;

    thumbs.forEach(thumb => {
        thumb.addEventListener('click', function () {
            thumbs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const src = this.querySelector('img')?.src;
            if (src) {
                mainImg.style.opacity = '0';
                mainImg.style.transition = 'opacity 0.2s';
                setTimeout(() => {
                    mainImg.src = src;
                    mainImg.style.opacity = '1';
                }, 200);
            }
        });
    });
}

// ============================================================
// ADDRESS SELECTION (Checkout)
// ============================================================
function initAddressSelect() {
    document.querySelectorAll('.address-card').forEach(card => {
        card.addEventListener('click', function () {
            document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('.address-radio');
            if (radio) radio.checked = true;
        });
    });
}

// ============================================================
// DROPDOWN HOVER (Desktop)
// ============================================================
function initDropdowns() {
    // Already handled by CSS :hover, but add touch support
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) return;

        dropdown.addEventListener('touchstart', function (e) {
            const isOpen = menu.style.display === 'block';
            document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = '');
            if (!isOpen) {
                e.preventDefault();
                menu.style.display = 'block';
            }
        });
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = '');
        }
    });
}

// ============================================================
// FORM VALIDATION (Client-side enhancement)
// ============================================================
function initFormValidation() {
    // Password match validation — fires on BOTH fields so it stays in sync
    const confirmPass = document.getElementById('confirm_password');
    const password = document.getElementById('password');
    if (confirmPass && password) {
        function validatePasswordMatch() {
            // Find the feedback span inside the confirm-password form group
            const group = confirmPass.closest('.form-group') || confirmPass.parentNode.parentNode;
            let feedback = group ? group.querySelector('.invalid-feedback') : null;

            if (confirmPass.value === '') {
                confirmPass.classList.remove('is-invalid', 'is-valid');
                return;
            }
            if (password.value !== confirmPass.value) {
                confirmPass.classList.add('is-invalid');
                confirmPass.classList.remove('is-valid');
                if (feedback) feedback.textContent = 'Passwords do not match.';
            } else {
                confirmPass.classList.remove('is-invalid');
                confirmPass.classList.add('is-valid');
                if (feedback) feedback.textContent = '';
            }
        }
        confirmPass.addEventListener('input', validatePasswordMatch);
        password.addEventListener('input', validatePasswordMatch);
    }

    // Real-time input validation
    document.querySelectorAll('.form-control[required]').forEach(input => {
        input.addEventListener('blur', function () {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function () {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('is-invalid');
            }
        });
    }

    // Min length for password
    if (password) {
        password.addEventListener('input', function () {
            if (this.value.length > 0 && this.value.length < 8) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value.length >= 8) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }
}

// ============================================================
// ADMIN: CONFIRM DIALOGS
// ============================================================
function confirmAction(message) {
    return confirm(message);
}

function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
}

function confirmApprove(sellerName) {
    return confirm(`Approve seller account for ${sellerName}?`);
}

function confirmReject(sellerName) {
    return confirm(`Reject seller application for ${sellerName}?`);
}

// ============================================================
// ADD TO CART FEEDBACK
// ============================================================
function showAddedToCart(btn) {
    const original = btn.innerHTML;
    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg> Added!`;
    btn.style.background = '#2d9e5f';
    btn.style.borderColor = '#2d9e5f';
    setTimeout(() => {
        btn.innerHTML = original;
        btn.style.background = '';
        btn.style.borderColor = '';
    }, 1500);
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: ${type === 'success' ? '#2d9e5f' : type === 'danger' ? '#e74c3c' : '#082B59'};
        color: #fff; padding: 14px 22px; border-radius: 10px;
        font-family: 'Poppins', sans-serif; font-size: 0.88rem; font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transform: translateY(20px); opacity: 0;
        transition: all 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================================
// ADMIN SIDEBAR TOGGLE (Mobile)
// ============================================================
function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar) return;
    const isOpen = sidebar.style.transform === 'translateX(0px)';
    sidebar.style.transform = isOpen ? 'translateX(-100%)' : 'translateX(0px)';
    sidebar.style.transition = 'transform 0.3s ease';
}

// ============================================================
// SEARCH FORM (Redirect)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const searchForms = document.querySelectorAll('.search-form');
    searchForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const query = this.querySelector('input')?.value.trim();
            if (query) {
                window.location.href = `browse.php?search=${encodeURIComponent(query)}`;
            }
        });
    });
});
