<?php
/**
 * @package midgard.admin.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Form validation functionality
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_validator
{
    /**
     * Test if username is available.
     *
     * If the formdata contains a person GUID, it is ignored during the search
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function is_username_available(array $fields)
    {
        if (!empty($fields["username"])) {
            $mc = new midgard_collector('midgard_user', 'login', $fields["username"]);
            $mc->set_key_property('person');
            $mc->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
            if (isset($fields['person'])) {
                $mc->add_constraint('person', '<>', $fields['person']);
            }
            $mc->execute();
            $keys = $mc->list_keys();
            if (count($keys) > 0) {
                return [
                    "username" => sprintf(midcom::get()->i18n->get_string("username %s is already in use", "midgard.admin.user"), $fields['username'])
                ];
            }
        }
        return true;
    }
}
