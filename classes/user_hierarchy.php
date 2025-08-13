<?php
namespace local_dashboardv2;

defined('MOODLE_INTERNAL') || die();

class user_hierarchy
{
    // ... existing methods remain the same ...

    /**
     * Get current user's custom field data
     */
    public static function get_current_user_hierarchy($userid)
    {
        global $DB;
        return $DB->get_record_sql("
            SELECT u.username,
                MAX(CASE WHEN ud.fieldid = 8 THEN ud.data END) AS spoc,
                MAX(CASE WHEN ud.fieldid = 11 THEN ud.data END) AS area_manager,
                MAX(CASE WHEN ud.fieldid = 12 THEN ud.data END) AS nutrition_officer,
                MAX(CASE WHEN ud.fieldid = 13 THEN ud.data END) AS regional_head
            FROM {user} u
            LEFT JOIN {user_info_data} ud ON ud.userid = u.id AND ud.fieldid IN (8, 11, 12, 13)
            WHERE u.id = ?
            GROUP BY u.id, u.username  ORDER BY u.username", [$userid]);
    }

    /**
     * Get Area Managers under a SPOC
     */
    public static function get_area_managers_by_region($spoc)
    {
        global $DB;
        return $DB->get_records_sql("
            SELECT DISTINCT u.id, u.username, u.firstname, u.lastname,
                MAX(CASE WHEN ud.fieldid = 11 THEN ud.data END) AS area_manager_name
            FROM {user} u
            LEFT JOIN {user_info_data} ud ON ud.userid = u.id AND ud.fieldid = 11
            LEFT JOIN {user_info_data} ud2 ON ud2.userid = u.id AND ud2.fieldid = 8
            LEFT JOIN {user_info_data} ud3 ON ud3.userid = u.id AND ud3.fieldid = 12
            WHERE ud2.data = ? AND ud.data IS NOT NULL AND ud.data != '' 
            AND ud.data = u.username AND (ud3.data IS NULL OR ud3.data = '')
            GROUP BY u.id, u.username, u.firstname, u.lastname
            ORDER BY u.username", [$spoc]);
    }

    /**
     * Get Nutrition Officers under an Area Manager
     */
    public static function get_nutrition_officers_by_area_manager($area_manager)
    {
        global $DB;
        return $DB->get_records_sql("
            SELECT DISTINCT u.id, u.username, u.firstname, u.lastname,
                MAX(CASE WHEN ud.fieldid = 10 THEN ud.data END) AS nutrition_officer_name
            FROM {user} u
            LEFT JOIN {user_info_data} ud ON ud.userid = u.id AND ud.fieldid = 12
            LEFT JOIN {user_info_data} ud2 ON ud2.userid = u.id AND ud2.fieldid = 11
            WHERE ud2.data = ? AND ud.data IS NOT NULL AND ud.data != ''
            AND ud.data = u.username
            GROUP BY u.id, u.username, u.firstname, u.lastname
            ORDER BY u.username", [$area_manager]);
    }

    /**
     * Helper method to build module completion columns
     */
    private static function get_module_completion_columns()
    {
        $modules = [
            1 => [3, 5, 6, 7],
            2 => [8, 10, 11, 12],
            3 => [13, 15, 16, 17],
            4 => [18, 20, 21, 22],
            5 => [23, 25, 26, 27],
            6 => [28, 30, 31, 32],
            7 => [33, 35, 36, 37]
        ];

        $columns = [];
        foreach ($modules as $num => $sections) {
            $section_list = implode(',', $sections);
            $columns[] = "ROUND((COUNT(CASE WHEN cs.section IN ({$section_list}) THEN cmc.id END) * 100.0) / (SELECT COUNT(*) FROM {course_modules} cm2 JOIN {course_sections} cs2 ON cm2.section = cs2.id WHERE cs2.section IN ({$section_list}) AND cs2.course = 6), 2) AS module_{$num}";
        }
        return implode(",\n                       ", $columns);
    }

    /**
     * Generic method to get users with completion data based on hierarchy field
     */
    private static function get_users_with_completion($field_id, $value, $exclude_self = true)
    {
        global $DB;
        $self_condition = $exclude_self ? "AND u.username != ud{$field_id}.data" : "";

        return $DB->get_records_sql("
            SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email,
                   ud8.data AS spoc, ud11.data AS area_manager, ud12.data AS nutrition_officer, ud13.data AS regional_head,
                   " . self::get_module_completion_columns() . "
            FROM {user} u
            LEFT JOIN {user_info_data} ud8 ON ud8.userid = u.id AND ud8.fieldid = 8
            LEFT JOIN {user_info_data} ud11 ON ud11.userid = u.id AND ud11.fieldid = 11
            LEFT JOIN {user_info_data} ud12 ON ud12.userid = u.id AND ud12.fieldid = 12
            LEFT JOIN {user_info_data} ud13 ON ud13.userid = u.id AND ud13.fieldid = 13
            LEFT JOIN {role_assignments} ra ON ra.userid = u.id
            LEFT JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.completionstate = 1
            LEFT JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
            LEFT JOIN {course_sections} cs ON cm.section = cs.id
            WHERE u.deleted = 0 AND ud{$field_id}.data = ? {$self_condition} AND ra.roleid = 5 
            AND (cs.course = 6 OR cs.course IS NULL)
            GROUP BY u.id, u.username, u.firstname, u.lastname, u.email, ud8.data, ud11.data, ud12.data, ud13.data
            ORDER BY u.username", [$value]);
    }

    /**
     * Get all users under SPOC with completion data
     */
    public static function get_all_users_under_spoc($spoc)
    {
        return self::get_users_with_completion(8, $spoc);
    }

    /**
     * Get users under specific area manager for SPOC
     */
    public static function get_users_under_area_manager_for_spoc($area_manager_username)
    {
        return self::get_users_with_completion(11, $area_manager_username);
    }

    /**
     * Get all users under Area Manager
     */
    public static function get_all_users_under_area_manager($area_manager)
    {
        return self::get_users_with_completion(11, $area_manager);
    }

    /**
     * Get users under Nutrition Officer for Area Manager
     */
    public static function get_users_under_nutrition_officer_for_area_manager($nutrition_officer_username)
    {
        return self::get_users_with_completion(12, $nutrition_officer_username);
    }

    /**
     * Get user's current role based on hierarchy
     */
    public static function get_current_user_role($userid)
    {
        $context = \context_system::instance();
        $roles = get_user_roles($context, $userid);
        $role_priorities = ['spoc', 'area_manager', 'nutritionofficer', 'learner'];

        foreach ($roles as $role) {
            if (in_array($role->shortname, $role_priorities)) {
                return $role->shortname;
            }
        }
        return '';
    }

    /**
     * Generic count method for different metrics
     */
    private static function get_count($field_id, $value, $metric)
    {
        global $DB;
        $exclude_self = "AND u.username != ud{$field_id}.data";

        $joins = [
            'new_registrations' => '',
            'course_enrolments' => 'JOIN {user_enrolments} ue ON ue.userid = u.id JOIN {enrol} e ON e.id = ue.enrolid',
            'course_completions' => 'JOIN {course_completions} cc ON cc.userid = u.id',
            'active_users' => '',
            'inactive_users' => ''
        ];

        $conditions = [
            'new_registrations' => '',
            'course_enrolments' => '',
            'course_completions' => 'AND cc.timecompleted IS NOT NULL',
            'active_users' => 'AND u.lastaccess > 0',
            'inactive_users' => 'AND u.lastaccess = 0'
        ];

        $count_field = $metric === 'course_completions' ? 'cc.userid' :
            ($metric === 'course_enrolments' ? 'ue.userid' : 'u.id');

        return $DB->get_field_sql(
            "
            SELECT COUNT(DISTINCT {$count_field})
            FROM {user} u
            LEFT JOIN {user_info_data} ud{$field_id} ON ud{$field_id}.userid = u.id AND ud{$field_id}.fieldid = {$field_id}
            LEFT JOIN {role_assignments} ra ON ra.userid = u.id
            {$joins[$metric]}
            WHERE u.deleted = 0 AND ud{$field_id}.data = ? {$exclude_self} AND ra.roleid = 5 {$conditions[$metric]}",
            [$value]
        );
    }

    // SPOC count methods
    public static function spoccount_newregistrations($spoc)
    {
        return self::get_count(8, $spoc, 'new_registrations');
    }
    public function spoccount_courseenrolments($spoc)
    {
        return self::get_count(8, $spoc, 'course_enrolments');
    }
    public function spoccount_coursecompletions($spoc)
    {
        return self::get_count(8, $spoc, 'course_completions');
    }
    public function spoccount_activeusers($spoc)
    {
        return self::get_count(8, $spoc, 'active_users');
    }
    public function spoccount_inactiveusers($spoc)
    {
        return self::get_count(8, $spoc, 'inactive_users');
    }

    // Area Manager count methods
    public function area_managercount_newregistrations($area_manager)
    {
        return self::get_count(11, $area_manager, 'new_registrations');
    }
    public function area_managercount_courseenrolments($area_manager)
    {
        return self::get_count(11, $area_manager, 'course_enrolments');
    }
    public function area_managercount_coursecompletions($area_manager)
    {
        return self::get_count(11, $area_manager, 'course_completions');
    }
    public function area_managercount_activeusers($area_manager)
    {
        return self::get_count(11, $area_manager, 'active_users');
    }
    public function area_managercount_inactiveusers($area_manager)
    {
        return self::get_count(11, $area_manager, 'inactive_users');
    }

    /**
     * NEW: Get detailed report data for insights
     */
    public static function get_report_data($report_type, $role_type, $identifier)
    {
        global $DB;

        // Map role types to field IDs
        $field_mapping = [
            'spoc' => 8,
            'area_manager' => 11,
            'nutrition_officer' => 12
        ];

        if (!isset($field_mapping[$role_type])) {
            return [];
        }

        $field_id = $field_mapping[$role_type];
        $exclude_self = "AND u.username != ud{$field_id}.data";

        // Base query components
        $base_select = "
            SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email,
                   u.timecreated, u.firstaccess, u.lastaccess,
                   ud8.data AS spoc, ud11.data AS area_manager, 
                   ud12.data AS nutrition_officer, ud13.data AS regional_head,
                   " . self::get_module_completion_columns();

        $base_from = "
            FROM {user} u
            LEFT JOIN {user_info_data} ud8 ON ud8.userid = u.id AND ud8.fieldid = 8
            LEFT JOIN {user_info_data} ud11 ON ud11.userid = u.id AND ud11.fieldid = 11
            LEFT JOIN {user_info_data} ud12 ON ud12.userid = u.id AND ud12.fieldid = 12
            LEFT JOIN {user_info_data} ud13 ON ud13.userid = u.id AND ud13.fieldid = 13
            LEFT JOIN {role_assignments} ra ON ra.userid = u.id
            LEFT JOIN {course_modules_completion} cmc ON cmc.userid = u.id AND cmc.completionstate = 1
            LEFT JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
            LEFT JOIN {course_sections} cs ON cm.section = cs.id";

        $base_where = "
            WHERE u.deleted = 0 AND ud{$field_id}.data = ? {$exclude_self} AND ra.roleid = 5 
            AND (cs.course = 6 OR cs.course IS NULL)";

        $base_group = "
            GROUP BY u.id, u.username, u.firstname, u.lastname, u.email, 
                     u.timecreated, u.firstaccess, u.lastaccess,
                     ud8.data, ud11.data, ud12.data, ud13.data";

        $base_order = " ORDER BY u.username";

        // Customize query based on report type
        switch ($report_type) {
            case 'newregistrations':
                // Recent registrations (last 30 days)
                $additional_where = " AND u.timecreated > " . (time() - (30 * 24 * 60 * 60));
                break;

            case 'courseenrolments':
                $base_select .= ", COUNT(DISTINCT ue.id) as enrolment_count, MAX(ue.timecreated) as enrolment_date";
                $base_from .= " LEFT JOIN {user_enrolments} ue ON ue.userid = u.id 
                               LEFT JOIN {enrol} e ON e.id = ue.enrolid";
                $additional_where = " AND ue.id IS NOT NULL";
                $base_group .= ", ue.userid";
                break;

            case 'coursecompletions':
                $base_select .= ", COUNT(DISTINCT cc.id) as completion_count, MAX(cc.timecompleted) as completion_date,
                                CASE WHEN COUNT(DISTINCT cc.id) > 0 THEN CONCAT(COUNT(DISTINCT cc.id), ' completed') ELSE '0 completed' END as completion_progress";
                $base_from .= " LEFT JOIN {course_completions} cc ON cc.userid = u.id";
                $additional_where = " AND cc.timecompleted IS NOT NULL";
                break;

            case 'activeusers':
                // Users active in last 30 days
                $base_select .= ", 
                    (SELECT COUNT(*) FROM {logstore_standard_log} l WHERE l.userid = u.id 
                     AND l.timecreated > " . (time() - (30 * 24 * 60 * 60)) . ") as activity_count";
                $additional_where = " AND u.lastaccess > " . (time() - (30 * 24 * 60 * 60));
                break;

            case 'inactiveusers':
                // Users inactive for more than 30 days
                $base_select .= ", 
                    CASE WHEN u.lastaccess = 0 THEN 999999 
                         ELSE FLOOR((UNIX_TIMESTAMP() - u.lastaccess) / (24 * 60 * 60)) 
                    END as days_inactive";
                $additional_where = " AND (u.lastaccess = 0 OR u.lastaccess < " . (time() - (30 * 24 * 60 * 60)) . ")";
                break;

            default:
                $additional_where = "";
        }

        $final_query = $base_select . $base_from . $base_where . ($additional_where ?? '') . $base_group . $base_order;

        return $DB->get_records_sql($final_query, [$identifier]);
    }
    /**
     * Get available courses for feedback analysis
     */
    public static function get_available_courses_for_feedback()
    {
        global $DB;
        return $DB->get_records_sql("
        SELECT DISTINCT c.id, c.fullname, c.shortname
        FROM {course} c
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE c.visible = 1 AND e.status = 0 AND ue.status = 0
        ORDER BY c.fullname
    ");
    }

    /**
     * Get available feedback forms for a course
     */
    public static function get_feedback_forms_by_course($courseid = null)
    {
        global $DB;

        $where = "f.course > 0";
        $params = [];

        if ($courseid) {
            $where .= " AND f.course = ?";
            $params[] = $courseid;
        }

        return $DB->get_records_sql("
        SELECT f.id, f.name, f.course, c.fullname as course_name
        FROM {feedback} f
        JOIN {course} c ON c.id = f.course
        WHERE {$where}
        ORDER BY c.fullname, f.name
    ", $params);
    }

    /**
     * Get feedback analysis data with filters
     */
    public static function get_feedback_analysis($feedback_id, $role_type, $identifier, $courseid = null)
    {
        global $DB;

        // Field mapping for hierarchy
        $field_mapping = [
            'spoc' => 8,
            'area_manager' => 11,
            'nutrition_officer' => 12
        ];

        if (!isset($field_mapping[$role_type])) {
            return [];
        }

        $field_id = $field_mapping[$role_type];

        // Build WHERE conditions
        $where_conditions = [];
        $params = [$feedback_id];

        // Add hierarchy filter
        $where_conditions[] = "ud_hierarchy.data = ?";
        $params[] = $identifier;

        // Add course enrollment filter if specified
        $course_join = "";
        if ($courseid) {
            $course_join = "
            JOIN {user_enrolments} ue ON ue.userid = uk.id
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
        ";
            $where_conditions[] = "ue.status = 0 AND e.status = 0";
            $params[] = $courseid;
        }

        $where_clause = implode(" AND ", $where_conditions);

        return $DB->get_records_sql("
        WITH feedback_values AS (
            SELECT
                fi.id,
                fi.name AS question,
                fv.value,
                CASE
                    WHEN fv.value = '4' THEN 4
                    WHEN fv.value = '3' THEN 3
                    WHEN fv.value = '2' THEN 2
                    WHEN fv.value = '1' THEN 1
                END AS score,
                uk.id AS userid,
                ud_hierarchy.data AS region
            FROM {feedback} f
            JOIN {feedback_item} fi ON f.id = fi.feedback
            JOIN {feedback_value} fv ON fi.id = fv.item
            JOIN {feedback_completed} fc ON fv.completed = fc.id
            JOIN {user} uk ON uk.id = fc.userid
            LEFT JOIN {user_info_data} ud_hierarchy ON ud_hierarchy.userid = uk.id AND ud_hierarchy.fieldid = ?
            {$course_join}
            WHERE f.id = ? AND {$where_clause}
        ),
        weighted_feedback AS (
            SELECT
                id, question, value, score,
                COUNT(*) AS count,
                COUNT(*) * score AS weighted_score
            FROM feedback_values
            GROUP BY id, question, value, score
        ),
        total_scores AS (
            SELECT
                id, question,
                SUM(weighted_score) AS total_weighted_score,
                SUM(count) AS total_users
            FROM weighted_feedback
            GROUP BY id, question
        ),
        average_score AS (
            SELECT
                id, question, total_weighted_score, total_users,
                ROUND(total_weighted_score * 1.0 / total_users, 2) AS avg_score
            FROM total_scores
        ),
        categorized_feedback AS (
            SELECT
                wf.id, wf.question,
                SUM(CASE WHEN wf.value = '4' THEN wf.count ELSE 0 END) AS excellent,
                SUM(CASE WHEN wf.value = '3' THEN wf.count ELSE 0 END) AS good,
                SUM(CASE WHEN wf.value = '2' THEN wf.count ELSE 0 END) AS average_score,
                SUM(CASE WHEN wf.value = '1' THEN wf.count ELSE 0 END) AS needs_improvement,
                (SELECT avg_score FROM average_score WHERE average_score.id = wf.id) AS avg_score,
                CASE
                    WHEN (SELECT avg_score FROM average_score WHERE average_score.id = wf.id) >= 3.5 THEN 'Excellent'
                    WHEN (SELECT avg_score FROM average_score WHERE average_score.id = wf.id) >= 2.5 THEN 'Good'
                    WHEN (SELECT avg_score FROM average_score WHERE average_score.id = wf.id) >= 1.5 THEN 'Average'
                    ELSE 'Needs Improvement'
                END AS final_category
            FROM weighted_feedback wf
            GROUP BY wf.id, wf.question
        )
        SELECT * FROM categorized_feedback
        ORDER BY id ASC
    ", array_merge([$field_id], $params));
    }
}