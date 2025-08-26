<?php
// download.php - Place in plugin root directory

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;

// Require login and set up security
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

// Get parameters
$selected_area_manager = optional_param('selected_area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('selected_nutrition_officer', '', PARAM_TEXT);

// Get current user role and hierarchy
$current_role = user_hierarchy::get_current_user_role($USER->id);
$user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

$users_data = [];

// Load data based on user role and selection
switch ($current_role) {
    case 'spoc':
        require_capability('local/dashboardv2:viewspocdata', $context);
        
        if ($selected_area_manager) {
            $users_data = user_hierarchy::get_users_under_area_manager_for_spoc($selected_area_manager);
            $filename = "users_under_" . $selected_area_manager . "_" . date('Y-m-d') . ".csv";
        } else {
            $users_data = user_hierarchy::get_all_users_under_spoc($user_hierarchy->spoc);
            $filename = "all_users_under_spoc_" . $user_hierarchy->spoc . "_" . date('Y-m-d') . ".csv";
        }
        break;
        
    case 'area_manager':
        require_capability('local/dashboardv2:viewareamanagerdata', $context);
        
        if ($selected_nutrition_officer) {
            $users_data = user_hierarchy::get_users_under_nutrition_officer_for_area_manager($selected_nutrition_officer);
            $filename = "users_under_" . $selected_nutrition_officer . "_" . date('Y-m-d') . ".csv";
        } else {
            $users_data = user_hierarchy::get_all_users_under_area_manager($USER->username);
            $filename = "all_users_under_area_manager_" . $USER->username . "_" . date('Y-m-d') . ".csv";
        }
        break;
        
    default:
        print_error('nopermissiontodownload', 'local_dashboardv2');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create file handle for output
$output = fopen('php://output', 'w');

// CSV headers
$headers = [
    'Username',
    'First Name', 
    'Last Name',
    'Full Name',
    'Email',
    'SPOC',
    'Regional Head',
    'Area Manager',
    'Nutrition Officer',
    'Module 1 (%)',
    'Module 2 (%)',
    'Module 3 (%)',
    'Module 4 (%)',
    'Module 5 (%)',
    'Module 6 (%)',
    'Module 7 (%)'
];

fputcsv($output, $headers);

// Write data rows
foreach ($users_data as $user) {
    $row = [
        $user->username,
        $user->firstname,
        $user->lastname,
        fullname($user),
        $user->email,
        $user->spoc ?? '',
        $user->regional_head ?? '',
        $user->area_manager ?? '',
        $user->nutrition_officer ?? '',
        $user->module_1 ?? 0,
        $user->module_2 ?? 0,
        $user->module_3 ?? 0,
        $user->module_4 ?? 0,
        $user->module_5 ?? 0,
        $user->module_6 ?? 0,
        $user->module_7 ?? 0
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;