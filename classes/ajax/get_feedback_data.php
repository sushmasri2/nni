<?php 
class get_feedback_data {
    
    public static function execute() {
        global $USER;
        
        require_login();
        require_sesskey();
        
        $context = \context_system::instance();
        require_capability('local/dashboardv2:viewdashboard', $context);
        
        header('Content-Type: application/json');
        
        try {
            $course_id = optional_param('course_id', 0, PARAM_INT);
            $feedback_id = optional_param('feedback_id', 0, PARAM_INT);
            $area_manager = optional_param('area_manager', '', PARAM_TEXT);
            $nutrition_officer = optional_param('nutrition_officer', '', PARAM_TEXT);
            
            $current_role = user_hierarchy::get_current_user_role($USER->id);
            $user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);
            
            $response_data = [
                'feedback_list' => [],
                'feedback_data' => []
            ];
            
            // If course is selected, get feedback list
            if ($course_id > 0) {
                switch ($current_role) {
                    case 'spoc':
                        require_capability('local/dashboardv2:viewspocdata', $context);
                        $feedback_list = user_hierarchy::get_feedback_list(
                            $course_id,
                            'spoc', 
                            $user_hierarchy->spoc, 
                            $area_manager,
                            ''
                        );
                        break;
                        
                    case 'area_manager':
                        require_capability('local/dashboardv2:viewareamanagerdata', $context);
                        $feedback_list = user_hierarchy::get_feedback_list(
                            $course_id,
                            'area_manager', 
                            $USER->username, 
                            '',
                            $nutrition_officer
                        );
                        break;
                }
                
                foreach ($feedback_list as $feedback) {
                    $response_data['feedback_list'][] = [
                        'id' => $feedback->id,
                        'name' => $feedback->name
                    ];
                }
            }
            
            // If feedback is selected, get feedback data
            if ($feedback_id > 0) {
                switch ($current_role) {
                    case 'spoc':
                        $feedback_data = user_hierarchy::get_feedback_data(
                            'spoc', 
                            $user_hierarchy->spoc, 
                            $course_id,
                            $feedback_id,
                            $area_manager,
                            ''
                        );
                        break;
                        
                    case 'area_manager':
                        $feedback_data = user_hierarchy::get_feedback_data(
                            'area_manager', 
                            $USER->username, 
                            $course_id,
                            $feedback_id,
                            '',
                            $nutrition_officer
                        );
                        break;
                }
                
                foreach ($feedback_data as $feedback) {
                    $response_data['feedback_data'][] = [
                        'id' => $feedback->id,
                        'question' => $feedback->question,
                        'excellent' => $feedback->excellent ?? 0,
                        'good' => $feedback->good ?? 0,
                        'average' => $feedback->average ?? 0,
                        'needs_improvement' => $feedback->needs_improvement ?? 0,
                        'avg_score' => round($feedback->avg_score ?? 0, 2),
                        'final_category' => $feedback->final_category ?? 'No Data',
                        'total_responses' => $feedback->total_responses ?? 0
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $response_data
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
        
        exit;
    }
}