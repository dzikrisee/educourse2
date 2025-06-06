// Live Search Function
function initLiveSearch(inputId, targetId, searchUrl) {
    const searchInput = document.getElementById(inputId);
    const searchResults = document.getElementById(targetId);
    let searchTimeout;

    if (!searchInput || !searchResults) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();

        searchTimeout = setTimeout(() => {
            if (searchTerm.length >= 2) {
                fetch(searchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'search=' + encodeURIComponent(searchTerm)
                })
                .then(response => response.text())
                .then(data => {
                    searchResults.innerHTML = data;
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
            } else if (searchTerm.length === 0) {
                // Reload original content when search is cleared
                location.reload();
            }
        }, 500);
    });
}

// Table Sorting Function
function sortTable(columnIndex, tableId) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Get current sort direction
    const header = table.querySelectorAll('th')[columnIndex];
    const currentSort = header.getAttribute('data-sort') || 'asc';
    const newSort = currentSort === 'asc' ? 'desc' : 'asc';
    
    // Clear all sort indicators
    table.querySelectorAll('th').forEach(th => {
        th.removeAttribute('data-sort');
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Set new sort indicator
    header.setAttribute('data-sort', newSort);
    header.classList.add('sort-' + newSort);
    
    // Sort rows
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        // Check if values are numbers
        const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newSort === 'asc' ? aNum - bNum : bNum - aNum;
        } else {
            return newSort === 'asc' 
                ? aVal.localeCompare(bVal) 
                : bVal.localeCompare(aVal);
        }
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (field.value && !emailRegex.test(field.value)) {
            field.classList.add('error');
            isValid = false;
        }
    });
    
    return isValid;
}

// File Upload Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Confirmation Dialog
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Auto-hide Flash Messages
function hideFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize live search if elements exist
    initLiveSearch('search-input', 'search-results', 'search-ajax.php');
    
    // Auto-hide flash messages
    hideFlashMessages();
    
    // Add click handlers for sortable table headers
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            sortTable(columnIndex, this.closest('table').id);
        });
    });
    
    // Add form validation on submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    });
    
    // Add file preview functionality
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            if (previewId) {
                previewImage(this, previewId);
            }
        });
    });
});

// Mobile menu toggle
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('mobile-active');
}

// Search functionality for course filtering
function filterCourses() {
    const searchTerm = document.getElementById('course-search').value.toLowerCase();
    const categoryFilter = document.getElementById('category-filter').value;
    const courseCards = document.querySelectorAll('.course-card');
    
    courseCards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const category = card.getAttribute('data-category');
        
        const matchesSearch = title.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}