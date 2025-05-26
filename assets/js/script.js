/**
 * Lab Equipment Inventory System
 * Main JavaScript File
 */

// Initialize all tooltips
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

// Initialize all popovers
function initPopovers() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
}

// Initialize DataTables
function initDataTables() {
    if ($.fn.DataTable) {
        $('.datatable').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    ordering: true,
                    paging: true,
                    lengthMenu: [10, 25, 50, 100],
                    pageLength: 10,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    }
                });
            }
        });
    }
}

// Confirm delete with SweetAlert2
function confirmDelete(url, itemName = 'item', callback = null) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete this ${itemName}. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            if (callback && typeof callback === 'function') {
                callback();
            } else {
                window.location.href = url;
            }
        }
    });
    
    return false;
}

// Show loading indicator
function showLoading(message = 'Processing...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Hide loading indicator
function hideLoading() {
    Swal.close();
}

// Show success notification
function showSuccess(message, redirect = null) {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        confirmButtonColor: '#198754'
    }).then(() => {
        if (redirect) {
            window.location.href = redirect;
        }
    });
}

// Show error notification
function showError(message) {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        confirmButtonColor: '#dc3545'
    });
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Format date and time for display
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    
    const date = new Date(dateTimeString);
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
}

// Format currency for display
function formatCurrency(amount, symbol = '₱') {
    if (isNaN(amount)) return '';
    
    return symbol + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Document ready function
$(document).ready(function() {
    // Initialize tooltips, popovers, and datatables
    initTooltips();
    initPopovers();
    initDataTables();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Enable printing of reports
    $('.print-report').on('click', function() {
        window.print();
        return false;
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $($(this).data('target'));
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
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
    
    // Ajax form submission for search
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = form.attr('action');
        const formData = form.serialize();
        
        $.ajax({
            url: url,
            type: 'GET',
            data: formData,
            beforeSend: function() {
                $('#searchResults').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
            },
            success: function(response) {
                $('#searchResults').html(response);
                initDataTables();
            },
            error: function() {
                $('#searchResults').html('<div class="alert alert-danger">Error loading search results. Please try again.</div>');
            }
        });
    });
}); 