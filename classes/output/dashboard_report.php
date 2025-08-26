<?php

namespace local_dashboardv2\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class dashboard_report implements renderable, templatable
{

    private $current_role;

    private $area_managers;

    private $nutrition_officers;


    public function __construct($current_role, $area_managers = [], $nutrition_officers = [])
    {
        $this->current_role = $current_role;
        $this->area_managers = $area_managers;
        $this->nutrition_officers = $nutrition_officers;
    }

    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->current_role = $this->current_role;

        $data->search_optoins = [];
        if ($data->is_spoc = ($this->current_role === 'spoc')) {
            $data->search_label = 'Select area manager';
            $data->data_selector = 'area_manager';

            foreach ($this->area_managers as $am) {
                $data->search_optoins[] = [
                    'username' => $am->username,
                    'name' => fullname($am),
                ];
            }
        } elseif ($data->is_area_manager = ($this->current_role === 'area_manager')) {
            $data->search_label = 'Select nutrition officer';
            $data->data_selector = 'nutrition_officer';
            foreach ($this->nutrition_officers as $no) {
                $data->search_optoins[] = [
                    'username' => $no->username,
                    'name' => fullname($no),
                ];
            }
        }

        $data->courselist = [];
        $courses = get_courses();
        foreach ($courses as $course) {
            if ($course->id == SITEID || $course->visible == 0) {
                continue;
            }
            $data->courselist[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
            ];
        }
        return $data;
    }
}
