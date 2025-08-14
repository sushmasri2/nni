<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;
use local_dashboardv2\output\reports_page;

// Require login and set up page
require_login();
$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/dashboardv2/reports.php');
$PAGE->set_title(get_string('reports', 'local_dashboardv2'));
$PAGE->set_heading(get_string('reports', 'local_dashboardv2'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/dashboardv2/style/dashboard.css');

// Get parameters
$report_type = required_param('type', PARAM_ALPHA);
$selected_area_manager = optional_param('area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('nutrition_officer', '', PARAM_TEXT);

// Get current user role and hierarchy
$current_role = user_hierarchy::get_current_user_role($USER->id);
$user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

// Validate report type
$valid_report_types = ['newregistrations', 'courseenrolments', 'coursecompletions', 'activeusers', 'inactiveusers'];
if (!in_array($report_type, $valid_report_types)) {
    print_error('invalidreporttype', 'local_dashboardv2');
}

// Check access based on role
switch ($current_role) {
    case 'spoc':
        require_capability('local/dashboardv2:viewspocdata', $context);
        break;
    case 'area_manager':
        require_capability('local/dashboardv2:viewareamanagerdata', $context);
        break;
    default:
        print_error('noaccess', 'local_dashboardv2');
}

// Initialize data arrays
$area_managers = [];
$nutrition_officers = [];
$report_data = [];

// Load data based on user role
switch ($current_role) {
    case 'spoc':
        if ($user_hierarchy && $user_hierarchy->spoc) {
            $area_managers = user_hierarchy::get_area_managers_by_region($user_hierarchy->spoc);
            
            if ($selected_area_manager) {
                $report_data = user_hierarchy::get_report_data($report_type, 'area_manager', $selected_area_manager);
            } else {
                $report_data = user_hierarchy::get_report_data($report_type, 'spoc', $user_hierarchy->spoc);
            }
        }
        break;
        
    case 'area_manager':
        $nutrition_officers = user_hierarchy::get_nutrition_officers_by_area_manager($USER->username);
        
        if ($selected_nutrition_officer) {
            $report_data = user_hierarchy::get_report_data($report_type, 'nutrition_officer', $selected_nutrition_officer);
        } else {
            $report_data = user_hierarchy::get_report_data($report_type, 'area_manager', $USER->username);
        }
        break;
}

// Initialize renderer
$renderer = $PAGE->get_renderer('local_dashboardv2');

// Create and render reports page
$reports_page = new reports_page(
    $report_type,
    $current_role,
    $user_hierarchy,
    $area_managers,
    $nutrition_officers,
    $report_data,
    $selected_area_manager,
    $selected_nutrition_officer
);

// Output page
echo $OUTPUT->header();
echo $renderer->render_reports_page($reports_page);
echo $OUTPUT->footer();