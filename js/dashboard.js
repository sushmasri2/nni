
var Dashboard = {

    init: function () {
        this.bindEvents();
    },

    bindEvents: function () {
        var self = this;

        // AJAX form submission for dropdowns
        var areaManagerSelect = document.getElementById('selected_area_manager');
        var nutritionOfficerSelect = document.getElementById('selected_nutrition_officer');

        if (areaManagerSelect) {
            areaManagerSelect.addEventListener('change', function () {
                self.loadUsersData();
            });
        }

        if (nutritionOfficerSelect) {
            nutritionOfficerSelect.addEventListener('change', function () {
                self.loadUsersData();
            });
        }

        // Search functionality
        var searchInput = document.getElementById('search-users');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                self.searchUsers(this.value);
            });
        }

        // Download button
        var downloadBtn = document.getElementById('download-users-table');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                self.downloadCSV();
            });
        }

        // Feedback filter events
        var feedbackFilters = document.querySelectorAll('.feedback-filter');
        feedbackFilters.forEach(function (filter) {
            filter.addEventListener('change', function () {
                self.loadFeedbackData();
            });
        });

        // Course selection change event
        var courseSelect = document.getElementById('selected_course');
        if (courseSelect) {
            courseSelect.addEventListener('change', function () {
                self.loadFeedbackForms(this.value);
            });
        }
    },

    loadUsersData: function () {
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

    updateUsersTable: function (data) {
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

        data.forEach(function (user) {
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

    searchUsers: function (searchTerm) {
        var table = document.querySelector('.users-data-table');
        if (!table) return;

        var rows = table.querySelectorAll('tbody tr');

        if (!searchTerm) {
            rows.forEach(function (row) {
                row.style.display = '';
            });
            return;
        }

        searchTerm = searchTerm.toLowerCase();

        rows.forEach(function (row) {
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

    downloadCSV: function () {
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
    },

    loadFeedbackForms: function (courseId) {
        var feedbackSelect = document.getElementById('selected_feedback');
        if (!feedbackSelect) return;

        // Clear existing options
        feedbackSelect.innerHTML = '<option value="">-- Choose a Feedback Form --</option>';

        if (!courseId) {
            var parentP = feedbackSelect.parentElement.querySelector('p');
            if (parentP) {
                parentP.textContent = 'Select a course to see available feedback forms';
            }
            return;
        }

        // AJAX call to get feedback forms
        var formData = new FormData();
        formData.append('action', 'get_feedback_forms');
        formData.append('course_id', courseId);
        formData.append('sesskey', M.cfg.sesskey);

        fetch(M.cfg.wwwroot + '/local/dashboardv2/ajax_get_feedback_forms.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.feedbacks) {
                    data.feedbacks.forEach(function (feedback) {
                        var option = document.createElement('option');
                        option.value = feedback.id;
                        option.textContent = feedback.name;
                        feedbackSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading feedback forms:', error));
    },

    loadFeedbackData: function () {
        var selectedAreaManager = document.getElementById('selected_area_manager') ?
            document.getElementById('selected_area_manager').value : '';
        var selectedNutritionOfficer = document.getElementById('selected_nutrition_officer') ?
            document.getElementById('selected_nutrition_officer').value : '';
        var selectedCourse = document.getElementById('selected_course') ?
            document.getElementById('selected_course').value : '';
        var selectedFeedback = document.getElementById('selected_feedback') ?
            document.getElementById('selected_feedback').value : '';

        if (!selectedCourse || !selectedFeedback) {
            return; // Need both course and feedback to proceed
        }

        // Show loading indicator
        var container = document.querySelector('.feedback-results');
        if (container) {
            container.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading feedback analysis...</div>';
        }

        // AJAX request for feedback analysis
        var formData = new FormData();
        formData.append('action', 'get_feedback_analysis');
        formData.append('area_manager', selectedAreaManager);
        formData.append('nutrition_officer', selectedNutritionOfficer);
        formData.append('course_id', selectedCourse);
        formData.append('feedback_id', selectedFeedback);
        formData.append('sesskey', M.cfg.sesskey);

        fetch(M.cfg.wwwroot + '/local/dashboardv2/ajax_get_feedback_analysis.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateFeedbackResults(data.data);
                } else {
                    console.error('Error loading feedback analysis:', data.message);
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Error loading feedback analysis</div>';
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

    updateFeedbackResults: function (feedbackData) {
        var container = document.querySelector('.feedback-results');
        if (!container) return;

        if (!feedbackData || feedbackData.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No feedback data found for the selected criteria.</div>';
            return;
        }

        // Build feedback results table
        var tableHTML = `
            <div class="card">
                <div class="card-header">
                    <h5>Feedback Analysis Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Excellent</th>
                                    <th>Good</th>
                                    <th>Average</th>
                                    <th>Needs Improvement</th>
                                    <th>Avg Score</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        feedbackData.forEach(function (item) {
            var categoryClass = '';
            switch (item.final_category) {
                case 'Excellent': categoryClass = 'badge-success'; break;
                case 'Good': categoryClass = 'badge-primary'; break;
                case 'Average': categoryClass = 'badge-warning'; break;
                case 'Needs Improvement': categoryClass = 'badge-danger'; break;
                default: categoryClass = 'badge-secondary';
            }

            tableHTML += `
                <tr>
                    <td>${item.question}</td>
                    <td><span class="badge badge-success">${item.excellent}</span></td>
                    <td><span class="badge badge-primary">${item.good}</span></td>
                    <td><span class="badge badge-warning">${item.average}</span></td>
                    <td><span class="badge badge-danger">${item.needs_improvement}</span></td>
                    <td><strong>${item.avg_score}</strong></td>
                    <td><span class="badge ${categoryClass}">${item.final_category}</span></td>
                </tr>
            `;
        });

        tableHTML += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = tableHTML;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    Dashboard.init();
});