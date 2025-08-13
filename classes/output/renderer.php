<?php
namespace local_dashboardv2\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

/**
 * Renderer for local_dashboardv2
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Render the dashboard page
     */
    public function render_dashboard_page(dashboard_page $page) {
        // Add JavaScript file
        $this->page->requires->js('/local/dashboardv2/js/dashboard.js');
        
        // Export data and render template
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_dashboardv2/dashboard', $data);
    }
    
    /**
     * Render the reports page
     */
    public function render_reports_page(reports_page $page) {
        // Add JavaScript file
        $this->page->requires->js('/local/dashboardv2/js/reports.js');
        
        // Export data and render template
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_dashboardv2/reports', $data);
    }
    
    /**
     * Render access denied message
     */
    public function render_access_denied($current_role) {
        $context = [
            'current_role' => $current_role ?: 'Not Assigned',
            'required_roles' => 'Manager or Area Manager'
        ];
        
        return $this->render_from_template('local_dashboardv2/access_denied', $context);
    }
}