<?php
// ajax_feedback_handler.php - Consolidated handler for all feedback operations

require_once('../../config.php');
use local_dashboardv2\user_hierarchy;

require_login();
require_sesskey();

$context = \context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

header('Content-Type: application/json');

try {
    $action = required_param('action', PARAM_ALPHA);
    $current_role = user_hierarchy::get_current_user_role($USER->id);
    
    switch ($action) {
        case 'get_courses':
            $selected_manager = required_param('manager', PARAM_TEXT);
            $courses = [];
            
            switch ($current_role) {
                case 'spoc':
                    $courses = user_hierarchy::get_courses_by_hierarchy(11, $selected_manager);
                    break;
                case 'area_manager':
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
            
            echo json_encode(['success' => true, 'data' => $formatted_courses]);
            break;
            
        case 'get_feedbacks':
            $course_id = required_param('courseid', PARAM_INT);
            $feedbacks = user_hierarchy::get_course_feedbacks($course_id);
            
            $formatted_feedbacks = [];
            foreach ($feedbacks as $feedback) {
                $formatted_feedbacks[] = [
                    'id' => $feedback->id,
                    'name' => $feedback->name,
                    'intro' => strip_tags($feedback->intro),
                    'cmid' => $feedback->cmid
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $formatted_feedbacks]);
            break;
            
        case 'get_responses':
            $selected_manager = required_param('manager', PARAM_TEXT);
            $feedback_id = required_param('feedback_id', PARAM_INT);
            $responses = [];
            
            switch ($current_role) {
                case 'spoc':
                    require_capability('local/dashboardv2:viewspocdata', $context);
                    $responses = user_hierarchy::get_feedback_responses($feedback_id, 11, $selected_manager);
                    break;
                case 'area_manager':
                    require_capability('local/dashboardv2:viewareamanagerdata', $context);
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
            
            echo json_encode(['success' => true, 'data' => $formatted_responses]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;