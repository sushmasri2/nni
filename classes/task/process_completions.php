<?php
// File: local/coursecompletion/classes/task/process_completions.php
namespace local_coursecompletion\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for processing course completions with detailed debugging
 */
class process_completions extends \core\task\scheduled_task
{

    public function get_name()
    {
        return get_string('taskname', 'local_coursecompletion');
    }

    public function execute()
    {
        global $DB, $CFG;

        mtrace('Processing course completions...');
        mtrace('Debug mode enabled - showing detailed information');
        mtrace('');

        // Module mapping from original script
        $modules = [
            "896" => "900,878,879,880,881,882,883,884,885,886,887,888,889,890,891,892,893,894,895,898,899",
            "918" => "922,901,902,903,904,905,906,907,908,909,910,911,912,913,914,915,916,917,920,921",
            "939" => "942,923,924,925,926,927,928,929,930,931,932,933,934,935,936,937,938,941",
            "958" => "961,943,944,945,946,947,948,949,950,951,952,953,954,955,956,957,960",
            "975" => "978,962,963,964,965,966,967,968,969,970,971,972,973,974,977",
            "992" => "995,979,980,981,982,983,984,985,986,987,988,989,990,991,994",
            "1006" => "1010,996,997,998,999,1000,1001,1002,1003,1004,1005,1008,1009",
        ];

        mtrace('Module mappings loaded: ' . count($modules) . ' mappings');
        mtrace('');

        // First, let's check what SCORM modules exist in course 6
        $check_scorm_sql = "
        SELECT
            cm.id as coursemoduleid,
            s.name,
            c.fullname as coursename
        FROM
            {course_modules} cm
        JOIN
            {modules} m ON cm.module = m.id
        JOIN
            {course} c ON cm.course = c.id
        JOIN
            {scorm} s ON s.id = cm.instance
        WHERE
            c.id = 6
            AND m.name = 'scorm'
        ORDER BY
            cm.id";

        $scorm_modules = $DB->get_records_sql($check_scorm_sql);
        mtrace('SCORM modules found in course 6:');
        foreach ($scorm_modules as $module) {
            $mapped = isset($modules[$module->coursemoduleid]) ? 'YES' : 'NO';
            mtrace("  ID: {$module->coursemoduleid} | Name: {$module->name} | Mapped: {$mapped}");
        }
        mtrace('');

        // SQL to fetch SCORM data - matching your useractv2.php exactly
        $scorm_sql = "
        SELECT
            cmc.coursemoduleid,
            (SELECT name FROM {scorm} WHERE id = cm.instance) AS name,
            CASE
                WHEN cmc.completionstate IS NULL THEN 'Not Started'
                WHEN cmc.completionstate = 0 THEN 'In Progress'
                WHEN cmc.completionstate = 1 THEN 'Completed'
                WHEN cmc.completionstate = 2 THEN 'Completed with Pass'
                WHEN cmc.completionstate = 3 THEN 'Completed with Fail'
                ELSE 'Unknown'
            END AS progress,
            CASE 
                WHEN cmc.timemodified IS NOT NULL 
                THEN FROM_UNIXTIME(cmc.timemodified)
                ELSE NULL
            END AS when_completed
        FROM
            {course_modules} cm
        JOIN
            {modules} m ON cm.module = m.id
        LEFT JOIN
            {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = :userid
        JOIN
            {course} c ON cm.course = c.id
        WHERE
            c.id = 6
            AND m.name = 'scorm'
            AND cm.id != 0
        ORDER BY
            cm.id";

        // Fetch users with custom field 'V2' - matching your useractv2.php exactly
        $user_sql = "
            SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
            FROM {user} u
            LEFT JOIN {user_info_data} ud ON ud.userid = u.id AND ud.fieldid = 7
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {role} r ON r.id = ra.roleid
            JOIN {context} ctx ON ctx.id = ra.contextid
            WHERE ud.data = 'V2' 
                AND u.deleted = 0 
                AND u.suspended = 0
                AND r.shortname = 'student'
                AND ctx.contextlevel = 50
            ORDER BY u.id";

        $users = $DB->get_records_sql($user_sql);
        $total_users = count($users);

        mtrace("Found {$total_users} users to process");
        mtrace('');

        $processed_count = 0;
        $updated_count = 0;
        $inserted_count = 0;

        foreach ($users as $user) {
            try {
                mtrace("Processing User: {$user->fullname} for ID: {$user->id}");

                // Get SCORM data for this user
                $scorm_data = $DB->get_records_sql($scorm_sql, ['userid' => $user->id]);

                if (!$scorm_data) {
                    mtrace("  No SCORM data found for user {$user->id}");
                    $processed_count++;
                    mtrace('');
                    continue;
                }

                mtrace("  Found " . count($scorm_data) . " SCORM records for this user");

                foreach ($scorm_data as $record) {
                    mtrace("{$record->coursemoduleid} : {$record->name}");
                    
                    if (isset($modules[$record->coursemoduleid])) {
                        $activity_ids = explode(",", $modules[$record->coursemoduleid]);
                        $updated_activities = [];
                        $inserted_activities = [];
                        $completed_activities = [];

                        foreach ($activity_ids as $activity_id) {
                            $activity_id = trim($activity_id);

                            // Check if completion record exists
                            $existing = $DB->get_record('course_modules_completion', [
                                'userid' => $user->id,
                                'coursemoduleid' => $activity_id
                            ]);

                            if ($existing) {
                                // Update if not completed
                                if ($existing->completionstate != 1) {
                                    $existing->completionstate = 1;
                                    $existing->timemodified = time();
                                    $DB->update_record('course_modules_completion', $existing);
                                    $updated_count++;
                                    $updated_activities[] = $activity_id;
                                } else {
                                    $completed_activities[] = $activity_id;
                                }
                            } else {
                                // Insert new completion record
                                $completion = new \stdClass();
                                $completion->userid = $user->id;
                                $completion->coursemoduleid = $activity_id;
                                $completion->completionstate = 1;
                                $completion->timemodified = time();

                                $DB->insert_record('course_modules_completion', $completion);
                                $inserted_count++;
                                $inserted_activities[] = $activity_id;
                            }
                        }

                        // Display the activity results in the format you want
                        if (!empty($inserted_activities)) {
                            mtrace("  INSERTED(" . implode(",", $inserted_activities) . ")");
                        }
                        if (!empty($updated_activities)) {
                            mtrace("  UPDATED(" . implode(",", $updated_activities) . ")");
                        }
                        if (!empty($completed_activities)) {
                            mtrace("  COMPLETED(" . implode(",", $completed_activities) . ")");
                        }
                    } else {
                        mtrace("  Module {$record->coursemoduleid} not found in mapping");
                    }
                }

                $processed_count++;
                mtrace('');

            } catch (\Exception $e) {
                mtrace("Error processing user {$user->id}: " . $e->getMessage());
                mtrace('');
                continue;
            }
        }

        mtrace("=========================================");
        mtrace("Processing complete. Total Users Processed: {$processed_count}");
        mtrace("Records updated: {$updated_count}");
        mtrace("Records inserted: {$inserted_count}");
        mtrace("=========================================");
    }
}

?>