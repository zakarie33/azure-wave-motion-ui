/**
 * Notary Management System
 * Main JavaScript File
 */

// Global App Object
const NotaryApp = {
    init() {
        this.bindEvents();
        this.initializeComponents();
    },

    bindEvents() {
        // Global form validation
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeValidation();
            this.initializeTooltips();
            this.initializeModals();
            this.initializeFileUploads();
        });

        // Sidebar toggle for mobile
        const sidebarToggle = document.querySelector('[data-bs-toggle="sidebar"]');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar);
        }

        // Auto-hide alerts
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
            setTimeout(() => {
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 150);
            }, parseInt(alert.dataset.autoDismiss) || 5000);
        });
    },

    initializeComponents() {
        // Initialize Bootstrap tooltips
        this.initializeTooltips();
        
        // Initialize date pickers
        this.initializeDatePickers();
        
        // Initialize search functionality
        this.initializeSearch();
        
        // Initialize table sorting
        this.initializeTableSorting();
    },

    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    initializeModals() {
        // Initialize Bootstrap modals
        const modalTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="modal"]'));
        modalTriggerList.map(modalTriggerEl => {
            return new bootstrap.Modal(modalTriggerEl);
        });
    },

    initializeValidation() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Real-time validation
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    },

    validateField(field) {
        const isValid = field.checkValidity();
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);
        
        if (feedback) {
            feedback.style.display = isValid ? 'none' : 'block';
        }
    },

    initializeDatePickers() {
        // Simple date picker initialization
        document.querySelectorAll('input[type="date"]').forEach(input => {
            // Set minimum date to today for appointment dates
            if (input.classList.contains('appointment-date')) {
                input.min = new Date().toISOString().split('T')[0];
            }
        });
    },

    initializeFileUploads() {
        document.querySelectorAll('.file-upload-area').forEach(area => {
            const input = area.querySelector('input[type="file"]');
            
            if (!input) return;

            // Drag and drop events
            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('dragover');
            });

            area.addEventListener('dragleave', () => {
                area.classList.remove('dragover');
            });

            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    input.files = files;
                    this.handleFileUpload(input, files[0]);
                }
            });

            // Click to upload
            area.addEventListener('click', () => {
                input.click();
            });

            // File input change
            input.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.handleFileUpload(input, e.target.files[0]);
                }
            });
        });
    },

    handleFileUpload(input, file) {
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (!allowedTypes.includes(file.type)) {
            this.showAlert('Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG files.', 'danger');
            input.value = '';
            return;
        }

        if (file.size > maxSize) {
            this.showAlert('File size too large. Maximum allowed size is 10MB.', 'danger');
            input.value = '';
            return;
        }

        // Show file info
        const fileInfo = input.parentNode.querySelector('.file-info');
        if (fileInfo) {
            fileInfo.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-file me-2"></i>
                    <span>${file.name}</span>
                    <small class="text-muted ms-2">(${this.formatFileSize(file.size)})</small>
                </div>
            `;
        }
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    initializeSearch() {
        document.querySelectorAll('.search-input').forEach(input => {
            let timeout;
            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.performSearch(e.target.value, e.target.dataset.target);
                }, 300);
            });
        });
    },

    performSearch(query, target) {
        const table = document.querySelector(target);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    },

    initializeTableSorting() {
        document.querySelectorAll('.sortable th').forEach(header => {
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });
    },

    sortTable(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = !header.classList.contains('asc');

        // Sort rows
        rows.sort((a, b) => {
            const aText = a.children[columnIndex].textContent.trim();
            const bText = b.children[columnIndex].textContent.trim();
            
            // Try to parse as numbers first
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // Try to parse as dates
            const aDate = new Date(aText);
            const bDate = new Date(bText);
            
            if (!isNaN(aDate) && !isNaN(bDate)) {
                return isAscending ? aDate - bDate : bDate - aDate;
            }
            
            // Default to string comparison
            return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
        });

        // Update header classes
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('asc', 'desc');
        });
        
        header.classList.add(isAscending ? 'asc' : 'desc');

        // Reorder rows in DOM
        rows.forEach(row => tbody.appendChild(row));
    },

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    },

    showAlert(message, type = 'info') {
        const alertContainer = document.querySelector('.alert-container') || document.body;
        const alertId = 'alert-' + Date.now();
        
        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, 5000);
    },

    // AJAX Helper Functions
    ajax(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaults, ...options };
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                this.showAlert('Network error: ' + error.message, 'danger');
                throw error;
            });
    },

    // Utility Functions
    formatDate(date, format = 'M j, Y') {
        const d = new Date(date);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        switch(format) {
            case 'M j, Y':
                return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
            case 'Y-m-d':
                return d.toISOString().split('T')[0];
            default:
                return d.toLocaleDateString();
        }
    },

    formatTime(time) {
        return new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Form handling
    serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },

    // Local storage helpers
    storage: {
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (error) {
                console.warn('LocalStorage not available:', error);
            }
        },

        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (error) {
                console.warn('LocalStorage not available:', error);
                return defaultValue;
            }
        },

        remove(key) {
            try {
                localStorage.removeItem(key);
            } catch (error) {
                console.warn('LocalStorage not available:', error);
            }
        }
    }
};

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    NotaryApp.init();
});

// Make NotaryApp globally available
window.NotaryApp = NotaryApp;