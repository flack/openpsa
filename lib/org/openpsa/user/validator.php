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
    protected midcom_services_i18n_l10n $l10n;

    public function __construct()
    {
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.user');
    }

    protected function get_accounthelper(midcom_db_person $person = null) : org_openpsa_user_accounthelper
    {
        return new org_openpsa_user_accounthelper($person);
    }

    /**
     * Validation rules for edit forms
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function validate_edit_form(array $fields)
    {
        $result = $this->is_username_available($fields);

        if (isset($fields['new_password'])) {
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
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function validate_create_form(array $fields)
    {
        $result = $this->is_username_available($fields);

        if (   $fields['password']['switch']
            && !$this->get_accounthelper()->check_password_strength((string) $fields['password']['password'])) {
            $result = ['password' => $this->l10n->get('password weak')];
        }

        if (is_array($result)) {
            return $result;
        }
        return $this->verify_existing_password($fields);
    }

    /**
     * Validate the existing password
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function verify_existing_password(array $fields)
    {
        if (midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, org_openpsa_user_interface::class)) {
            //User has the necessary rights, so we're good
            return true;
        }
        $user = midcom::get()->auth->get_user($fields['person']);
        if ($user && $user->username) {
            $account = new midcom_core_account($user->get_storage());
            if (!midcom_connection::verify_password($fields["current_password"], $account->get_password())) {
                return [
                    'current_password' => $this->l10n->get("wrong current password")
                ];
            }
        }
        return true;
    }

    /**
     * Test if a username exists
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function username_exists(array $fields)
    {
        if ($this->is_username_available(['username' => $fields['username']]) === true) {
            return ["username" => $this->l10n->get("unknown username")];
        }
        return true;
    }

    /**
     * Test is email address exists
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function email_exists(array $fields)
    {
        $result = [];
        $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
        $qb->add_constraint('email', '=', $fields["email"]);
        $count = $qb->count();
        if ($count == 0) {
            $result["email"] = $this->l10n->get("unknown email address");
        } elseif ($count > 1) {
            $result["email"] = $this->l10n->get("multiple entries found, cannot continue");
        }

        return $result ?: true;
    }

    /**
     * Test that both email and username exist
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function email_and_username_exist(array $fields)
    {
        $result = [];
        $user = midcom::get()->auth->get_user_by_name($fields["username"]);
        if (!$user) {
            $result["username"] = $this->l10n->get("no user found with this username and email address");
        } else {
            $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
            $qb->add_constraint('email', '=', $fields["email"]);
            $qb->add_constraint('guid', '=', $user->guid);
            if ($qb->count() == 0) {
                $result["username"] = $this->l10n->get("no user found with this username and email address");
            }
        }
        return $result ?: true;
    }

    /**
     * Test that no previous password is reused & password is strong enough
     *
     * @param array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function password_check(array $fields)
    {
        $result = [];

        $accounthelper = $this->get_accounthelper(new midcom_db_person($fields["person"]));
        if (!$accounthelper->check_password_reuse($fields['new_password'])){
            $result['password'] = $this->l10n->get('password was already used');
        }
        if (!$accounthelper->check_password_strength($fields['new_password'])){
            $result['password'] = $this->l10n->get('password weak');
        }
        return $result ?: true;
    }
}
