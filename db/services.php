<?php
// db/services.php - Add this to register your web service

$functions = array(
    'local_dashboardv2_get_users_data' => array(
        'classname'   => 'local_dashboardv2\external\get_users_data',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get users data for dashboard',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/dashboardv2:viewdashboard',
        'loginrequired' => true,
    ),
);