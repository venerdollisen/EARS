// EARS Dashboard JavaScript
$(document).ready(function() {
    // Sidebar toggle for mobile
    $('.sidebar-toggle').click(function() {
        $('.sidebar').toggleClass('show');
    });

    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('.sidebar, .sidebar-toggle').length) {
                $('.sidebar').removeClass('show');
            }
        }
    });

    // Aggressive auto-hide for ALL alerts - guaranteed to work
    function forceAutoHideAllAlerts() {
        $('.alert').each(function() {
            const $alert = $(this);
            if (!$alert.data('auto-hide-set')) {
                $alert.data('auto-hide-set', true);
                setTimeout(function() {
                    $alert.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2500);
            }
        });
    }
    
    // Run immediately and then every 500ms to catch ALL alerts
    forceAutoHideAllAlerts();
    setInterval(forceAutoHideAllAlerts, 500);

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Common API functions
    window.EARS = {
        // Show alert message at the top of the page by default
        showAlert: function(message, type = 'info', container = '#globalAlertContainer') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            // If container doesn't exist, fall back to in-page alertContainer
            const $container = $(container).length ? $(container) : $('#alertContainer');
            // Replace any existing alert and then scroll to top so it's visible
            $container.html(alertHtml);
            try {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                window.scrollTo(0, 0);
            }
            
            // Force auto-dismiss after 2.5 seconds with improved fade out effect
            const dismissTimeout = setTimeout(function() {
                $container.find('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2500);
            
            // Handle manual close button click to clear timeout
            // Remove any existing event listeners to prevent duplication
            $container.find('.btn-close').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                clearTimeout(dismissTimeout);
                $(this).closest('.alert').fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        // Show loading spinner
        showLoading: function(button) {
            const originalText = button.html();
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Loading...');
            return originalText;
        },

        // Hide loading spinner
        hideLoading: function(button, originalText) {
            button.prop('disabled', false).html(originalText);
        },

        // Format currency
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(amount);
        },

        // Format date
        formatDate: function(dateString) {
            return new Date(dateString).toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },

        // Make API request
        apiRequest: function(url, method = 'GET', data = null) {
            return $.ajax({
                url: url,
                method: method,
                contentType: 'application/json',
                data: data ? JSON.stringify(data) : null
            });
        },

        // Handle form submission
        handleFormSubmit: function(formSelector, successCallback = null) {
            $(formSelector).submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = EARS.showLoading(submitBtn);
                
                const formData = {};
                form.serializeArray().forEach(function(item) {
                    formData[item.name] = item.value;
                });
                
                EARS.apiRequest(form.attr('action'), 'POST', formData)
                    .done(function(response) {
                        if (response.success) {
                            EARS.showAlert(response.message, 'success');
                            if (successCallback) successCallback(response);
                        } else {
                            EARS.showAlert(response.error, 'danger');
                        }
                    })
                    .fail(function(xhr) {
                        const response = xhr.responseJSON;
                        EARS.showAlert(response?.error || 'An error occurred', 'danger');
                    })
                    .always(function() {
                        EARS.hideLoading(submitBtn, originalText);
                    });
            });
        },

        // Initialize data tables
        initDataTable: function(selector, options = {}) {
            const defaultOptions = {
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            };
            
            return $(selector).DataTable($.extend(defaultOptions, options));
        },

        // Initialize select2
        initSelect2: function(selector, options = {}) {
            const defaultOptions = {
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option...'
            };
            
            return $(selector).select2($.extend(defaultOptions, options));
        },

        // Initialize date picker
        initDatePicker: function(selector, options = {}) {
            const defaultOptions = {
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            };
            
            return $(selector).datepicker($.extend(defaultOptions, options));
        },

        // Initialize modal
        showModal: function(modalId, data = {}) {
            const modal = $(modalId);
            
            // Set modal data
            Object.keys(data).forEach(function(key) {
                modal.find(`[data-field="${key}"]`).val(data[key]);
            });
            
            modal.modal('show');
        },

        // Close modal
        closeModal: function(modalId) {
            $(modalId).modal('hide');
        },

        // Validate form
        validateForm: function(formSelector) {
            const form = $(formSelector);
            let isValid = true;
            
            form.find('[required]').each(function() {
                const field = $(this);
                const value = field.val().trim();
                
                if (!value) {
                    field.addClass('is-invalid');
                    isValid = false;
                } else {
                    field.removeClass('is-invalid');
                }
            });
            
            return isValid;
        },

        // Clear form
        clearForm: function(formSelector) {
            $(formSelector)[0].reset();
            $(formSelector).find('.is-invalid').removeClass('is-invalid');
        },

        // Confirm action
        confirm: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        },

        // Delete record
        deleteRecord: function(url, recordName = 'record') {
            EARS.confirm(`Are you sure you want to delete this ${recordName}?`, function() {
                EARS.apiRequest(url, 'DELETE')
                    .done(function(response) {
                        if (response.success) {
                            EARS.showAlert(response.message, 'success');
                            location.reload();
                        } else {
                            EARS.showAlert(response.error, 'danger');
                        }
                    })
                    .fail(function(xhr) {
                        const response = xhr.responseJSON;
                        EARS.showAlert(response?.error || 'Failed to delete record', 'danger');
                    });
            });
        },

        // Style required field asterisks
        styleRequiredFields: function() {
            $('.form-label').each(function() {
                const $label = $(this);
                const text = $label.html();
                if (text && text.includes('*')) {
                    const newText = text.replace(/\*/g, '<span class="asterisk">*</span>');
                    $label.html(newText);
                }
            });
        }
    };

    // Global error handler for AJAX requests
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (xhr.status === 401) {
            // Unauthorized - redirect to login
            window.location.href = APP_URL + '/login';
        } else if (xhr.status === 403) {
            // Forbidden
            EARS.showAlert('You do not have permission to perform this action.', 'danger');
        } else if (xhr.status === 500) {
            // Server error
            EARS.showAlert('A server error occurred. Please try again later.', 'danger');
        }
    });

    // Auto-save form data to localStorage (opt-in via data-autosave="true")
    $('form').each(function() {
        const form = $(this);
        const isAutoSaveEnabled = (form.data('autosave') === true) || (form.attr('data-autosave') === 'true');
        const formId = form.attr('id');

        // If autosave is not enabled, ensure any previous cache is cleared and skip
        if (!isAutoSaveEnabled) {
            if (formId) {
                try { localStorage.removeItem(formId); } catch (e) {}
            }
            return; // do not attach listeners
        }

        if (!formId) {
            // Autosave requires a stable id
            return;
        }

        // Load saved data
        const savedData = localStorage.getItem(formId);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(function(key) {
                form.find(`[name="${key}"]`).val(data[key]);
            });
        }

        // Save data on input change
        form.on('input change', 'input, select, textarea', function() {
            const formData = {};
            form.serializeArray().forEach(function(item) {
                formData[item.name] = item.value;
            });
            localStorage.setItem(formId, JSON.stringify(formData));
        });

        // Clear saved data on successful submission
        form.on('submit', function() {
            setTimeout(function() {
                localStorage.removeItem(formId);
            }, 1000);
        });
    });

    // Initialize common components
    EARS.initSelect2('.select2');
    EARS.initDatePicker('.datepicker');
    EARS.styleRequiredFields();
}); 