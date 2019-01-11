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
class org_openpsa_user_validator extends midgard_admin_user_validator
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

        if(isset($fields['new_password'])){
            $result_password = $this->password_check($fields);
            if (is_array($result_password)) {
                $result = (is_array($result)) ? array_merge($result, $result_password) : $result_password;
            }
        }

        if (is_array($result)) {
            return $result;
        }
        return $this->verify_existing_password($fields);
    }

    /**
     * Validation rules for create forms
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function validate_create_form(array $fields)
    {
        $result = $this->is_username_available($fields);

        $accounthelper = new org_openpsa_user_accounthelper();
        if($fields['password']['switch'] && !$accounthelper->check_password_strength($fields['password']['password'])){
            $result = ['password' => midcom::get()->i18n->get_string('password weak')];
        }

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
        if (midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class)) {
            //User has the necessary rights, so we're good
            return true;
        }
        $user = midcom::get()->auth->get_user($fields['person']);
        if (!midcom_connection::login($user->username, $fields["current_password"])) {
            return [
                'current_password' => midcom::get()->i18n->get_string("wrong current password", "org.openpsa.user")
            ];
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
        $result = [];
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
        $result = [];
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
        $result = [];
        $user = midcom::get()->auth->get_user_by_name($fields["username"]);
        if (!$user) {
            $result["username"] = midcom::get()->i18n->get_string("no user found with this username and email address", "org.openpsa.user");
        } else {
            $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
            $qb->add_constraint('email', '=', $fields["email"]);
            $qb->add_constraint('guid', '=', $user->guid);
            if ($qb->count() == 0) {
                $result["username"] = midcom::get()->i18n->get_string("no user found with this username and email address", "org.openpsa.user");
            }
        }
        if (!empty($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Test that no previous password is reused & password is strong enough
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function password_check(array $fields)
    {
        $result = [];

        $user = new midcom_db_person($fields["person"]);

        $accounthelper = new org_openpsa_user_accounthelper($user);
        if (!$accounthelper->check_password_reuse($fields['new_password'])){
            $result['password'] = midcom::get()->i18n->get_string('password was already used');
        }
        if (!$accounthelper->check_password_strength($fields['new_password'])){
            $result['password'] = midcom::get()->i18n->get_string('password weak');
        }
        if (!empty($result)) {
            return $result;
        }
        return true;
    }
}
