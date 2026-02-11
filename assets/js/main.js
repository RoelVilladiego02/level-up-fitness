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

