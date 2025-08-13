<?php
namespace local_dashboardv2\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class reports_page implements renderable, templatable
{
    private $report_type;
    private $current_role;
    private $user_hierarchy;
    private $area_managers;
    private $nutrition_officers;
    private $report_data;
    private $selected_area_manager;
    private $selected_nutrition_officer;

    public function __construct(
        $report_type,
        $current_role,
        $user_hierarchy,
        $area_managers = [],
        $nutrition_officers = [],
        $report_data = [],
        $selected_area_manager = '',
        $selected_nutrition_officer = ''
    ) {
        $this->report_type = $report_type;
        $this->current_role = $current_role;
        $this->user_hierarchy = $user_hierarchy;
        $this->area_managers = $area_managers;
        $this->nutrition_officers = $nutrition_officers;
        $this->report_data = $report_data;
        $this->selected_area_manager = $selected_area_manager;
        $this->selected_nutrition_officer = $selected_nutrition_officer;
    }

    public function export_for_template(renderer_base $output)
    {
        global $USER;

        $data = new stdClass();
        $data->report_type = $this->report_type;
        $data->report_title = get_string($this->report_type, 'local_dashboardv2');
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

        // Format report data based on report type
        $data->report_data = [];
        foreach ($this->report_data as $user) {
            $user_data = [
                'username' => $user->username,
                'fullname' => fullname($user),
                'email' => $user->email,
                'spoc' => $user->spoc ?? '-',
                'regional_head' => $user->regional_head ?? '-',
                'area_manager' => $user->area_manager ?? '-',
                'nutrition_officer' => $user->nutrition_officer ?? '-',
                'firstaccess' => $user->firstaccess ? userdate($user->firstaccess) : '-',
                'lastaccess' => $user->lastaccess ? userdate($user->lastaccess) : '-',
                'timecreated' => $user->timecreated ? userdate($user->timecreated) : '-'
            ];

            // Add report-specific data
            switch ($this->report_type) {
                case 'courseenrolments':
                    $user_data['enrolment_count'] = $user->enrolment_count ?? 0;
                    $user_data['enrolment_date'] = $user->enrolment_date ? userdate($user->enrolment_date) : '-';
                    break;
                    
                case 'coursecompletions':
                    $user_data['completion_count'] = $user->completion_count ?? 0;
                    $user_data['completion_date'] = $user->completion_date ? userdate($user->completion_date) : '-';
                    $user_data['completion_progress'] = $user->completion_progress ?? '0%';
                    break;
                    
                case 'activeusers':
                    $user_data['last_login'] = $user->lastaccess ? userdate($user->lastaccess) : '-';
                    $user_data['activity_count'] = $user->activity_count ?? 0;
                    break;
                    
                case 'inactiveusers':
                    $user_data['days_inactive'] = $user->days_inactive ?? 0;
                    $user_data['last_activity'] = $user->lastaccess ? userdate($user->lastaccess) : get_string('never');
                    break;
            }

            // Add module completion data
            for ($i = 1; $i <= 7; $i++) {
                $user_data["module_{$i}"] = $user->{"module_{$i}"} ?? 0;
            }

            $data->report_data[] = $user_data;
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

        // Set data availability flags
        $data->has_area_managers = !empty($this->area_managers);
        $data->has_nutrition_officers = !empty($this->nutrition_officers);
        $data->has_report_data = !empty($this->report_data);
        $data->total_records = count($this->report_data);

        // Report type specific flags
        $data->is_newregistrations = ($this->report_type === 'newregistrations');
        $data->is_courseenrolments = ($this->report_type === 'courseenrolments');
        $data->is_coursecompletions = ($this->report_type === 'coursecompletions');
        $data->is_activeusers = ($this->report_type === 'activeusers');
        $data->is_inactiveusers = ($this->report_type === 'inactiveusers');

        return $data;
    }
}