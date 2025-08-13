// Feedback Reports JavaScript - Place in /local/dashboardv2/js/feedback_reports.js

var FeedbackReports = {
    
    init: function() {
        this.bindEvents();
    },
    
    bindEvents: function() {
        var self = this;
        
        // Filter change events
        var areaManagerSelect = document.getElementById('area_manager');
        var nutritionOfficerSelect = document.getElementById('nutrition_officer');
        var courseSelect = document.getElementById('course');
        var feedbackSelect = document.getElementById('feedback');
        
        if (areaManagerSelect) {
            areaManagerSelect.addEventListener('change', function() {
                // Clear dependent dropdowns
                if (courseSelect) courseSelect.value = '';
                if (feedbackSelect) feedbackSelect.value = '';
                self.reloadPage();
            });
        }
        
        if (nutritionOfficerSelect) {
            nutritionOfficerSelect.addEventListener('change', function() {
                // Clear dependent dropdowns
                if (courseSelect) courseSelect.value = '';
                if (feedbackSelect) feedbackSelect.value = '';
                self.reloadPage();
            });
        }
        
        if (courseSelect) {
            courseSelect.addEventListener('change', function() {
                // Clear feedback dropdown
                if (feedbackSelect) feedbackSelect.value = '';
                self.reloadPage();
            });
        }
        
        if (feedbackSelect) {
            feedbackSelect.addEventListener('change', function() {
                self.reloadPage();
            });
        }
        
        // Download functionality
        this.addDownloadButton();
    },
    
    reloadPage: function() {
        var form = document.getElementById('feedback-filters-form');
        if (form) {
            form.submit();
        }
    },
    
    addDownloadButton: function() {
        var tableContainer = document.querySelector('.feedback-table-container .card-body');
        if (!tableContainer || !document.querySelector('.feedback-data-table')) {
            return;
        }
        
        var downloadBtn = document.createElement('div');
        downloadBtn.className = 'text-right mb-3';
        downloadBtn.innerHTML = '<button id="download-feedback-csv" class="btn btn-success"><i class="fa fa-download"></i> Download CSV</button>';
        
        tableContainer.insertBefore(downloadBtn, tableContainer.firstChild);
        
        document.getElementById('download-feedback-csv').addEventListener('click', function() {
            FeedbackReports.downloadCSV();
        });
    },
    
    downloadCSV: function() {
        var table = document.querySelector('.feedback-data-table');
        if (!table) return;
        
        var csv = [];
        var rows = table.querySelectorAll('tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (var j = 0; j < cols.length; j++) {
                // Clean up the cell content
                var cellText = cols[j].innerText || cols[j].textContent || '';
                cellText = cellText.replace(/"/g, '""'); // Escape quotes
                row.push('"' + cellText + '"');
            }
            
            csv.push(row.join(','));
        }
        
        var csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        var downloadLink = document.createElement('a');
        
        downloadLink.download = 'feedback_report_' + new Date().toISOString().slice(0,10) + '.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    },
    
    searchTable: function(searchTerm) {
        var table = document.querySelector('.feedback-data-table');
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
            var question = row.querySelector('td:nth-child(2)');
            var found = false;
            
            if (question && question.textContent.toLowerCase().includes(searchTerm)) {
                found = true;
            }
            
            row.style.display = found ? '' : 'none';
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    FeedbackReports.init();
});