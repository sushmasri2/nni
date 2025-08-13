// Reports JavaScript - Place in /local/dashboardv2/js/reports.js

var Reports = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // Filter change events
        var areaManagerSelect = document.getElementById('selected_area_manager');
        var nutritionOfficerSelect = document.getElementById('selected_nutrition_officer');
        
        if (areaManagerSelect) {
            areaManagerSelect.addEventListener('change', function() {
                self.reloadReport();
            });
        }
        
        if (nutritionOfficerSelect) {
            nutritionOfficerSelect.addEventListener('change', function() {
                self.reloadReport();
            });
        }
        
        // Search functionality
        var searchInput = document.getElementById('search-report');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                self.searchReport(this.value);
            });
        }
        
        // Download button
        var downloadBtn = document.getElementById('download-report');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                self.downloadReport();
            });
        }
    },
    
    reloadReport: function() {
        var selectedAreaManager = document.getElementById('selected_area_manager') ? 
            document.getElementById('selected_area_manager').value : '';
        var selectedNutritionOfficer = document.getElementById('selected_nutrition_officer') ? 
            document.getElementById('selected_nutrition_officer').value : '';
        
        // Get current report type from URL
        var urlParams = new URLSearchParams(window.location.search);
        var reportType = urlParams.get('type');
        
        // Build new URL with current filters
        var newUrl = new URL(window.location);
        if (selectedAreaManager) {
            newUrl.searchParams.set('area_manager', selectedAreaManager);
        } else {
            newUrl.searchParams.delete('area_manager');
        }
        
        if (selectedNutritionOfficer) {
            newUrl.searchParams.set('nutrition_officer', selectedNutritionOfficer);
        } else {
            newUrl.searchParams.delete('nutrition_officer');
        }
        
        // Reload page with new parameters
        window.location.href = newUrl.toString();
    },
    
    searchReport: function(searchTerm) {
        var table = document.querySelector('.reports-data-table');
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
            
            // Search in first 6 columns: username, fullname, email, spoc, area_manager, nutrition_officer
            for (var i = 0; i < Math.min(6, cells.length); i++) {
                if (cells[i] && cells[i].textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        });
        
        this.updateRecordCount();
    },
    
    updateRecordCount: function() {
        var table = document.querySelector('.reports-data-table');
        var summaryElement = document.querySelector('.reports-summary .alert');
        
        if (!table || !summaryElement) return;
        
        var totalRows = table.querySelectorAll('tbody tr').length;
        var visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])').length;
        
        if (visibleRows < totalRows) {
            var originalText = summaryElement.innerHTML;
            var newText = originalText.replace(
                /Showing: <strong>\d+<\/strong>/,
                'Showing: <strong>' + visibleRows + '</strong> of <strong>' + totalRows + '</strong>'
            );
            if (newText === originalText) {
                // Add showing count if not present
                newText = originalText.replace(
                    /Total records: <strong>\d+<\/strong>/,
                    '$& | Showing: <strong>' + visibleRows + '</strong>'
                );
            }
            summaryElement.innerHTML = newText;
        }
    },
    
    downloadReport: function() {
        var selectedAreaManager = document.getElementById('selected_area_manager') ? 
            document.getElementById('selected_area_manager').value : '';
        var selectedNutritionOfficer = document.getElementById('selected_nutrition_officer') ? 
            document.getElementById('selected_nutrition_officer').value : '';
        
        // Get current report type from URL
        var urlParams = new URLSearchParams(window.location.search);
        var reportType = urlParams.get('type');
        
        // Create form and submit for download
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = M.cfg.wwwroot + '/local/dashboardv2/download_report.php';
        
        // Add report type
        var reportTypeInput = document.createElement('input');
        reportTypeInput.type = 'hidden';
        reportTypeInput.name = 'report_type';
        reportTypeInput.value = reportType;
        form.appendChild(reportTypeInput);
        
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
    Reports.init();
});