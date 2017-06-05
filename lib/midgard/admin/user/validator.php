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
        $result = [];
        if (!empty($fields["username"])) {
            $user = midcom::get()->auth->get_user_by_name($fields["username"]);

            if (   $user
                && (   !isset($fields['person'])
                    || $user->guid != $fields['person'])) {
                $result["username"] = sprintf(midcom::get()->i18n->get_string("username %s is already in use", "midgard.admin.user"), $fields['username']);
            }
        }

        if (!empty($result)) {
            return $result;
        }
        return true;
    }
}
