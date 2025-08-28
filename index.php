<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;
use local_dashboardv2\output\dashboard_page;
use local_dashboardv2\output\dashboard_report;

// Require login and set up page
require_login();
$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);
$PAGE->requires->css('/local/edwiserreports/styles/edwiserreports.min.css');
require_once('../edwiserreports/classes/output/renderable.php');
local_edwiserreports\utility::load_color_pallets();

$PAGE->set_context($context);
$PAGE->set_url('/local/dashboardv2/index.php');
$PAGE->set_title(get_string('dashboard', 'local_dashboardv2'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/dashboardv2/style/dashboard.css');

// Get current user role and hierarchy
$current_role = user_hierarchy::get_current_user_role($USER->id);
$user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

// Process form submissions and pagination parameters
$selected_area_manager = optional_param('selected_area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('selected_nutrition_officer', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

// Validate perpage values
$allowed_perpage = [10, 25, 50, 100];
if (!in_array($perpage, $allowed_perpage)) {
    $perpage = 25;
}

// Initialize data arrays
$area_managers = [];
$nutrition_officers = [];
$users_data = [];
$total_count = 0;

// Load data based on user role
switch ($current_role) {
    case 'spoc':
        if ($user_hierarchy && $user_hierarchy->spoc) {
            require_capability('local/dashboardv2:viewspocdata', $context);

            $area_managers = user_hierarchy::get_area_managers_by_region($user_hierarchy->spoc);

            if ($selected_area_manager) {
                $users_data = user_hierarchy::get_users_under_area_manager_for_spoc($selected_area_manager, $page, $perpage);
                $total_count = user_hierarchy::count_users_under_area_manager_for_spoc($selected_area_manager);
            } else {
                $users_data = user_hierarchy::get_all_users_under_spoc($user_hierarchy->spoc, $page, $perpage);
                $total_count = user_hierarchy::count_users_under_spoc($user_hierarchy->spoc);
            }
        }
        break;

    case 'area_manager':
        require_capability('local/dashboardv2:viewareamanagerdata', $context);

        $nutrition_officers = user_hierarchy::get_nutrition_officers_by_area_manager($USER->username);

        if ($selected_nutrition_officer) {
            $users_data = user_hierarchy::get_users_under_nutrition_officer_for_area_manager($selected_nutrition_officer, $page, $perpage);
            $total_count = user_hierarchy::count_users_under_nutrition_officer_for_area_manager($selected_nutrition_officer);
        } else {
            $users_data = user_hierarchy::get_all_users_under_area_manager($USER->username, $page, $perpage);
            $total_count = user_hierarchy::count_users_under_area_manager($USER->username);
        }
        break;

    default:
        // No access for other roles
        break;
}

// Calculate pagination info
$start_record = ($total_count > 0) ? ($page * $perpage) + 1 : 0;
$end_record = min(($page + 1) * $perpage, $total_count);

// Create pagination object
$baseurl = new moodle_url('/local/dashboardv2/index.php');
if ($selected_area_manager) {
    $baseurl->param('selected_area_manager', $selected_area_manager);
}
if ($selected_nutrition_officer) {
    $baseurl->param('selected_nutrition_officer', $selected_nutrition_officer);
}
$baseurl->param('perpage', $perpage);

$pagination = null;
if ($total_count > $perpage) {
    $pagination = new paging_bar($total_count, $page, $perpage, $baseurl);
}

// Initialize renderer
$renderer = $PAGE->get_renderer('local_dashboardv2');

// Output page
echo $OUTPUT->header();

if (in_array($current_role, ['spoc', 'area_manager'])) {

    $component = "local_edwiserreports";

    // Strings for js.
    local_edwiserreports_get_required_strings_for_js();

    // Load all js files from externaljs folder.
    foreach (scandir($CFG->dirroot . '/local/edwiserreports/externaljs/build/') as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) != 'js') {
            continue;
        }
        $PAGE->requires->js(new moodle_url('/local/edwiserreports/externaljs/build/' . $file));
    }
    $PAGE->requires->js_call_amd('local_edwiserreports/main', 'init');

    // Create and render dashboard page with pagination data
    $dashboard_page = new dashboard_page(
        $current_role,
        $user_hierarchy,
        $area_managers,
        $nutrition_officers,
        $users_data,
        $selected_area_manager,
        $selected_nutrition_officer,
        $pagination,
        $total_count,
        $perpage,
        $start_record,
        $end_record
    );

    $dashboardreport = new dashboard_report($current_role, $area_managers, $nutrition_officers);

    $renderable = new \local_edwiserreports\output\customedwiserreports_renderable();
    $output = $PAGE->get_renderer('local_edwiserreports')->render($renderable);
    $output .= html_writer::start_div('row', ['id' => 'wdm-edwiserreports']);
    $output .= $renderer->render_module_progress($dashboardreport);
    $output .= $renderer->render_quiz_progress($dashboardreport);
    $output .= html_writer::end_div();

    echo $renderer->render_dashboard_page($dashboard_page);
    echo $renderer->render_dashboard_feedbacks($dashboardreport);
    echo html_writer::div($output, 'local-edwiserreports mt-5');
} else {
    // Render access denied
    echo $renderer->render_access_denied($current_role);
}

echo $OUTPUT->footer();