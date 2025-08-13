<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;
use local_dashboardv2\output\dashboard_page;
use local_dashboardv2\output\dashboard_renderer;

// Require login and set up page
require_login();
$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/dashboardv2/index.php');
$PAGE->set_title(get_string('dashboard', 'local_dashboardv2'));
$PAGE->set_heading(...);
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/dashboardv2/style/dashboard.css');

// Get current user role and hierarchy
$current_role = user_hierarchy::get_current_user_role($USER->id);
$user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

// Process form submissions
$selected_area_manager = optional_param('selected_area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('selected_nutrition_officer', '', PARAM_TEXT);
$selected_course = optional_param('selected_course', 0, PARAM_INT);
$selected_feedback = optional_param('selected_feedback', 0, PARAM_INT);
// Initialize data arrays
$area_managers = [];
$nutrition_officers = [];
$users_data = [];

// Load data based on user role
switch ($current_role) {
    case 'spoc':
        if ($user_hierarchy && $user_hierarchy->spoc) {
            require_capability('local/dashboardv2:viewspocdata', $context);

            $area_managers = user_hierarchy::get_area_managers_by_region($user_hierarchy->spoc);

            if ($selected_area_manager) {
                $users_data = user_hierarchy::get_users_under_area_manager_for_spoc($selected_area_manager);
            } else {
                $users_data = user_hierarchy::get_all_users_under_spoc($user_hierarchy->spoc);
            }
        }
        break;

    case 'area_manager':
        require_capability('local/dashboardv2:viewareamanagerdata', $context);

        $nutrition_officers = user_hierarchy::get_nutrition_officers_by_area_manager($USER->username);

        if ($selected_nutrition_officer) {
            $users_data = user_hierarchy::get_users_under_nutrition_officer_for_area_manager($selected_nutrition_officer);
        } else {
            $users_data = user_hierarchy::get_all_users_under_area_manager($USER->username);
        }
        break;

    default:
        // No access for other roles
        break;
}

// Initialize renderer
$renderer = $PAGE->get_renderer('local_dashboardv2');

// Output page
echo $OUTPUT->header();

if (in_array($current_role, ['spoc', 'area_manager'])) {
    // Create and render dashboard page
    $dashboard_page = new dashboard_page(
        $current_role,
        $user_hierarchy,
        $area_managers,
        $nutrition_officers,
        $users_data,
        $selected_area_manager,
        $selected_nutrition_officer,
        $selected_course,

        $selected_feedback
    );

    echo $renderer->render_dashboard_page($dashboard_page);
} else {
    // Render access denied
    echo $renderer->render_access_denied($current_role);
}

echo $OUTPUT->footer();