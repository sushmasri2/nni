<?php
// ajax_get_feedback_forms.php - Place in plugin root directory

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
    $course_id = optional_param('course_id', 0, PARAM_INT);
    
    if ($action !== 'get_feedback_forms' || !$course_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameters',
            'feedbacks' => []
        ]);
        exit;
    }
    
    // Get feedback forms for the course
    $feedbacks = user_hierarchy::get_feedback_forms_by_course($course_id);
    
    // Format data for response
    $formatted_feedbacks = [];
    foreach ($feedbacks as $feedback) {
        $formatted_feedbacks[] = [
            'id' => $feedback->id,
            'name' => $feedback->name,
            'course' => $feedback->course,
            'course_name' => $feedback->course_name
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback forms loaded successfully',
        'feedbacks' => $formatted_feedbacks
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'feedbacks' => []
    ]);
}

exit;