<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: action.php 24407 2009-12-08 11:57:32Z gudd $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_action extends midcom_baseclasses_components_handler
{
    /**
     * The person we're working with, if any
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_person = null;

    public function _on_initialize()
    {
        $_MIDCOM->auth->require_valid_user();
    }

    private function _load_person($identifier)
    {
        $person = new org_openpsa_contacts_person_dba($identifier);

        if (!is_object($person))
        {
            debug_add("Person object {$identifier} is not an object");
            return false;
        }

        $_MIDCOM->set_pagetitle("{$person->firstname} {$person->lastname}");

        return $person;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_group_memberships($handler_id, $args, &$data)
    {
        // Check if we get the person
        $this->_person = $this->_load_person($args[0]);
        if (!$this->_person)
        {
            debug_add("Person loading failed");
            return false;
        }

        $this->_request_data['person'] =& $this->_person;

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $this->_person->id);
        $this->_request_data['memberships'] = $qb->execute();

        // Group person listing, always work even if there are none
        $_MIDCOM->skip_page_style = true;

        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_account_create($handler_id, $args, &$data)
    {
        // Check if we get the person
        $this->_person = $this->_load_person($args[0]);
        if (!$this->_person)
        {
            debug_add("Person loading failed");
            return false;
        }

        $this->_request_data['person'] =& $this->_person;

        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        if ($this->_person->username)
        {
            // Creating new account for existing account is not possible
            return false;
        }

        if (array_key_exists('midcom_helper_datamanager2_save', $_POST))
        {
            // User has tried to create account
            $plaintext = true;
            if(array_key_exists('org_openpsa_contacts_person_account_encrypt' , $_POST))
            {
                $plaintext = false;
            }
            $stat = $this->_person->set_account($_POST['org_openpsa_contacts_person_account_username'], $_POST['org_openpsa_contacts_person_account_password'], $plaintext);

            if ($stat)
            {
                // Account created, redirect to person card
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
            }
            else
            {
                // Failure, give a message
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("failed to create user account, reason ").midcom_connection::get_error_string(), 'error');
            }
        }
        else if (array_key_exists('midcom_helper_datamanager2_cancel', $_POST))
        {
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
        }

        $this->_request_data['person_action'] = 'create account';

        if ($this->_person->email)
        {
            // Email address (first part) is the default username
            $this->_request_data['default_username'] = preg_replace('/@.*/', '', $this->_person->email);
        }
        else
        {
            // Otherwise use cleaned up firstname.lastname
            $this->_request_data['default_username'] = midcom_generate_urlname_from_string($this->_person->firstname) . '.' . midcom_generate_urlname_from_string($this->_person->lastname);
        }

        // We should do this by listing to /dev/urandom
        $d = $this->_config->get('default_password_lenght');
        // Safety
        if ($d == 0)
        {
            $d = 6;
        }
        if (function_exists('mt_rand'))
        {
            $rand = 'mt_rand';
        }
        else
        {
            $rand = 'rand';
        }
        // Valid characters for default password (PONDER: make configurable ?)
        $passwdchars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#@';
        $this->_request_data['default_password'] = '';
        while ($d--)
        {
            $this->_request_data['default_password'] .= $passwdchars[$rand(0, strlen($passwdchars) - 1)];
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");

        //get rules for js in style
        $rules = $this->_config->get('password_match_score');
        $data_rules = midcom_get_snippet_content($rules);
        $result = eval ("\$contents = array ( {$data_rules}\n );");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse the schema definition in '{$rules}', see above for PHP errors.");
            // This will exit.
        }
        $this->_request_data['password_rules'] = $contents['rules'];
        //get password_length & minimum score for js
        $this->_request_data['min_score'] = $this->_config->get('min_password_score');
        $this->_request_data['min_length'] = $this->_config->get('min_password_length');
        $this->_request_data['max_length'] = $this->_config->get('max_password_length');

        $this->_update_breadcrumb_line();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }


    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_account_edit($handler_id, $args, &$data)
    {
        // Check if we get the person
        $this->_person = $this->_load_person($args[0]);
        if (!$this->_person)
        {
            debug_add("Person loading failed");
            return false;
        }

        $this->_request_data['person'] =& $this->_person;

        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        if ($this->_person->id != midcom_connection::get_user() && !midcom_connection::is_admin())
        {
            return false;
        }

        if (!$this->_person->username)
        {
            // Account needs to be created first, relocate
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate($prefix . "account/create/" . $this->_person->guid . "/");
        }

        $this->_request_data['person_action'] = 'edit account';

        if (array_key_exists('midcom_helper_datamanager2_save', $_POST))
        {
            // Check that the inputted passwords match
            if ($_POST['org_openpsa_contacts_person_account_newpassword'] != $_POST['org_openpsa_contacts_person_account_newpassword2'])
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("passwords don't match"), 'error');
            }
            elseif($_POST['org_openpsa_contacts_person_account_current_password'] != null || $_MIDCOM->auth->admin)
            {
                $plaintext = true;
                if(array_key_exists('org_openpsa_contacts_person_account_encrypt' , $_POST))
                {
                    $plaintext = false;
                }
                $check_user = true;
                //check user auth if current user is not admin
                if(!$_MIDCOM->auth->admin)
                {
                    //user auth
                    $check_user = midgard_user::auth($this->_person->username, $_POST['org_openpsa_contacts_person_account_current_password']);
                }

                if(!$check_user)
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("wrong current password"), 'error');
                    $stat = false;
                }
                else
                {
                    // Update account
                    $stat = $this->_person->set_account($_POST['org_openpsa_contacts_person_account_username'], $_POST['org_openpsa_contacts_person_account_newpassword'], $plaintext );
                }
                if ($stat)
                {
                    // Account updated, redirect to person card
                    $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                    $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
                }
                else
                {
                    // Failure, give a message
                    $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("failed to update user account, reason ") . midcom_connection::get_error_string(), 'error');
                }
            }
            else
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("no current password given"), 'error');
            }
        }
        else if (array_key_exists('midcom_helper_datamanager2_cancel', $_POST))
        {
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        $_MIDCOM->enable_jquery();

        //get rules for js in style
        $rules = $this->_config->get('password_match_score');
        $data_rules = midcom_get_snippet_content($rules);
        $result = eval ("\$contents = array ( {$data_rules}\n );");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse the schema definition in '{$rules}', see above for PHP errors.");
            // This will exit.
        }
        $this->_request_data['password_rules'] = $contents['rules'];
        //get password_length & minimum score for js
        $this->_request_data['min_score'] = $this->_config->get('min_password_score');
        $this->_request_data['min_length'] = $this->_config->get('min_password_length');
        $this->_request_data['max_length'] = $this->_config->get('max_password_length');

        $this->_update_breadcrumb_line();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $this->add_breadcrumb("person/{$this->_person->guid}/", $this->_person->name);
        $this->add_breadcrumb("", $this->_l10n->get($this->_request_data['person_action']));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_group_memberships($handler_id, &$data)
    {
        // This is most likely a dynamic_load
        if (count($data['memberships']) > 0)
        {
            midcom_show_style("show-person-groups-header");
            foreach ($data['memberships'] as $member)
            {
                $this->_request_data['member'] = $member;

                if ($member->extra == "")
                {
                    $member->extra = $this->_l10n->get('<title>');
                }
                $data['member_title'] = $member->extra;
                $data['group'] = new org_openpsa_contacts_group_dba($member->gid);
                midcom_show_style("show-person-groups-item");
            }
            midcom_show_style("show-person-groups-footer");
        }
        else
        {
            midcom_show_style("show-person-groups-empty");
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_account_create($handler_id, &$data)
    {
        midcom_show_style("show-person-account-create");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_account_edit($handler_id, &$data)
    {
        midcom_show_style("show-person-account-edit");
    }
}
?>