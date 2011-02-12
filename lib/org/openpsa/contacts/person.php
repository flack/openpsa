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
class org_openpsa_contacts_person_dba extends midcom_db_person
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_person';

    /**
     * Helper class for handling accounts
     *
     * @param midcom_core_account
     */
    private $_account;

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    /**
     * Retrieve a reference to a person object, uses in-request caching
     *
     * @param string $src GUID of person (ids work but are discouraged)
     * @return org_openpsa_contacts_person_dba reference to device object or false
     */
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * Sets username and password for person
     *
     * @param string - contains username
     * @param string - contains the new - to set - password
     */
    function set_account($username, $new_password)
    {
        $this->_account = midcom_core_account::get($this);
        if (!empty($new_password))
        {
            $new_password_encrypted = midcom_connection::prepare_password($new_password);

            $current_password_plaintext = false;
            //check if password in person is plaintext or not
            if (preg_match('/^\*{2}/', $this->_account->get_password()))
            {
                $current_password_plaintext = true;
            }
            //check if the new encrypted password was already used
            if (    $this->check_password($new_password_encrypted)
                 && $this->check_password_strength($new_password))
            {
                $this->_account->set_password($new_password);
                $this->save_password();
            }
            else
            {
                return false;
            }
        }

        $this->_account->set_username($username);

        if (!$this->_account->save())
        {
            return false;
        }

        //sets privilege
        $_MIDCOM->auth->request_sudo('org.openpsa.contacts');
        $this->set_privilege('midgard:owner', "user:" . $this->guid);
        $_MIDCOM->auth->drop_sudo();

        return true;
    }

    public function _on_creating()
    {
        //Make sure we have objType
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PERSON;
        }
        return true;
    }

    public function _on_updated()
    {
        parent::_on_updated();
        if ($this->homepage)
        {
            // This group has a homepage, register a prober
            $args = array
            (
                'person' => $this->guid,
            );
            $_MIDCOM->load_library('midcom.services.at');
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    public function _on_deleting()
    {
        // FIXME: Call duplicate checker's dependency handling methods
        return true;
    }

    function get_label_property()
    {
        if ($this->rname)
        {
            $property = 'rname';
        }
        else
        {
            $property = 'username';
        }

        return $property;
    }

    /**
     * Function to check if passed password was already used
     *
     * @param string password to check
     * @return bool returns if password was already used - true indicates passed password wasn't used
     */
    function check_password($password)
    {
        //check current password
        if (($this->_account->get_password() == $password))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password is the same as the current one', 'org.openpsa.contacts'), 'error');
            return false;
        }

        //get last passwords
        $old_passwords = $this->get_old_passwords();

        //check last passwords
        if (in_array($password, $old_passwords))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password was already used', 'org.openpsa.contacts'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Function to add current password to parameter old passwords - does not update()
     *
     * @param string password to set
     */
    function save_password($password)
    {
        $old_passwords_array = $this->get_old_passwords();
        $new_passwords_string = "";
        if (is_array($old_passwords_array))
        {
            array_unshift($old_passwords_array, $this->_account->get_password());
        }
        $count = count($old_passwords_array);
        $max = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('max_old_passwords');
        if($count > $max)
        {
            for($i = $max;$i <= $count;$i++)
            {
                unset($old_passwords_array[$i]);
            }
        }
        $new_passwords_string = serialize($old_passwords_array);

        //add timestamp of password-change
        $timestamp = time();
        $this->set_parameter("org_openpsa_contacts_password", "last_change", $timestamp);

        $this->set_parameter("org_openpsa_contacts_password", "old_passwords", $new_passwords_string);
        return true;
    }

    /**
     * Function get old passwords
     *
     * @return array - array with old passwords - empty if there aren't any old passwords'
     */
    function get_old_passwords()
    {
        $old_passwords_string = $this->get_parameter("org_openpsa_contacts_password", "old_passwords");
        if ($old_passwords_string != "")
        {
            $old_passwords_array = unserialize($old_passwords_string);
            $count = count($old_passwords_array);
            $max = (int) midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('max_old_passwords');
            if($count > $max)
            {
                //only as much passwords as given in config
                for ($i = $max; $i <= $count; $i++)
                {
                    unset($old_passwords_array[$i]);
                }
            }
        }
        else
        {
            $old_passwords_array = array();
        }
        return $old_passwords_array;
    }

    /**
     * Function to check strength of passed password
     *
     * @param string - contains password to check
     */
    function check_password_strength($password)
    {
        $password_length = strlen($password);
        $score = 0;

        // score for length & repetition
        $pattern_length = 4;
        $score_char = 4;
        $score = $password_length * $score_char;
        for ($count = 1; $count <= $pattern_length; $count++)
        {
            $score += strlen($this->check_repetition($count, $password)) - $password_length;
        }

        $max = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('min_password_length');
        $rules = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('password_match_score');

        $data_snippet = midcom_helper_misc::get_snippet_content($rules);
        $result = eval ("\$contents = array ( {$data_snippet}\n );");
        if ($result === false)
        {
            throw new midcom_error("Failed to parse the schema definition in '{$rules}', see above for PHP errors.");
        }
        if($password_length < $max)
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password too short', 'org.openpsa.contacts'), 'error');
            return false;
        }
        //check $password with rules
        foreach ($contents['rules'] as $rule)
        {
            $match = preg_match($rule['match'], $password);
            if($match > 0)
            {
                $score += $rule['score'];
            }
        }
        if($score <= midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('min_password_score'))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password weak', 'org.openpsa.contacts'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Function to check repetition for given password
     *
     * @param integer $plen length to check for repetitions inside the password
     * @param string $password contains password to check
     *
     * @return string - string without repetitions
     */
    function check_repetition($plen, $password)
    {
        $result = "";
        for ($i = 0; $i < strlen($password); $i++)
        {
            $repeated=true;
            for ($j = 0; $j < $plen && ($j + $i + $plen) < strlen($password); $j++)
            {
                if(    (substr($password, $j + $i, 1) == substr($password, $j + $i + $plen, 1))
                    && $repeated)
                {
                    $repeated = true;
                }
                else
                {
                    $repeated = false;
                }
            }
            if ($j < $plen)
            {
                $repeated=false;
            }
            if ($repeated)
            {
                $i += $plen - 1;
                $repeated = false;
            }
            else
            {
                $result .= substr($password, $i, 1);
            }
        }
        return $result;
    }

    /**
     * Function to check password age for this user (age is taken from config)
     *
     * @return boolean - true indicates password is ok - false password is to old
     */
    function check_password_age()
    {
        $max_age_days = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('password_max_age_days');
        if ($max_age_days == 0)
        {
            return true;
        }
        $max_timeframe = time() - ($max_age_days * 24 * 60 * 60);
        $last_change = $this->get_parameter("org_openpsa_contacts_password", "last_change");

        if (empty($last_change))
        {
            return false;
        }

        if ($max_timeframe < $last_change )
        {
            return true;
        }
        return false;
    }

    /**
     * Function to disable account for time period given in config
     *
     * @return boolean - indicates success
     */
    function disable_account()
    {
        $this->_account = midcom_core_account::get($this);

        $timeframe_minutes = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('password_block_timeframe_min');
        if ($timeframe_minutes == 0)
        {
            return false;
        }
        $timeframe = $timeframe_minutes * 60;
        $args = array
        (
            'guid' => $this->guid,
            'parameter_name' => 'org_openpsa_contacts_blocked_account',
            'password' => 'account_password',
        );

        $atstat = midcom_services_at_interface::register(time() + $timeframe, 'org.openpsa.contacts', 'reopen_account', $args);
        if (!$atstat)
        {
             throw new midcom_error("Failed to register interface for re_open the user account, last Midgard error was: " . midcom_connection::get_error_string());
        }
        $this->set_parameter("org_openpsa_contacts_blocked_account", "account_password", $this->_account->get_password());
        $this->_account->set_password('');
        return $this->_account->save();
    }
}
?>