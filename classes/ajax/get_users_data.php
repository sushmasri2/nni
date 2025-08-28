<?php
// classes/ajax/get_users_data.php - Properly structured AJAX endpoint

namespace local_dashboardv2\ajax;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

use local_dashboardv2\user_hierarchy;

class get_users_data
{

    public static function execute()
    {
        global $USER, $CFG;

        // Require login and set up security
        require_login();
        require_sesskey();

        $context = \context_system::instance();
        require_capability('local/dashboardv2:viewdashboard', $context);

        // Set JSON header
        header('Content-Type: application/json');

        try {
            // Get parameters - Add pagination parameters
            $area_manager = optional_param('area_manager', '', PARAM_TEXT);
            $nutrition_officer = optional_param('nutrition_officer', '', PARAM_TEXT);
            $page = optional_param('page', 0, PARAM_INT);
            $perpage = optional_param('perpage', 25, PARAM_INT);

            // Get current user role and hierarchy
            $current_role = user_hierarchy::get_current_user_role($USER->id);
            $user_hierarchy = user_hierarchy::get_current_user_hierarchy($USER->id);

            $users_data = [];
            $total_count = 0;

            // Load data based on user role and selection
            switch ($current_role) {
                case 'spoc':
                    require_capability('local/dashboardv2:viewspocdata', $context);

                    if ($area_manager) {
                        $users_data = user_hierarchy::get_users_under_area_manager_for_spoc($area_manager, $page, $perpage);
                        $total_count = user_hierarchy::count_users_under_area_manager_for_spoc($area_manager);
                    } else {
                        $users_data = user_hierarchy::get_all_users_under_spoc($user_hierarchy->spoc, $page, $perpage);
                        $total_count = user_hierarchy::count_users_under_spoc($user_hierarchy->spoc);
                    }
                    break;

                case 'area_manager':
                    require_capability('local/dashboardv2:viewareamanagerdata', $context);

                    if ($nutrition_officer) {
                        $users_data = user_hierarchy::get_users_under_nutrition_officer_for_area_manager($nutrition_officer, $page, $perpage);
                        $total_count = user_hierarchy::count_users_under_nutrition_officer_for_area_manager($nutrition_officer);
                    } else {
                        $users_data = user_hierarchy::get_all_users_under_area_manager($USER->username, $page, $perpage);
                        $total_count = user_hierarchy::count_users_under_area_manager($USER->username);
                    }
                    break;

                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Access denied',
                        'data' => [],
                        'total_count' => 0,
                        'pagination' => [
                            'page' => 0,
                            'perpage' => 0,
                            'total' => 0
                        ]
                    ]);
                    exit;
            }

            // Format data for response
            $formatted_data = [];
            foreach ($users_data as $user) {
                $formatted_data[] = [
                    'username' => $user->username,
                    'fullname' => fullname($user),
                    'email' => $user->email,
                    'spoc' => $user->spoc ?? '',
                    'regional_head' => $user->regional_head ?? '',
                    'area_manager' => $user->area_manager ?? '',
                    'nutrition_officer' => $user->nutrition_officer ?? '',
                    'module_1' => $user->module_1 ?? 0,
                    'module_2' => $user->module_2 ?? 0,
                    'module_3' => $user->module_3 ?? 0,
                    'module_4' => $user->module_4 ?? 0,
                    'module_5' => $user->module_5 ?? 0,
                    'module_6' => $user->module_6 ?? 0,
                    'module_7' => $user->module_7 ?? 0
                ];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Data loaded successfully',
                'data' => $formatted_data,
                'total_count' => $total_count,
                'pagination' => [
                    'page' => $page,
                    'perpage' => $perpage,
                    'total' => $total_count
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'total_count' => 0,
                'pagination' => [
                    'page' => 0,
                    'perpage' => 0,
                    'total' => 0
                ]
            ]);
        }

        exit;
    }
}