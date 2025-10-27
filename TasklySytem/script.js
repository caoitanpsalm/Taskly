class TasklyApp {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupMobileMenu();
        this.setupFormValidations();
        this.setupRealTimeUpdates();
        this.checkNotifications();
    }

    // Mobile menu functionality
    setupMobileMenu() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.createElement('div');
        
        overlay.className = 'mobile-overlay';
        document.body.appendChild(overlay);

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            });
        }

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });

        // Close sidebar when clicking on menu items on mobile
        if (window.innerWidth <= 768) {
            const menuItems = document.querySelectorAll('.nav-menu a');
            menuItems.forEach(item => {
                item.addEventListener('click', () => {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                });
            });
        }
    }

    // Form validation and enhancements
    setupFormValidations() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showToast('Please fill all required fields correctly', 'error');
                } else {
                    this.showLoading(form);
                }
            });

            // Real-time validation
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });

        // Due date validation - prevent past dates
        const dueDateInputs = document.querySelectorAll('input[type="date"]');
        dueDateInputs.forEach(input => {
            const today = new Date().toISOString().split('T')[0];
            input.min = today;
        });
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        // Clear previous errors
        this.clearFieldError(field);
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'This field is required');
            isValid = false;
        }
        
        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        }
        
        // Password strength (for registration)
        if (field.type === 'password' && value && field.name === 'password') {
            if (value.length < 6) {
                this.showFieldError(field, 'Password must be at least 6 characters long');
                isValid = false;
            }
        }
        
        return isValid;
    }

    showFieldError(field, message) {
        field.style.borderColor = '#e74c3c';
        
        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.style.color = '#e74c3c';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '5px';
        errorElement.textContent = message;
    }

    clearFieldError(field) {
        field.style.borderColor = '';
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    // Loading states
    showLoading(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `<div class="spinner"></div>Processing...`;
            
            // Revert after 5 seconds (safety measure)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
    }

    // Toast notifications
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    }

    // Real-time updates (for task counts, etc.)
    setupRealTimeUpdates() {
        // Update task counts every 30 seconds
        if (document.querySelector('.stats-cards')) {
            setInterval(() => {
                this.updateStats();
            }, 30000);
        }
    }

    updateStats() {
        // This would typically make an AJAX call to get updated stats
        console.log('Updating stats...');
        // Example implementation:
        // fetch('api/get-stats.php')
        //     .then(response => response.json())
        //     .then(data => this.updateStatsUI(data));
    }

    updateStatsUI(stats) {
        // Update the stats cards with new data
        const statCards = document.querySelectorAll('.stat-number');
        // Implementation would depend on your specific data structure
    }

    // Notification system
    checkNotifications() {
        // Check for new notifications
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            this.showBrowserNotification('Taskly', 'Welcome to your task management system!');
        }
    }

    showBrowserNotification(title, message) {
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/favicon.ico'
            });
        }
    }

    // Enhanced search functionality
    setupSearch() {
        const searchInputs = document.querySelectorAll('.search-input');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', TasklyUtils.debounce((e) => {
                this.filterTable(e.target);
            }, 300));
        });
    }

    filterTable(searchInput) {
        const searchTerm = searchInput.value.toLowerCase();
        const contentBox = searchInput.closest('.content-box');
        const table = contentBox.querySelector('table');
        
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            
            if (isVisible) visibleCount++;
        });
        
        // Update the count display if available
        const countSpan = searchInput.parentNode.querySelector('span');
        if (countSpan) {
            const originalText = countSpan.textContent;
            const baseMatch = originalText.match(/(.*?)(\d+)/);
            
            if (baseMatch) {
                const baseText = baseMatch[1].trim();
                const originalCount = baseMatch[2];
                
                if (searchTerm) {
                    countSpan.textContent = `${baseText} (${visibleCount} of ${originalCount})`;
                } else {
                    countSpan.textContent = originalText;
                }
            }
        }
    }

    // Task completion animations
    setupTaskAnimations() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-success') && e.target.closest('.btn-success').textContent.includes('Complete')) {
                const taskRow = e.target.closest('tr');
                if (taskRow) {
                    taskRow.style.transition = 'all 0.3s ease';
                    taskRow.style.opacity = '0.7';
                    taskRow.style.backgroundColor = '#d4edda';
                    
                    setTimeout(() => {
                        this.showToast('Task completed successfully!', 'success');
                    }, 500);
                }
            }
        });
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.tasklyApp = new TasklyApp();
});

// Utility functions
const TasklyUtils = {
    // Format date
    formatDate: (dateString) => {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    },

    // Debounce function for search
    debounce: (func, wait) => {
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

    // Local storage helpers
    setItem: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Error saving to localStorage:', e);
        }
    },

    getItem: (key) => {
        try {
            return JSON.parse(localStorage.getItem(key));
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return null;
        }
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TasklyApp, TasklyUtils };
}