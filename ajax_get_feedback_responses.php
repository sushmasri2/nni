<?php
// ajax_get_feedback_responses.php - Place in plugin root directory

require_once('../../config.php');

use local_dashboardv2\user_hierarchy;

require_login();
require_sesskey();

$context = \context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

header('Content-Type: application/json');

try {
    $selected_manager = required_param('manager', PARAM_TEXT);
    $feedback_id = required_param('feedback_id', PARAM_INT);
    $current_role = user_hierarchy::get_current_user_role($USER->id);
    
    $responses = [];
    
    switch ($current_role) {
        case 'spoc':
            require_capability('local/dashboardv2:viewspocdata', $context);
            // Get responses for users under area manager
            $responses = user_hierarchy::get_feedback_responses($feedback_id, 11, $selected_manager);
            break;
            
        case 'area_manager':
            require_capability('local/dashboardv2:viewareamanagerdata', $context);
            // Get responses for users under nutrition officer
            $responses = user_hierarchy::get_feedback_responses($feedback_id, 12, $selected_manager);
            break;
    }
    
    $formatted_responses = [];
    foreach ($responses as $user) {
        $formatted_responses[] = [
            'username' => $user->username,
            'fullname' => fullname($user),
            'email' => $user->email,
            'spoc' => $user->spoc ?? '',
            'area_manager' => $user->area_manager ?? '',
            'nutrition_officer' => $user->nutrition_officer ?? '',
            'response_date' => $user->response_date ? userdate($user->response_date) : ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_responses
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;