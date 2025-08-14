<?php
// ajax_get_courses.php - Place in plugin root directory

require_once('../../config.php');

use local_dashboardv2\user_hierarchy;

require_login();
require_sesskey();

$context = \context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

header('Content-Type: application/json');

try {
    $selected_manager = required_param('manager', PARAM_TEXT);
    $current_role = user_hierarchy::get_current_user_role($USER->id);
    
    $courses = [];
    
    switch ($current_role) {
        case 'spoc':
            // Get courses for area manager
            $courses = user_hierarchy::get_courses_by_hierarchy(11, $selected_manager);
            break;
            
        case 'area_manager':
            // Get courses for nutrition officer
            $courses = user_hierarchy::get_courses_by_hierarchy(12, $selected_manager);
            break;
    }
    
    $formatted_courses = [];
    foreach ($courses as $course) {
        $formatted_courses[] = [
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_courses
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;