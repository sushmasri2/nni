<?php
namespace local_dashboardv2\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;
use local_dashboardv2\user_hierarchy;

class dashboard_page implements renderable, templatable
{

    private $current_role;
    private $user_hierarchy;
    private $area_managers;
    private $nutrition_officers;
    private $users_data;
    private $selected_area_manager;
    private $selected_nutrition_officer;
    private $insights;
    private $available_courses;
    private $available_feedbacks;
    private $selected_course;
    private $selected_feedback;
    private $feedback_analysis_data;
    private function getCountBasedOnRole($insight_name, $role, $hierarchy_instance)
    {
        switch ($insight_name) {
            case 'newregistrations':
                if ($role == 'spoc')
                    return $hierarchy_instance->spoccount_newregistrations($this->user_hierarchy->spoc);
                if ($role == 'area_manager')
                    return $hierarchy_instance->area_managercount_newregistrations($this->user_hierarchy->area_manager);
                return 0;

            case 'courseenrolments':
                if ($role == 'spoc')
                    return $hierarchy_instance->spoccount_courseenrolments($this->user_hierarchy->spoc);
                if ($role == 'area_manager')
                    return $hierarchy_instance->area_managercount_courseenrolments($this->user_hierarchy->area_manager);
                return 0;

            case 'coursecompletions':
                if ($role == 'spoc')
                    return $hierarchy_instance->spoccount_coursecompletions($this->user_hierarchy->spoc);
                if ($role == 'area_manager')
                    return $hierarchy_instance->area_managercount_coursecompletions($this->user_hierarchy->area_manager);
                return 0;

            case 'activeusers':
                if ($role == 'spoc')
                    return $hierarchy_instance->spoccount_activeusers($this->user_hierarchy->spoc);
                if ($role == 'area_manager')
                    return $hierarchy_instance->area_managercount_activeusers($this->user_hierarchy->area_manager);
                return 0;

            case 'inactiveusers':
                if ($role == 'spoc')
                    return $hierarchy_instance->spoccount_inactiveusers($this->user_hierarchy->spoc);
                if ($role == 'area_manager')
                    return $hierarchy_instance->area_managercount_inactiveusers($this->user_hierarchy->area_manager);
                return 0;

            default:
                return 0;
        }
    }

    public function __construct(
        $current_role,
        $user_hierarchy,
        $area_managers = [],
        $nutrition_officers = [],
        $users_data = [],
        $selected_area_manager = '',
        $selected_nutrition_officer = '',
        $selected_course = '',
        $selected_feedback = '',
    ) {
        $this->current_role = $current_role;
        $this->user_hierarchy = $user_hierarchy;
        $this->area_managers = $area_managers;
        $this->nutrition_officers = $nutrition_officers;
        $this->users_data = $users_data;
        $this->selected_area_manager = $selected_area_manager;
        $this->selected_nutrition_officer = $selected_nutrition_officer;
        $this->selected_course = $selected_course;
        $this->selected_feedback = $selected_feedback;
        $user_hierarchy_instance = new user_hierarchy();
        $this->available_courses = user_hierarchy::get_available_courses_for_feedback();
        $this->available_feedbacks = user_hierarchy::get_feedback_forms_by_course($selected_course);
        $this->insights = array(
            'newregistrations' => array(
                'icon' => (new \moodle_url('/local/edwiserreports/pix/registration.svg'))->out(),
                'title' => get_string('newregistrations', 'local_dashboardv2'),
                'class' => 'newregistrations',
                'internal' => true,
                'count' => $this->getCountBasedOnRole('newregistrations', $current_role, $user_hierarchy_instance),
            ),
            'courseenrolments' => array(
                'icon' => (new \moodle_url('/local/edwiserreports/pix/enrolment.svg'))->out(),
                'title' => get_string('courseenrolments', 'local_dashboardv2'),
                'class' => 'courseenrolments',
                'internal' => true,
                'count' => $this->getCountBasedOnRole('courseenrolments', $current_role, $user_hierarchy_instance),

            ),
            'coursecompletions' => array(
                'icon' => (new \moodle_url('/local/edwiserreports/pix/coursecompletion.svg'))->out(),
                'title' => get_string('coursecompletions', 'local_dashboardv2'),
                'class' => 'coursecompletions',
                'internal' => true,
                'count' => $this->getCountBasedOnRole('coursecompletions', $current_role, $user_hierarchy_instance),

            ),
            'activeusers' => array(
                'icon' => (new \moodle_url('/local/edwiserreports/pix/activeusers.svg'))->out(),
                'title' => get_string('activeusers', 'local_dashboardv2'),
                'class' => 'activeusers',
                'internal' => true,
                'count' => $this->getCountBasedOnRole('activeusers', $current_role, $user_hierarchy_instance),

            ),
            'inactiveusers' => array(
                'icon' => (new \moodle_url('/local/edwiserreports/pix/inactiveusers.svg'))->out(),
                'title' => get_string('inactiveusers', 'local_dashboardv2'),
                'class' => 'inactiveusers',
                'internal' => true,
                'count' => $this->getCountBasedOnRole('inactiveusers', $current_role, $user_hierarchy_instance),

            ),
        );
        if ($selected_course && $selected_feedback && $current_role) {
            $identifier = '';
            if ($current_role === 'spoc' && $user_hierarchy->spoc) {
                $identifier = $selected_area_manager ?: $user_hierarchy->spoc;
                $role_type = $selected_area_manager ? 'area_manager' : 'spoc';
            } elseif ($current_role === 'area_manager') {
                $identifier = $selected_nutrition_officer ?: $user_hierarchy->area_manager;
                $role_type = $selected_nutrition_officer ? 'nutrition_officer' : 'area_manager';
            }

            if ($identifier) {
                $this->feedback_analysis_data = user_hierarchy::get_feedback_analysis(
                    $selected_feedback,
                    $role_type,
                    $identifier,
                    $selected_course
                );
            }
        }
    }
    public function export_for_template(renderer_base $output)
    {
        global $USER;

        $data = new stdClass();
        $data->current_role = $this->current_role;
        $data->user_fullname = fullname($USER);
        $data->role_display = ucfirst($this->current_role ?: 'No Role Assigned');

        // Convert area managers to template format
        $data->area_managers = [];
        foreach ($this->area_managers as $am) {
            $data->area_managers[] = [
                'username' => $am->username,
                'fullname' => fullname($am),
                'selected' => ($this->selected_area_manager === $am->username)
            ];
        }

        // Convert nutrition officers to template format
        $data->nutrition_officers = [];
        foreach ($this->nutrition_officers as $no) {
            $data->nutrition_officers[] = [
                'username' => $no->username,
                'fullname' => fullname($no),
                'selected' => ($this->selected_nutrition_officer === $no->username)
            ];
        }

        // Convert users data to template format
        $data->users_data = [];
        foreach ($this->users_data as $user) {
            $data->users_data[] = [
                'username' => $user->username,
                'fullname' => fullname($user),
                'email' => $user->email,
                'spoc' => $user->spoc ?? '-',
                'regional_head' => $user->regional_head ?? '-',
                'area_manager' => $user->area_manager ?? '-',
                'nutrition_officer' => $user->nutrition_officer ?? '-',
                'module_1' => $user->module_1 ?? '0/0',
                'module_2' => $user->module_2 ?? '0/0',
                'module_3' => $user->module_3 ?? '0/0',
                'module_4' => $user->module_4 ?? '0/0',
                'module_5' => $user->module_5 ?? '0/0',
                'module_6' => $user->module_6 ?? '0/0',
                'module_7' => $user->module_7 ?? '0/0'
            ];
        }

        // Set role-specific data
        $data->is_spoc = ($this->current_role === 'spoc');
        $data->is_area_manager = ($this->current_role === 'area_manager');
        $data->has_access = in_array($this->current_role, ['spoc', 'area_manager']);

        // Set hierarchy data
        if ($this->user_hierarchy) {
            $data->spoc_name = $this->user_hierarchy->spoc ?? '';
            $data->area_manager_name = $this->user_hierarchy->area_manager ?? '';
            $data->user_region = $this->user_hierarchy->spoc ?? $USER->username;
        }

        // Set selection data
        $data->selected_area_manager = $this->selected_area_manager;
        $data->selected_nutrition_officer = $this->selected_nutrition_officer;

        // Set data availability flags
        $data->has_area_managers = !empty($this->area_managers);
        $data->has_nutrition_officers = !empty($this->nutrition_officers);
        $data->has_users_data = !empty($this->users_data);
        $data->insights = array_values($this->insights);
        $data->available_courses = [];
        foreach ($this->available_courses as $course) {
            $data->available_courses[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'selected' => ($this->selected_course == $course->id)
            ];
        }

        $data->available_feedbacks = [];
        foreach ($this->available_feedbacks as $feedback) {
            $data->available_feedbacks[] = [
                'id' => $feedback->id,
                'name' => $feedback->name,
                'course_name' => $feedback->course_name,
                'selected' => ($this->selected_feedback == $feedback->id)
            ];
        }

        // Add feedback analysis data
        $data->feedback_analysis_data = [];
        if ($this->feedback_analysis_data) {
            foreach ($this->feedback_analysis_data as $feedback_item) {
                $data->feedback_analysis_data[] = [
                    'question' => $feedback_item->question,
                    'excellent' => $feedback_item->excellent,
                    'good' => $feedback_item->good,
                    'average' => $feedback_item->average_score,
                    'needs_improvement' => $feedback_item->needs_improvement,
                    'avg_score' => $feedback_item->avg_score,
                    'final_category' => $feedback_item->final_category
                ];
            }
        }

        $data->selected_course = $this->selected_course;
        $data->selected_feedback = $this->selected_feedback;
        $data->has_available_courses = !empty($this->available_courses);
        $data->has_available_feedbacks = !empty($this->available_feedbacks);
        $data->has_feedback_analysis = !empty($this->feedback_analysis_data);
        return $data;
    }

}