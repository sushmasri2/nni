<?php
// ajax_get_feedbacks.php - Place in plugin root directory

require_once('../../config.php');

use local_dashboardv2\user_hierarchy;

require_login();
require_sesskey();

$context = \context_system::instance();
require_capability('local/dashboardv2:viewdashboard', $context);

header('Content-Type: application/json');

try {
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
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_feedbacks
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;