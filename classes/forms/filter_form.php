<?php
namespace local_dashboardv2\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class filter_form extends \moodleform {
    
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        
        if (isset($customdata['area_managers']) && !empty($customdata['area_managers'])) {
            $options = ['' => get_string('choose_area_manager', 'local_dashboardv2')];
            foreach ($customdata['area_managers'] as $am) {
                $options[$am->username] = fullname($am) . ' (' . $am->username . ')';
            }
            
            $mform->addElement('select', 'selected_area_manager', 
                get_string('select_area_manager', 'local_dashboardv2'), $options);
        }
        
        if (isset($customdata['nutrition_officers']) && !empty($customdata['nutrition_officers'])) {
            $options = ['' => get_string('choose_nutrition_officer', 'local_dashboardv2')];
            foreach ($customdata['nutrition_officers'] as $no) {
                $options[$no->username] = fullname($no) . ' (' . $no->username . ')';
            }
            
            $mform->addElement('select', 'selected_nutrition_officer', 
                get_string('select_nutrition_officer', 'local_dashboardv2'), $options);
        }
        
        $this->add_action_buttons(false, get_string('filter', 'core'));
    }
}