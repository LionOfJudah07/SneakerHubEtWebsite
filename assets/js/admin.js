// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin dashboard components
    initializeAdminCharts();
    initializeDataTables();
    initializeAdminModals();
    initializeBulkActions();
    initializeDateRangePicker();
    initializeExportButtons();
    
    // Real-time updates
    startDashboardUpdates();
});

// Initialize admin charts
function initializeAdminCharts() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#4e73df',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'ETB ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ETB ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Load revenue data
        loadRevenueData(revenueChart);
    }
    
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [65, 59, 80, 81, 56, 55],
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Users Chart
    const usersCtx = document.getElementById('usersChart');
    if (usersCtx) {
        new Chart(usersCtx, {
            type: 'doughnut',
            data: {
                labels: ['Buyers', 'Vendors', 'Admins'],
                datasets: [{
                    data: [70, 25, 5],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Load revenue data for chart
function loadRevenueData(chart) {
    fetch('/sneaker-commerce/api/admin.php?action=revenue_stats&period=monthly')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chart.data.labels = data.data.map(item => item.month);
                chart.data.datasets[0].data = data.data.map(item => item.revenue);
                chart.update();
            }
        })
        .catch(error => console.error('Error loading revenue data:', error));
}

// Initialize DataTables
function initializeDataTables() {
    const dataTables = document.querySelectorAll('table[data-datatable="true"]');
    
    dataTables.forEach(table => {
        $(table).DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                zeroRecords: 'No matching records found',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                // Add custom filter buttons
                addTableFilters(this);
            }
        });
    });
}

// Add custom filters to DataTables
function addTableFilters(datatable) {
    const table = datatable.table().node();
    const filterRow = document.createElement('tr');
    filterRow.className = 'table-filters';
    
    // Add filter cells for each column
    $(table).find('thead th').each(function() {
        const cell = document.createElement('th');
        const columnIndex = $(this).index();
        const column = datatable.column(columnIndex);
        
        // Only add filters to searchable columns
        if (column.dataSrc() !== 'actions' && column.dataSrc() !== 'select') {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm';
            input.placeholder = 'Filter...';
            input.style.width = '100%';
            
            input.addEventListener('keyup', debounce(function() {
                column.search(this.value).draw();
            }, 300));
            
            cell.appendChild(input);
        }
        
        filterRow.appendChild(cell);
    });
    
    $(table).find('thead').append(filterRow);
}

// Initialize admin modals
function initializeAdminModals() {
    // Status update modal
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const itemId = button.dataset.itemId;
            const currentStatus = button.dataset.currentStatus;
            const itemType = button.dataset.itemType || 'item';
            
            const modal = this;
            modal.querySelector('#status_item_id').value = itemId;
            modal.querySelector('#status_item_type').value = itemType;
            modal.querySelector('#status').value = currentStatus;
        });
    }
    
    // Delete confirmation modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const itemId = button.dataset.itemId;
            const itemName = button.dataset.itemName;
            const itemType = button.dataset.itemType || 'item';
            
            const modal = this;
            modal.querySelector('#delete_item_id').value = itemId;
            modal.querySelector('#delete_item_type').value = itemType;
            modal.querySelector('.delete-item-name').textContent = itemName;
        });
    }
    
    // Bulk edit modal
    const bulkEditModal = document.getElementById('bulkEditModal');
    if (bulkEditModal) {
        bulkEditModal.addEventListener('show.bs.modal', function(event) {
            const selectedItems = getSelectedItems();
            const modal = this;
            
            modal.querySelector('.selected-count').textContent = selectedItems.length;
            
            // Enable/disable form based on selection
            const form = modal.querySelector('form');
            form.querySelectorAll('input, select, button').forEach(element => {
                element.disabled = selectedItems.length === 0;
            });
        });
    }
}

// Initialize bulk actions
function initializeBulkActions() {
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateRowSelection(checkbox);
            });
            updateBulkActions();
        });
    }
    
    // Individual checkboxes
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRowSelection(this);
            updateBulkActions();
        });
    });
    
    // Bulk action form
    const bulkActionForm = document.getElementById('bulkActionForm');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedItems = getSelectedItems();
            const action = this.querySelector('select[name="bulk_action"]').value;
            
            if (selectedItems.length === 0) {
                showNotification('Please select at least one item.', 'warning');
                return;
            }
            
            if (!action) {
                showNotification('Please select an action.', 'warning');
                return;
            }
            
            if (action === 'delete' && !confirm(`Are you sure you want to delete ${selectedItems.length} item(s)?`)) {
                return;
            }
            
            performBulkAction(action, selectedItems);
        });
    }
}

// Update row selection style
function updateRowSelection(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
        row.classList.add('table-active');
    } else {
        row.classList.remove('table-active');
    }
}

// Get selected items
function getSelectedItems() {
    const selected = [];
    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });
    return selected;
}

// Update bulk actions state
function updateBulkActions() {
    const selectedCount = getSelectedItems().length;
    const totalCount = document.querySelectorAll('.item-checkbox').length;
    const selectAllCheckbox = document.getElementById('selectAll');
    const bulkActionForm = document.getElementById('bulkActionForm');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = selectedCount === totalCount && totalCount > 0;
        selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < totalCount;
    }
    
    if (bulkActionForm) {
        const actionSelect = bulkActionForm.querySelector('select[name="bulk_action"]');
        const actionButton = bulkActionForm.querySelector('button[type="submit"]');
        
        if (selectedCount === 0) {
            actionSelect.disabled = true;
            actionButton.disabled = true;
            actionButton.textContent = 'Apply';
        } else {
            actionSelect.disabled = false;
            actionButton.disabled = false;
            actionButton.textContent = `Apply to ${selectedCount} item(s)`;
        }
    }
    
    // Update selected count display
    const selectedCountElement = document.querySelector('.selected-count');
    if (selectedCountElement) {
        if (selectedCount > 0) {
            selectedCountElement.textContent = `${selectedCount} selected`;
            selectedCountElement.style.display = 'inline';
        } else {
            selectedCountElement.style.display = 'none';
        }
    }
}

// Perform bulk action
function performBulkAction(action, items) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('items', JSON.stringify(items));
    
    fetch('/sneaker-commerce/api/admin.php?action=bulk_update', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Reload page after successful bulk action
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize date range picker
function initializeDateRangePicker() {
    const dateRangeInput = document.getElementById('dateRange');
    if (dateRangeInput) {
        flatpickr(dateRangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    const endDate = selectedDates[1].toISOString().split('T')[0];
                    
                    // Update hidden inputs
                    document.getElementById('date_from').value = startDate;
                    document.getElementById('date_to').value = endDate;
                }
            }
        });
    }
}

// Initialize export buttons
function initializeExportButtons() {
    document.querySelectorAll('.export-btn').forEach(button => {
        button.addEventListener('click', function() {
            const format = this.dataset.format || 'csv';
            const type = this.dataset.type || 'data';
            const filters = getCurrentFilters();
            
            exportData(type, format, filters);
        });
    });
}

// Get current filter values
function getCurrentFilters() {
    const filters = {};
    document.querySelectorAll('[data-filter]').forEach(input => {
        if (input.value) {
            filters[input.dataset.filter] = input.value;
        }
    });
    return filters;
}

// Export data
function exportData(type, format, filters) {
    const params = new URLSearchParams({
        action: 'export',
        type: type,
        format: format,
        ...filters
    });
    
    window.location.href = `/sneaker-commerce/api/admin.php?${params.toString()}`;
}

// Start dashboard updates
function startDashboardUpdates() {
    // Update stats every 60 seconds
    setInterval(updateDashboardStats, 60000);
    
    // Check for new notifications every 30 seconds
    setInterval(checkNotifications, 30000);
}

// Update dashboard stats
function updateDashboardStats() {
    fetch('/sneaker-commerce/api/admin.php?action=dashboard_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCards(data.stats);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Update stat cards
function updateStatCards(stats) {
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`.stat-${key}`);
        if (element) {
            const oldValue = parseInt(element.textContent.replace(/,/g, '')) || 0;
            const newValue = stats[key];
            
            if (oldValue !== newValue) {
                animateCounter(element, oldValue, newValue);
            }
        }
    });
}

// Animate counter
function animateCounter(element, start, end) {
    const duration = 1000;
    const steps = 60;
    const increment = (end - start) / steps;
    let current = start;
    let step = 0;
    
    const timer = setInterval(() => {
        current += increment;
        step++;
        
        if (step >= steps) {
            current = end;
            clearInterval(timer);
        }
        
        element.textContent = Math.round(current).toLocaleString();
    }, duration / steps);
}

// Check for new notifications
function checkNotifications() {
    fetch('/sneaker-commerce/api/admin.php?action=notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.unread > 0) {
                updateNotificationBadge(data.unread);
                showNewNotifications(data.notifications);
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Show new notifications
function showNewNotifications(notifications) {
    notifications.forEach(notification => {
        showNotification(notification.message, notification.type || 'info');
    });
}

// Helper function for debounce
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

// Show notification (from main.js)
function showNotification(message, type = 'info') {
    // Use the main.js notification function if available
    if (window.SneakerHub && window.SneakerHub.showNotification) {
        window.SneakerHub.showNotification(message, type);
    } else {
        // Fallback implementation
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdminCharts);
} else {
    initializeAdminCharts();
}