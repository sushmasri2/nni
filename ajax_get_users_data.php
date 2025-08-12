<?php
// ajax_get_users_data.php - Place in plugin root directory

require_once('../../config.php');

use local_dashboardv2\ajax\get_users_data;

// Call the class method
get_users_data::execute();