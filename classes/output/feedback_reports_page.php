<?php
namespace local_dashboardv2\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class feedback_reports_page implements renderable, templatable
{
    private $current_role;
    private $user_hierarchy;
    private $area_managers;
    private $nutrition_officers;
    private $courses;
    private $feedbacks;
    private $feedback_data;
    private $selected_area_manager;
    private $selected_nutrition_officer;
    private $selected_course;
    private $selected_feedback;

    public function __construct(
        $current_role,
        $user_hierarchy,
        $area_managers = [],
        $nutrition_officers = [],
        $courses = [],
        $feedbacks = [],
        $feedback_data = [],
        $selected_area_manager = '',
        $selected_nutrition_officer = '',
        $selected_course = 0,
        $selected_feedback = 0
    ) {
        $this->current_role = $current_role;
        $this->user_hierarchy = $user_hierarchy;
        $this->area_managers = $area_managers;
        $this->nutrition_officers = $nutrition_officers;
        $this->courses = $courses;
        $this->feedbacks = $feedbacks;
        $this->feedback_data = $feedback_data;
        $this->selected_area_manager = $selected_area_manager;
        $this->selected_nutrition_officer = $selected_nutrition_officer;
        $this->selected_course = $selected_course;
        $this->selected_feedback = $selected_feedback;
    }

    public function export_for_template(renderer_base $output)
    {
        global $USER;

        $data = new stdClass();
        $data->current_role = $this->current_role;
        $data->user_fullname = fullname($USER);
        $data->role_display = ucfirst($this->current_role ?: 'No Role Assigned');
        
        // Back to dashboard URL
        $data->dashboard_url = new \moodle_url('/local/dashboardv2/');

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

        // Convert courses to template format
        $data->courses = [];
        foreach ($this->courses as $course) {
            $data->courses[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'selected' => ($this->selected_course == $course->id)
            ];
        }

        // Convert feedbacks to template format
        $data->feedbacks = [];
        foreach ($this->feedbacks as $feedback) {
            $data->feedbacks[] = [
                'id' => $feedback->id,
                'name' => $feedback->name,
                'selected' => ($this->selected_feedback == $feedback->id)
            ];
        }

        // Format feedback report data
        $data->feedback_data = [];
        foreach ($this->feedback_data as $item) {
            $data->feedback_data[] = [
                'id' => $item->id,
                'question' => $item->question,
                'excellent' => $item->excellent,
                'good' => $item->good,
                'average' => $item->average,
                'needs_improvement' => $item->needs_improvement,
                'avg_score' => round($item->avg_score, 2),
                'final_category' => $item->final_category,
                'total_responses' => $item->excellent + $item->good + $item->average + $item->needs_improvement
            ];
        }

        // Set role-specific data
        $data->is_spoc = ($this->current_role === 'spoc');
        $data->is_area_manager = ($this->current_role === 'area_manager');

        // Set hierarchy data
        if ($this->user_hierarchy) {
            $data->spoc_name = $this->user_hierarchy->spoc ?? '';
            $data->area_manager_name = $this->user_hierarchy->area_manager ?? '';
            $data->user_region = $this->user_hierarchy->spoc ?? $USER->username;
        }

        // Set selection data
        $data->selected_area_manager = $this->selected_area_manager;
        $data->selected_nutrition_officer = $this->selected_nutrition_officer;
        $data->selected_course = $this->selected_course;
        $data->selected_feedback = $this->selected_feedback;

        // Set data availability flags
        $data->has_area_managers = !empty($this->area_managers);
        $data->has_nutrition_officers = !empty($this->nutrition_officers);
        $data->has_courses = !empty($this->courses);
        $data->has_feedbacks = !empty($this->feedbacks);
        $data->has_feedback_data = !empty($this->feedback_data);

        // Get selected course name
        $data->selected_course_name = '';
        if ($this->selected_course) {
            foreach ($this->courses as $course) {
                if ($course->id == $this->selected_course) {
                    $data->selected_course_name = $course->fullname;
                    break;
                }
            }
        }

        // Get selected feedback name
        $data->selected_feedback_name = '';
        if ($this->selected_feedback) {
            foreach ($this->feedbacks as $feedback) {
                if ($feedback->id == $this->selected_feedback) {
                    $data->selected_feedback_name = $feedback->name;
                    break;
                }
            }
        }

        return $data;
    }
}