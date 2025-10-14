/**
 * Court Management System - Main JavaScript File
 */

// Global application object
window.CourtSystem = {
    config: {
        baseUrl: '/court-system',
        apiUrl: '/court-system/api',
        maxFileSize: 20 * 1024 * 1024, // 20MB
        allowedFileTypes: ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png']
    },
    
    // Initialize the application
    init: function() {
        this.initEventListeners();
        this.initTooltips();
        this.initPopovers();
        this.initFormValidation();
        this.initFileUpload();
        this.initNotifications();
        this.initDataTables();
    },
    
    // Initialize event listeners
    initEventListeners: function() {
        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });
        
        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('confirm-delete') || 
                e.target.closest('.confirm-delete')) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Loading states for forms
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('loading-form')) {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                if (submitBtn) {
                    CourtSystem.showLoading(submitBtn);
                }
            }
        });
        
        // Auto-refresh notifications
        setInterval(function() {
            CourtSystem.refreshNotifications();
        }, 30000); // Every 30 seconds
    },
    
    // Initialize Bootstrap tooltips
    initTooltips: function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },
    
    // Initialize Bootstrap popovers
    initPopovers: function() {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    },
    
    // Initialize form validation
    initFormValidation: function() {
        // Bootstrap validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    },
    
    // Initialize file upload functionality
    initFileUpload: function() {
        // File input change handler
        document.addEventListener('change', function(e) {
            if (e.target.type === 'file') {
                CourtSystem.handleFileSelect(e.target);
            }
        });
        
        // Drag and drop functionality
        const dropZones = document.querySelectorAll('.file-upload-area');
        dropZones.forEach(function(dropZone) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });
            
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = dropZone.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.files = files;
                        CourtSystem.handleFileSelect(fileInput);
                    }
                }
            });
        });
    },
    
    // Handle file selection
    handleFileSelect: function(input) {
        const files = input.files;
        if (files.length === 0) return;
        
        const file = files[0];
        
        // Validate file size
        if (file.size > this.config.maxFileSize) {
            this.showAlert('File size exceeds maximum allowed size of ' + this.formatFileSize(this.config.maxFileSize), 'danger');
            input.value = '';
            return;
        }
        
        // Validate file type
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedFileTypes.includes(extension)) {
            this.showAlert('File type not allowed. Allowed types: ' + this.config.allowedFileTypes.join(', '), 'danger');
            input.value = '';
            return;
        }
        
        // Show file preview
        const previewId = input.getAttribute('data-preview');
        if (previewId) {
            this.previewFile(file, previewId);
        }
        
        // Auto-populate title if empty
        const titleField = document.getElementById('title');
        if (titleField && !titleField.value) {
            const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
            titleField.value = nameWithoutExt.replace(/[_-]/g, ' ');
        }
    },
    
    // Preview uploaded file
    previewFile: function(file, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return;
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-file-earmark"></i> 
                    ${file.name} (${CourtSystem.formatFileSize(file.size)})
                </div>
            `;
        }
    },
    
    // Initialize notifications
    initNotifications: function() {
        // Mark notifications as read when clicked
        document.addEventListener('click', function(e) {
            if (e.target.closest('.notification-item')) {
                const notificationId = e.target.closest('.notification-item').dataset.notificationId;
                if (notificationId) {
                    CourtSystem.markNotificationRead(notificationId);
                }
            }
        });
    },
    
    // Initialize DataTables
    initDataTables: function() {
        // Auto-initialize tables with .data-table class
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(function(table) {
            if (typeof DataTable !== 'undefined') {
                new DataTable(table, {
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });
            }
        });
    },
    
    // Utility functions
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Show loading state on button
    showLoading: function(button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        button.disabled = true;
        
        // Store original text for restoration
        button.dataset.originalText = originalText;
        
        // Re-enable after 10 seconds as fallback
        setTimeout(function() {
            CourtSystem.hideLoading(button);
        }, 10000);
    },
    
    // Hide loading state
    hideLoading: function(button) {
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            button.disabled = false;
            delete button.dataset.originalText;
        }
    },
    
    // Show alert message
    showAlert: function(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container') || document.body;
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    },
    
    // Refresh notification count
    refreshNotifications: function() {
        fetch(this.config.apiUrl + '/notifications.php?unread_count=1')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                    } else {
                        const bellIcon = document.querySelector('#notificationDropdown');
                        if (bellIcon) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count > 9 ? '9+' : data.count;
                            bellIcon.appendChild(newBadge);
                        }
                    }
                } else if (badge) {
                    badge.remove();
                }
            })
            .catch(error => console.log('Error refreshing notifications:', error));
    },
    
    // Mark notification as read
    markNotificationRead: function(notificationId) {
        fetch(this.config.apiUrl + '/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_read&id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.refreshNotifications();
            }
        })
        .catch(error => console.log('Error marking notification as read:', error));
    },
    
    // AJAX form submission
    submitForm: function(form, callback) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            this.showLoading(submitBtn);
        }
        
        fetch(form.action || window.location.href, {
            method: form.method || 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (submitBtn) {
                this.hideLoading(submitBtn);
            }
            
            if (callback) {
                callback(data);
            }
        })
        .catch(error => {
            if (submitBtn) {
                this.hideLoading(submitBtn);
            }
            console.error('Form submission error:', error);
            this.showAlert('An error occurred while submitting the form.', 'danger');
        });
    },
    
    // Confirm dialog
    confirm: function(message, callback) {
        if (confirm(message)) {
            if (callback) callback();
            return true;
        }
        return false;
    },
    
    // Format date
    formatDate: function(dateString, format = 'MMM d, yyyy') {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid Date';
        
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        return date.toLocaleDateString('en-US', options);
    },
    
    // Format datetime
    formatDateTime: function(dateString, format = 'MMM d, yyyy h:mm a') {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid Date';
        
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        
        return date.toLocaleDateString('en-US', options);
    },
    
    // Time ago helper
    timeAgo: function(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
        if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + ' months ago';
        return Math.floor(diffInSeconds / 31536000) + ' years ago';
    },
    
    // Export functionality
    exportData: function(format, url, params = {}) {
        const queryString = new URLSearchParams(params);
        queryString.set('export', format);
        
        const exportUrl = url + '?' + queryString.toString();
        window.open(exportUrl, '_blank');
    },
    
    // Print functionality
    printElement: function(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="${this.config.baseUrl}/assets/css/style.css" rel="stylesheet">
                    <style>
                        @media print {
                            .no-print { display: none !important; }
                            body { padding: 20px; }
                        }
                    </style>
                </head>
                <body>
                    ${element.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
};

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    CourtSystem.init();
});

// Global helper functions for backward compatibility
function previewFile(input, previewId) {
    if (input.files && input.files[0]) {
        CourtSystem.previewFile(input.files[0], previewId);
    }
}

function formatFileSize(bytes) {
    return CourtSystem.formatFileSize(bytes);
}

function showLoading(button) {
    CourtSystem.showLoading(button);
}

function exportData(format, url, params) {
    CourtSystem.exportData(format, url, params);
}

// jQuery-like selector for convenience
function $(selector) {
    return document.querySelector(selector);
}

function $$(selector) {
    return document.querySelectorAll(selector);
}