                <?php if (is_logged_in()): ?>
                </div>
            </div>
        </div>
        <?php else: ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Load notifications if user is logged in
            <?php if (is_logged_in()): ?>
            loadNotifications();
            setInterval(loadNotifications, 30000); // Refresh every 30 seconds
            <?php endif; ?>
        });
        
        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // File upload validation
        function validateFileUpload(input, maxSize = <?php echo MAX_FILE_SIZE; ?>) {
            const file = input.files[0];
            if (!file) return true;
            
            const allowedTypes = <?php echo json_encode(ALLOWED_FILE_TYPES); ?>;
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExtension)) {
                alert('File type not allowed. Allowed types: ' + allowedTypes.join(', '));
                input.value = '';
                return false;
            }
            
            if (file.size > maxSize) {
                alert('File size too large. Maximum size: ' + (maxSize / 1024 / 1024) + 'MB');
                input.value = '';
                return false;
            }
            
            return true;
        }
        
        // Show loading spinner
        function showLoading(buttonId) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="loading-spinner"></span> Processing...';
            }
        }
        
        // Hide loading spinner
        function hideLoading(buttonId, originalText) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }
        
        // Load notifications
        function loadNotifications() {
            fetch('api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    const count = document.getElementById('notification-count');
                    const list = document.getElementById('notifications-list');
                    
                    if (count) {
                        count.textContent = data.unread_count || 0;
                        count.style.display = data.unread_count > 0 ? 'inline' : 'none';
                    }
                    
                    if (list) {
                        if (data.notifications && data.notifications.length > 0) {
                            list.innerHTML = data.notifications.map(notification => 
                                `<li><a class="dropdown-item ${!notification.is_read ? 'fw-bold' : ''}" href="notifications.php">
                                    <small class="text-muted">${notification.created_at}</small><br>
                                    ${notification.subject}
                                </a></li>`
                            ).join('');
                        } else {
                            list.innerHTML = '<li><span class="dropdown-item-text">No notifications</span></li>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }
        
        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Auto-save functionality for forms
        function enableAutoSave(formId, saveUrl) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    const formData = new FormData(form);
                    formData.append('auto_save', '1');
                    
                    fetch(saveUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Draft saved', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Auto-save error:', error);
                    });
                });
            });
        }
        
        // Show toast notification
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
            return container;
        }
        
        // Print functionality
        function printPage() {
            window.print();
        }
        
        // Export functionality
        function exportData(url, format = 'pdf') {
            const link = document.createElement('a');
            link.href = url + '?format=' + format;
            link.download = '';
            link.click();
        }
        
        // Search functionality with debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Initialize search with debounce
        function initializeSearch(inputId, resultsId, searchUrl) {
            const searchInput = document.getElementById(inputId);
            const resultsContainer = document.getElementById(resultsId);
            
            if (!searchInput || !resultsContainer) return;
            
            const performSearch = debounce(function(query) {
                if (query.length < 2) {
                    resultsContainer.innerHTML = '';
                    return;
                }
                
                fetch(searchUrl + '?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        resultsContainer.innerHTML = data.html || '<p>No results found</p>';
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        resultsContainer.innerHTML = '<p>Error performing search</p>';
                    });
            }, 300);
            
            searchInput.addEventListener('input', function() {
                performSearch(this.value);
            });
        }
        
        // Theme toggle functionality
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>
    
</body>
</html>