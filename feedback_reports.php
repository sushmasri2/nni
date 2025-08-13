<?php
// feedback_reports.php - Place in plugin root directory

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;
use local_dashboardv2\output\feedback_reports_page;

// Require login and set up page
require_login();
$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/dashboardv2/feedback_reports.php');
$PAGE->set_title(get_string('feedback_reports', 'local_dashboardv2'));
$PAGE->set_heading(get_string('feedback_reports', 'local_dashboardv2'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/dashboardv2/style/dashboard.css');

// Get parameters
$selected_area_manager = optional_param('area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('nutrition_officer', '', PARAM_TEXT);
$selected_course = optional_param('course', '', PARAM_INT);
$selected_feedback = optional_param('feedback', '', PARAM_INT);

// Get current user role and hierarchy
$current_role = user_hierarchy::get_current_user_role($USER->id);
$user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

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
$courses = [];
$feedbacks = [];
$feedback_data = [];

// Load data based on user role
switch ($current_role) {
    case 'spoc':
        if ($user_hierarchy && $user_hierarchy->spoc) {
            $area_managers = user_hierarchy::get_area_managers_by_region($user_hierarchy->spoc);
            
            if ($selected_area_manager) {
                $courses = user_hierarchy::get_enrolled_courses_by_area_manager($selected_area_manager);
            } else {
                $courses = user_hierarchy::get_enrolled_courses_by_spoc($user_hierarchy->spoc);
            }
        }
        break;
        
    case 'area_manager':
        $nutrition_officers = user_hierarchy::get_nutrition_officers_by_area_manager($USER->username);
        
        if ($selected_nutrition_officer) {
            $courses = user_hierarchy::get_enrolled_courses_by_nutrition_officer($selected_nutrition_officer);
        } else {
            $courses = user_hierarchy::get_enrolled_courses_by_area_manager($USER->username);
        }
        break;
}

// Get feedbacks for selected course
if ($selected_course) {
    $feedbacks = user_hierarchy::get_feedbacks_by_course($selected_course);
}

// Get feedback data if feedback is selected
if ($selected_feedback) {
    $feedback_data = user_hierarchy::get_feedback_report_data($selected_feedback, $current_role, $selected_area_manager, $selected_nutrition_officer);
}

// Initialize renderer
$renderer = $PAGE->get_renderer('local_dashboardv2');

// Create and render feedback reports page
$feedback_reports_page = new feedback_reports_page(
    $current_role,
    $user_hierarchy,
    $area_managers,
    $nutrition_officers,
    $courses,
    $feedbacks,
    $feedback_data,
    $selected_area_manager,
    $selected_nutrition_officer,
    $selected_course,
    $selected_feedback
);

// Output page
echo $OUTPUT->header();
echo $renderer->render_feedback_reports_page($feedback_reports_page);
echo $OUTPUT->footer();