<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_viewer extends midcom_baseclasses_components_request
{
    public function add_password_validation_code()
    {
        //get rules for js in style
        $this->_request_data['password_rules'] = $this->_config->get('password_score_rules');

        //get password_length & minimum score for js
        $this->_request_data['min_score'] = $this->_config->get('min_password_score');
        $this->_request_data['min_length'] = $this->_config->get('min_password_length');
        $this->_request_data['max_length'] = $this->_config->get('max_password_length');
    }

    public function _on_handle($handler_id, $args)
    {
        if ($handler_id != 'lostpassword')
        {
            midcom::get('auth')->require_valid_user();
        }
    }

}
?>