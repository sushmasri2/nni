<?php
// ajax_get_feedback_analysis.php - Place in plugin root directory

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;

// Require login and set up security
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

// Set JSON header
header('Content-Type: application/json');

try {
    // Get parameters
    $action = optional_param('action', '', PARAM_ALPHA);
    $area_manager = optional_param('area_manager', '', PARAM_TEXT);
    $nutrition_officer = optional_param('nutrition_officer', '', PARAM_TEXT);
    $course_id = optional_param('course_id', 0, PARAM_INT);
    $feedback_id = optional_param('feedback_id', 0, PARAM_INT);
    
    if ($action !== 'get_feedback_analysis' || !$course_id || !$feedback_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameters',
            'data' => []
        ]);
        exit;
    }
    
    // Get current user role and hierarchy
    $current_role = user_hierarchy::get_current_user_role($USER->id);
    $user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);
    
    // Check permissions based on role
    switch ($current_role) {
        case 'spoc':
            require_capability('local/dashboardv2:viewspocdata', $context);
            break;
        case 'area_manager':
            require_capability('local/dashboardv2:viewareamanagerdata', $context);
            break;
        default:
            throw new Exception('Access denied');
    }
    
    // Determine the identifier and role type for the query
    $identifier = '';
    $role_type = '';
    
    switch ($current_role) {
        case 'spoc':
            if ($area_manager) {
                $identifier = $area_manager;
                $role_type = 'area_manager';
            } else {
                $identifier = $user_hierarchy->spoc;
                $role_type = 'spoc';
            }
            break;
            
        case 'area_manager':
            if ($nutrition_officer) {
                $identifier = $nutrition_officer;
                $role_type = 'nutrition_officer';
            } else {
                $identifier = $USER->username;
                $role_type = 'area_manager';
            }
            break;
    }
    
    if (!$identifier) {
        throw new Exception('Unable to determine user hierarchy');
    }
    
    // Get feedback analysis data
    $feedback_data = user_hierarchy::get_feedback_analysis(
        $feedback_id, 
        $role_type, 
        $identifier, 
        $course_id
    );
    
    // Format data for response
    $formatted_data = [];
    foreach ($feedback_data as $item) {
        $formatted_data[] = [
            'question' => $item->question,
            'excellent' => $item->excellent,
            'good' => $item->good,
            'average' => $item->average_score,
            'needs_improvement' => $item->needs_improvement,
            'avg_score' => $item->avg_score,
            'final_category' => $item->final_category
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback analysis loaded successfully',
        'data' => $formatted_data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}

exit;