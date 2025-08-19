<?php
// File: local/coursecompletion/settings.php (optional - for admin settings)
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursecompletion', 
        get_string('pluginname', 'local_coursecompletion'));
    
    // Add settings here if needed in the future
    $settings->add(new admin_setting_heading('local_coursecompletion/general',
        get_string('pluginname', 'local_coursecompletion'),
        'This plugin processes course completions automatically via scheduled task.'));
    
    $ADMIN->add('localplugins', $settings);
}

?>