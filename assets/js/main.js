/**
 * Main JavaScript File
 * Level Up Fitness - Gym Management System
 */

$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Confirm delete action
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this record?')) {
            e.preventDefault();
        }
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Auto-hide alerts after 5 seconds
    $(".alert:not(.alert-permanent)").delay(5000).fadeOut("slow");

    // Format currency on input
    $('.currency-input').on('change', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val('₱' + value.toFixed(2));
        }
    });

    // Date picker (if using a date library)
    // Can be extended with date picker library

    // Responsive sidebar toggle
    $('#sidebarToggle').on('click', function() {
        $('body').toggleClass('sidebar-open');
    });

    // Search functionality
    $('.search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const rows = $('tbody tr');
        
        rows.each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchTerm));
        });
    });

    // Print functionality
    $('.btn-print').on('click', function() {
        window.print();
    });

    // Export to CSV (basic implementation)
    $('.btn-export-csv').on('click', function() {
        const table = $(this).closest('table');
        const csv = tableToCSV(table);
        downloadCSV(csv);
    });
});

/**
 * Convert table to CSV
 */
function tableToCSV(table) {
    let csv = [];
    const rows = table.find('tr');

    rows.each(function() {
        let row = [];
        $(this).find('td, th').each(function() {
            row.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });

    return csv.join('\n');
}

/**
 * Download CSV file
 */
function downloadCSV(csv) {
    const fileName = 'export_' + new Date().toISOString().split('T')[0] + '.csv';
    const link = document.createElement('a');
    link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
    link.setAttribute('download', fileName);
    link.click();
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    $(element).addClass('loading').append('<div class="spinner-border spinner-border-sm ms-2"></div>');
}

/**
 * Hide loading spinner
 */
function hideLoading(element) {
    $(element).removeClass('loading').find('.spinner-border').remove();
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastId = 'toast_' + Date.now();
    const alertClass = 'alert-' + type;
    
    const toastHTML = `
        <div id="${toastId}" class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(toastHTML);
    
    setTimeout(() => {
        $('#' + toastId).fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Format date to readable format
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Format time
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Validate email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone number
 */
function isValidPhone(phone) {
    const re = /^[0-9]{10,15}$/;
    return re.test(phone.replace(/[-\s]/g, ''));
}

/**
 * Confirm action with SweetAlert
 */
function confirmAction(title, message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * ============================================
 * NOTIFICATION SYSTEM FUNCTIONS
 * ============================================
 */

/**
 * Mark notification as read
 */
function markNotificationRead(notificationId) {
    $.ajax({
        url: window.APP_URL + 'api/notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'mark_read',
            notification_id: notificationId
        },
        success: function(response) {
            if (response.success) {
                // Update notification bell count
                updateNotificationBell();
                // Show success Toast
                showToast('Notification marked as read', 'success');
                // Reload the notifications in dropdown
                loadUnreadNotifications();
            }
        },
        error: function() {
            showToast('Error marking notification as read', 'danger');
        }
    });
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsRead() {
    if (confirm('Mark all notifications as read?')) {
        $.ajax({
            url: window.APP_URL + 'api/notifications.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'mark_all_read'
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationBell();
                    showToast('All notifications marked as read', 'success');
                    if (window.location.pathname.includes('notifications')) {
                        location.reload();
                    }
                }
            },
            error: function() {
                showToast('Error marking notifications as read', 'danger');
            }
        });
    }
}

/**
 * Delete notification
 */
function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        $.ajax({
            url: window.APP_URL + 'api/notifications.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'delete',
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationBell();
                    showToast('Notification deleted', 'success');
                    // Remove from DOM if in list view
                    $('#notification-' + notificationId).fadeOut(function() {
                        $(this).remove();
                    });
                }
            },
            error: function() {
                showToast('Error deleting notification', 'danger');
            }
        });
    }
}

/**
 * Update notification bell count
 */
function updateNotificationBell() {
    $.ajax({
        url: window.APP_URL + 'api/notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'get_unread_count'
        },
        success: function(response) {
            if (response.success) {
                const count = response.unread_count;
                const badge = $('#notificationBell .badge');
                
                if (count > 0) {
                    if (badge.length === 0) {
                        $('#notificationBell').append(
                            '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' +
                            (count > 99 ? '99+' : count) +
                            '</span>'
                        );
                    } else {
                        badge.text(count > 99 ? '99+' : count);
                    }
                } else {
                    badge.remove();
                }
            }
        }
    });
}

/**
 * Load unread notifications in dropdown
 */
function loadUnreadNotifications() {
    $.ajax({
        url: window.APP_URL + 'api/notifications.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'get_unread',
            limit: 5
        },
        success: function(response) {
            if (response.success && response.notifications) {
                // Update dropdown if it exists
                const dropdown = $('.notification-dropdown');
                if (dropdown.length > 0) {
                    // Implementation would rebuild the dropdown UI
                    location.reload(); // Simple refresh for now
                }
            }
        }
    });
}

/**
 * Initialize notification system
 * Call on page load if needed
 */
function initNotificationSystem() {
    // APP_URL should already be set in header.php
    // If not set for some reason (e.g., in standalone JS context), try to infer it
    if (typeof window.APP_URL === 'undefined' || !window.APP_URL) {
        // Try to get the app base URL from the current page location
        const path = window.location.pathname;
        if (path.includes('/level-up-fitness/')) {
            window.APP_URL = '/level-up-fitness/';
        } else if (path.includes('/level-up-fitness')) {
            window.APP_URL = '/level-up-fitness/';
        } else {
            window.APP_URL = '/';
        }
    }
    
    // Update bell on page load
    updateNotificationBell();
    
    // Refresh notifications every 30 seconds
    setInterval(updateNotificationBell, 30000);
}

// Initialize on document ready
$(document).ready(function() {
    if ($('#notificationBell').length > 0) {
        initNotificationSystem();
    }
});


