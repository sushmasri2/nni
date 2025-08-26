<?php
function local_dashboardv2_after_config() {
    global $USER, $CFG, $FULLME;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Avoid infinite redirect loop
    if (strpos($FULLME, '/local/dashboardv2/') !== false) {
        return;
    }

    $context = context_system::instance();
    $roles = get_user_roles($context, $USER->id);

    foreach ($roles as $role) {
        if (in_array($role->shortname, ['spoc', 'area_manager'])) {
            // Redirect only if user is on the default dashboard
            if (strpos($FULLME, '/my') !== false) {
                redirect(new moodle_url('/local/dashboardv2/'));
                exit;
            }
        }
    }
}
