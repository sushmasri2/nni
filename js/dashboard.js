// Corrected dashboard.js - Place in /local/dashboardv2/js/dashboard.js

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
        
        // Feedback section event listeners
        var courseSelect = document.getElementById('selected_course');
        var feedbackSelect = document.getElementById('selected_feedback');
        var feedbackAreaManager = document.getElementById('feedback_area_manager');
        var feedbackNutritionOfficer = document.getElementById('feedback_nutrition_officer');
        
        if (courseSelect) {
            courseSelect.addEventListener('change', function() {
                // Reset feedback selection when course changes
                if (feedbackSelect) {
                    feedbackSelect.innerHTML = '<option value="">-- Choose a Feedback --</option>';
                    feedbackSelect.disabled = true;
                }
                self.loadFeedbackData();
            });
        }
        
        if (feedbackSelect) {
            feedbackSelect.addEventListener('change', function() {
                self.loadFeedbackData();
            });
        }
        
        if (feedbackAreaManager) {
            feedbackAreaManager.addEventListener('change', function() {
                // Reset course and feedback selections when hierarchy changes
                if (courseSelect) courseSelect.selectedIndex = 0;
                if (feedbackSelect) {
                    feedbackSelect.innerHTML = '<option value="">-- Choose a Feedback --</option>';
                    feedbackSelect.disabled = true;
                }
                self.resetFeedbackDisplay();
            });
        }
        
        if (feedbackNutritionOfficer) {
            feedbackNutritionOfficer.addEventListener('change', function() {
                // Reset course and feedback selections when hierarchy changes
                if (courseSelect) courseSelect.selectedIndex = 0;
                if (feedbackSelect) {
                    feedbackSelect.innerHTML = '<option value="">-- Choose a Feedback --</option>';
                    feedbackSelect.disabled = true;
                }
                self.resetFeedbackDisplay();
            });
        }
    },
    
    loadFeedbackData: function() {
        var courseId = document.getElementById('selected_course') ? 
            document.getElementById('selected_course').value : '';
        var feedbackId = document.getElementById('selected_feedback') ? 
            document.getElementById('selected_feedback').value : '';
        var feedbackAreaManager = document.getElementById('feedback_area_manager') ? 
            document.getElementById('feedback_area_manager').value : '';
        var feedbackNutritionOfficer = document.getElementById('feedback_nutrition_officer') ? 
            document.getElementById('feedback_nutrition_officer').value : '';
        
        // If course is selected but no feedback, load feedback list
        if (courseId && !feedbackId) {
            this.loadFeedbackList();
            return;
        }
        
        // If feedback is selected, automatically load and display feedback data
        if (feedbackId) {
            var container = document.querySelector('.feedback-data-container');
            if (container) {
                container.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading feedback analysis...</div>';
            }
            
            var formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('feedback_id', feedbackId);
            formData.append('area_manager', feedbackAreaManager);
            formData.append('nutrition_officer', feedbackNutritionOfficer);
            formData.append('sesskey', M.cfg.sesskey);
            
            fetch(M.cfg.wwwroot + '/local/dashboardv2/ajax_get_feedback_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.feedback_data) {
                    this.updateFeedbackTable(data.data.feedback_data);
                } else {
                    console.error('Error loading feedback:', data.message);
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Error loading feedback data</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Ajax error:', error);
                if (container) {
                    container.innerHTML = '<div class="alert alert-danger">Network error loading feedback</div>';
                }
            });
        } else {
            this.resetFeedbackDisplay();
        }
    },
    
    loadFeedbackList: function() {
        var courseId = document.getElementById('selected_course').value;
        var feedbackAreaManager = document.getElementById('feedback_area_manager') ? 
            document.getElementById('feedback_area_manager').value : '';
        var feedbackNutritionOfficer = document.getElementById('feedback_nutrition_officer') ? 
            document.getElementById('feedback_nutrition_officer').value : '';
        
        var formData = new FormData();
        formData.append('course_id', courseId);
        formData.append('area_manager', feedbackAreaManager);
        formData.append('nutrition_officer', feedbackNutritionOfficer);
        formData.append('sesskey', M.cfg.sesskey);
        
        fetch(M.cfg.wwwroot + '/local/dashboardv2/ajax_get_feedback_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.feedback_list) {
                this.updateFeedbackList(data.data.feedback_list);
            }
        })
        .catch(error => {
            console.error('Ajax error:', error);
        });
    },
    
    updateFeedbackList: function(feedbackList) {
        var feedbackSelect = document.getElementById('selected_feedback');
        if (!feedbackSelect) return;
        
        // Clear current options
        feedbackSelect.innerHTML = '<option value="">-- Choose a Feedback --</option>';
        
        // Add new options
        feedbackList.forEach(function(feedback) {
            var option = document.createElement('option');
            option.value = feedback.id;
            option.textContent = feedback.name;
            feedbackSelect.appendChild(option);
        });
        
        // Enable the select
        feedbackSelect.disabled = false;
        
        // Reset feedback data display
        this.resetFeedbackDisplay();
    },
    
    resetFeedbackDisplay: function() {
        var container = document.querySelector('.feedback-data-container');
        if (container) {
            var courseId = document.getElementById('selected_course') ? 
                document.getElementById('selected_course').value : '';
            
            if (!courseId) {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="no-data">
                                <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Follow the steps above to view feedback analysis</h5>
                                <p class="text-muted">Complete all 3 steps to display detailed feedback data and analysis.</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="no-data">
                                <i class="fa fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Select a feedback activity to view analysis</h5>
                                <p class="text-muted">Choose a specific feedback from the dropdown to display detailed analysis.</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    },
    
    updateFeedbackTable: function(data) {
        var container = document.querySelector('.feedback-data-container');
        if (!container) return;
        
        if (!data || data.length === 0) {
            container.innerHTML = '<div class="no-data"><p>No feedback data found for selected criteria.</p></div>';
            return;
        }
        
        var tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped feedback-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Excellent (4)</th>
                            <th>Good (3)</th>
                            <th>Average (2)</th>
                            <th>Needs Improvement (1)</th>
                            <th>Average Score</th>
                            <th>Category</th>
                            <th>Total Responses</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.forEach(function(feedback) {
            var badgeClass = 'badge-secondary';
            if (feedback.final_category === 'Excellent') badgeClass = 'badge-success';
            else if (feedback.final_category === 'Good') badgeClass = 'badge-primary';
            else if (feedback.final_category === 'Average') badgeClass = 'badge-warning';
            else if (feedback.final_category === 'Needs Improvement') badgeClass = 'badge-danger';
            
            tableHTML += `
                <tr>
                    <td><strong>${feedback.question}</strong></td>
                    <td><span class="badge badge-success">${feedback.excellent}</span></td>
                    <td><span class="badge badge-primary">${feedback.good}</span></td>
                    <td><span class="badge badge-warning">${feedback.average}</span></td>
                    <td><span class="badge badge-danger">${feedback.needs_improvement}</span></td>
                    <td><strong>${feedback.avg_score}</strong></td>
                    <td><span class="badge ${badgeClass}">${feedback.final_category}</span></td>
                    <td><strong>${feedback.total_responses}</strong></td>
                </tr>
            `;
        });
        
        tableHTML += '</tbody></table></div>';
        container.innerHTML = tableHTML;
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