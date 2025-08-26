<?php
// download_report.php - Place in plugin root directory

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;

// Require login and set up security
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

// Get parameters
$report_type = required_param('report_type', PARAM_ALPHA);
$selected_area_manager = optional_param('selected_area_manager', '', PARAM_TEXT);
$selected_nutrition_officer = optional_param('selected_nutrition_officer', '', PARAM_TEXT);

// Validate report type
$valid_report_types = ['newregistrations', 'courseenrolments', 'coursecompletions', 'activeusers', 'inactiveusers'];
if (!in_array($report_type, $valid_report_types)) {
    print_error('invalidreporttype', 'local_dashboardv2');
}

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

$report_data = [];

// Load data based on user role and selection
switch ($current_role) {
    case 'spoc':
        if ($user_hierarchy && $user_hierarchy->spoc) {
            if ($selected_area_manager) {
                $report_data = user_hierarchy::get_report_data($report_type, 'area_manager', $selected_area_manager);
                $filename = "{$report_type}_report_area_manager_{$selected_area_manager}_" . date('Y-m-d') . ".csv";
            } else {
                $report_data = user_hierarchy::get_report_data($report_type, 'spoc', $user_hierarchy->spoc);
                $filename = "{$report_type}_report_spoc_{$user_hierarchy->spoc}_" . date('Y-m-d') . ".csv";
            }
        }
        break;
        
    case 'area_manager':
        if ($selected_nutrition_officer) {
            $report_data = user_hierarchy::get_report_data($report_type, 'nutrition_officer', $selected_nutrition_officer);
            $filename = "{$report_type}_report_nutrition_officer_{$selected_nutrition_officer}_" . date('Y-m-d') . ".csv";
        } else {
            $report_data = user_hierarchy::get_report_data($report_type, 'area_manager', $USER->username);
            $filename = "{$report_type}_report_area_manager_{$USER->username}_" . date('Y-m-d') . ".csv";
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

// Base CSV headers
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
    'Registration Date',
    'First Access',
    'Last Access'
];

// Add report-specific headers
switch ($report_type) {
    case 'courseenrolments':
        $headers[] = 'Enrolment Count';
        $headers[] = 'Latest Enrolment Date';
        break;
        
    case 'coursecompletions':
        $headers[] = 'Completion Count';
        $headers[] = 'Latest Completion Date';
        $headers[] = 'Completion Progress';
        break;
        
    case 'activeusers':
        $headers[] = 'Activity Count (30 days)';
        break;
        
    case 'inactiveusers':
        $headers[] = 'Days Inactive';
        break;
}

// Add module headers
for ($i = 1; $i <= 7; $i++) {
    $headers[] = "Module {$i} (%)";
}

fputcsv($output, $headers);

// Write data rows
foreach ($report_data as $user) {
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
        $user->timecreated ? date('Y-m-d H:i:s', $user->timecreated) : '',
        $user->firstaccess ? date('Y-m-d H:i:s', $user->firstaccess) : '',
        $user->lastaccess ? date('Y-m-d H:i:s', $user->lastaccess) : ''
    ];
    
    // Add report-specific data
    switch ($report_type) {
        case 'courseenrolments':
            $row[] = $user->enrolment_date ? date('Y-m-d H:i:s', $user->enrolment_date) : '';
            break;
            
        case 'coursecompletions':
            $row[] = $user->completion_count ?? 0;
            $row[] = $user->completion_date ? date('Y-m-d H:i:s', $user->completion_date) : '';
            $row[] = $user->completion_progress ?? '0 completed';
            break;
            
        case 'activeusers':
            $row[] = $user->activity_count ?? 0;
            break;
            
        case 'inactiveusers':
            break;
    }
    
    // Add module completion data
    for ($i = 1; $i <= 7; $i++) {
        $row[] = $user->{"module_{$i}"} ?? 0;
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit;