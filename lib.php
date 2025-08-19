<?php
// File: local/coursecompletion/lib.php
defined('MOODLE_INTERNAL') || die();

/**
 * Course Completion Cron Task
 */
function local_coursecompletion_cron() {
    global $DB, $CFG;
    
    mtrace('Starting course completion processing...');
    
    try {
        // Get the task instance and run it
        $task = new \local_coursecompletion\task\process_completions();
        $task->execute();
        
        mtrace('Course completion processing completed successfully.');
        return true;
    } catch (Exception $e) {
        mtrace('Error in course completion processing: ' . $e->getMessage());
        return false;
    }
}

?>