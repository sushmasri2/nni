// Simple dashboard.js - Place in /local/dashboardv2/js/dashboard.js

var Dashboard = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // AJAX form submission for dropdowns
        var areaManagerSelect = document.getElementById('selected_area_manager');
        var nutritionOfficerSelect = document.getElementById('selected_nutrition_officer');
        
        if (areaManagerSelect) {
            areaManagerSelect.addEventListener('change', function() {
                self.loadUsersData();
            });
        }
        
        if (nutritionOfficerSelect) {
            nutritionOfficerSelect.addEventListener('change', function() {
                self.loadUsersData();
            });
        }
        
        // Search functionality
        var searchInput = document.getElementById('search-users');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                self.searchUsers(this.value);
            });
        }
        
        // Download button
        var downloadBtn = document.getElementById('download-users-table');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                self.downloadCSV();
            });
        }
    },
    
    loadUsersData: function() {
        var selectedAreaManager = document.getElementById('selected_area_manager') ? 
            document.getElementById('selected_area_manager').value : '';
        var selectedNutritionOfficer = document.getElementById('selected_nutrition_officer') ? 
            document.getElementById('selected_nutrition_officer').value : '';
        
        // Show loading indicator
        var container = document.querySelector('.users-table-container');
        if (container) {
            container.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>';
        }
        
        // Simple AJAX request using fetch
        var formData = new FormData();
        formData.append('area_manager', selectedAreaManager);
        formData.append('nutrition_officer', selectedNutritionOfficer);
        formData.append('sesskey', M.cfg.sesskey);
        
        fetch(M.cfg.wwwroot + '/local/dashboardv2/ajax_get_users_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateUsersTable(data.data);
            } else {
                console.error('Error loading data:', data.message);
                if (container) {
                    container.innerHTML = '<div class="alert alert-danger">Error loading data</div>';
                }
            }
        })
        .catch(error => {
            console.error('Ajax error:', error);
            if (container) {
                container.innerHTML = '<div class="alert alert-danger">Network error</div>';
            }
        });
    },
    
    updateUsersTable: function(data) {
        var container = document.querySelector('.users-table-container');
        if (!container) return;
        
        if (!data || data.length === 0) {
            container.innerHTML = '<div class="no-data"><p>No users found.</p></div>';
            return;
        }
        
        var tableHTML = `
            <table class="table table-striped users-data-table table-responsive">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>SPOC</th>
                        <th>Regional Head</th>
                        <th>Area Manager</th>
                        <th>Nutrition Officer</th>
                        <th>Module 1</th>
                        <th>Module 2</th>
                        <th>Module 3</th>
                        <th>Module 4</th>
                        <th>Module 5</th>
                        <th>Module 6</th>
                        <th>Module 7</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.forEach(function(user) {
            tableHTML += `
                <tr>
                    <td>${user.username}</td>
                    <td>${user.fullname}</td>
                    <td>${user.email}</td>
                    <td>${user.spoc || ''}</td>
                    <td>${user.regional_head || ''}</td>
                    <td>${user.area_manager || ''}</td>
                    <td>${user.nutrition_officer || ''}</td>
                    <td>${user.module_1 || '0'}%</td>
                    <td>${user.module_2 || '0'}%</td>
                    <td>${user.module_3 || '0'}%</td>
                    <td>${user.module_4 || '0'}%</td>
                    <td>${user.module_5 || '0'}%</td>
                    <td>${user.module_6 || '0'}%</td>
                    <td>${user.module_7 || '0'}%</td>
                </tr>
            `;
        });
        
        tableHTML += '</tbody></table>';
        container.innerHTML = tableHTML;
    },
    
    searchUsers: function(searchTerm) {
        var table = document.querySelector('.users-data-table');
        if (!table) return;
        
        var rows = table.querySelectorAll('tbody tr');
        
        if (!searchTerm) {
            rows.forEach(function(row) {
                row.style.display = '';
            });
            return;
        }
        
        searchTerm = searchTerm.toLowerCase();
        
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            var found = false;
            
            // Search in specific columns: username, fullname, email, spoc, regional_head, area_manager, nutrition_officer
            for (var i = 0; i <= 6; i++) {
                if (cells[i] && cells[i].textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        });
    },
    
    downloadCSV: function() {
        var selectedAreaManager = document.getElementById('selected_area_manager') ? 
            document.getElementById('selected_area_manager').value : '';
        var selectedNutritionOfficer = document.getElementById('selected_nutrition_officer') ? 
            document.getElementById('selected_nutrition_officer').value : '';
        
        // Create form and submit for download
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = M.cfg.wwwroot + '/local/dashboardv2/download.php';
        
        if (selectedAreaManager) {
            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'selected_area_manager';
            input1.value = selectedAreaManager;
            form.appendChild(input1);
        }
        
        if (selectedNutritionOfficer) {
            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'selected_nutrition_officer';
            input2.value = selectedNutritionOfficer;
            form.appendChild(input2);
        }
        
        var sesskey = document.createElement('input');
        sesskey.type = 'hidden';
        sesskey.name = 'sesskey';
        sesskey.value = M.cfg.sesskey;
        form.appendChild(sesskey);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Dashboard.init();
});