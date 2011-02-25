<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped access to org_openpsa_person plus some utility methods
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_accounthelper extends midcom_baseclasses_components_purecode
{
    /**
     * The person we're working with, if any
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_person;

    /**
     * The account we're working with, if any
     *
     * @var midcom_core_account
     */
    private $_account;

    public $errstr;

    /**
     * can be called by various handlers
     *
     * @param string password: leave blank for auto generated
     */
    public function create_account($person_guid,$username,$usermail,$password="",$send_welcome_mail){

        //quick validation
        if (empty($person_guid) || empty($username) || empty($usermail))
        {
            $this->errstr = "Missing information";
            return false;
        }

        // Check if we get the person
        $this->_person = new org_openpsa_contacts_person_dba($person_guid);

        //need to generate password?
        if (empty($password))
        {
            $generated_password = true;
            $password = org_openpsa_contacts_handler_person_account::generate_safe_password($this->_person,$this->_config->get("min_password_length"));
        }
        else
        {
            $generated_password = false;
        }

        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        $this->_account = new midcom_core_account($this->_person);

        //an account already existing?
        if ($this->_account->get_password())
        {
            $this->errstr = "Creating new account for existing account is not possible";
            return false;
        }

        //try creating
        $success = $this->_person->set_account($username, $password);
        if(!$success)
        {
            $this->errstr = "couldnt set account, reason: ".$this->_person->errstr;
            return false;
        }

        //send welcome mail?
        if($send_welcome_mail)
        {
            $_MIDCOM->componentloader->load('org.openpsa.mail');
            $mail = new org_openpsa_mail();
            $mail->to = $usermail;

            $mail->from = $this->_config->get('welcome_mail_from_address');


            $mail->subject = $this->_config->get('welcome_mail_title');

            // Make replacements to body
            $replacements = array(
                "__USERNAME__" => $username,
                "__PASSWORD__" => $password
            );
            $mail->body = strtr($this->_config->get('welcome_mail_body'),$replacements);

            $ret = $mail->send();
            if (!$ret)
            {
                $this->errstr = "Unable to deliver welcome mail";
                return false;
            }

        }
        else
        {
            /*
             * no welcome mail was sent:
             * if the password was auto generated show it in an ui message
             */
            if ($generated_password)
            {
                $_MIDCOM->uimessages->add(
                    $this->_l10n->get('org.openpsa.contacts'),
                    sprintf($this->_l10n->get("account_creation_success"),$username,$password),
                    'ok'
                );
            }
        }

        // Relocate to group view
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $_MIDCOM->relocate("{$prefix}person/{$this->_person->guid}/");
        // This will exit
    }
}
?>