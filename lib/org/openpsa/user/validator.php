<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Form validation functionality
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_validator extends midcom_admin_user_validator
{
    /**
     * Validation rules for edit forms
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function validate_edit_form(array $fields)
    {
        $result = $this->is_username_available($fields);
        if (is_array($result)) {
            return $result;
        }
        return $this->verify_existing_password($fields);
    }

    /**
     * Validate the existing password
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function verify_existing_password(array $fields)
    {
        if (midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')) {
            //User has the necessary rights, so we're good
            return true;
        }

        if (!midcom_connection::login($fields["username"], $fields["current_password"])) {
            return array(
                'current_password' => midcom::get()->i18n->get_string("wrong current password", "org.openpsa.user")
            );
        }
        return true;
    }

    /**
     * Test if a username exists
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function username_exists(array $fields)
    {
        $result = array();
        $user = midcom::get()->auth->get_user_by_name($fields["username"]);
        if (!$user) {
            $result["username"] = midcom::get()->i18n->get_string("unknown username", "org.openpsa.user");
        }

        if (!empty($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Test is email address exists
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function email_exists(array $fields)
    {
        $result = array();
        $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
        $qb->add_constraint('email', '=', $fields["email"]);
        $count = $qb->count();
        if ($count == 0) {
            $result["email"] = midcom::get()->i18n->get_string("unknown email address", "org.openpsa.user");
        } elseif ($count > 1) {
            $result["email"] = midcom::get()->i18n->get_string("multiple entries found, cannot continue", "org.openpsa.user");
        }

        if (!empty($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Test that both email and username exist
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function email_and_username_exist(array $fields)
    {
        $result = array();
        $user = midcom::get()->auth->get_user_by_name($fields["username"]);
        if (!$user) {
            $result["username"] = midcom::get()->i18n->get_string("no user found with this username and email address", "org.openpsa.user");
        } else {
            $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
            $qb->add_constraint('email', '=', $fields["email"]);
            $qb->add_constraint('guid', '=', $user->guid);
            $count = $qb->count();
            if ($count == 0) {
                $result["username"] = midcom::get()->i18n->get_string("no user found with this username and email address", "org.openpsa.user");
            }
        }
        if (!empty($result)) {
            return $result;
        }
        return true;
    }
}
