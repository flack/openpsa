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
class org_openpsa_contacts_person_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_person';

    var $name; //Compound of firstname, lastname and username
    var $rname; //Another compound of firstname, lastname and username

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

    public function _on_loaded()
    {
        //Fill name and rname
        if (   !empty($this->firstname)
            && !empty($this->lastname))
        {
            $this->name = $this->firstname . ' ' . $this->lastname;
            $this->rname = $this->lastname . ', ' . $this->firstname;
        }
        else if (!empty($this->firstname))
        {
            $this->name = $this->firstname;
            $this->rname = $this->firstname;
        }
        else if (!empty($this->username))
        {
            $this->name = $this->username;
            $this->rname = $this->username;
        }
        else
        {
            $this->name = 'person #' . $this->id;
            $this->rname = 'person #' . $this->id;
        }

        $this->_verify_privileges();

        return true;
    }

    /**
     * Sets username and password for person
     *
     * @param string - contains username
     * @param string - contains the new - to set - password
     * @param bool - if password should be encrypted or not
     */
    function set_account($username, $new_password, $plaintext = false )
    {
        $password_checked = false;
        if (!empty($new_password))
        {
            $new_password_encrypted = $this->encrypt_password($new_password, $plaintext);

            $current_password_plaintext = false;
            //check if password in person is plaintext or not
            if (preg_match('/^\*{2}/', $this->password))
            {
                $current_password_plaintext = true;
            }
            $check_same_passwords = false;
            //check if new password and current password are both encrypted or both are not encrypted -  if true they can be compared
            if (   ($current_password_plaintext && $plaintext)
                || (!$current_password_plaintext && !$plaintext))
            {
                $check_same_passwords = true;
            }
            //check if the new encrypted password was already used
            if (    $this->check_password($new_password_encrypted , $check_same_passwords)
                 && $this->check_password_strength($new_password))
            {
                $password_checked = true;
            }
            else
            {
                return false;
            }
        }
        if ($username != $this->username)
        {
            if (   strtolower($username) != strtolower($this->username)
                && $this->check_account_exists($username))
            {
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('username already exists' , 'org.openpsa.contacts') , 'error');
                return false;
            }
            $this->username = $username;
        }
        if ($password_checked == true)
        {
            $this->set_password($new_password_encrypted);
        }

        //sets privilege
        $_MIDCOM->auth->request_sudo('org.openpsa.contacts');
        $this->set_privilege('midgard:owner', "user:" . $this->guid);
        $_MIDCOM->auth->drop_sudo();

        return $this->update();
    }

    /**
     * Removes persons account
     */
    function unset_account()
    {
        $this->username = '';
        $this->password = '';
        return $this->update();
    }

    /**
     * Make sure user has correct privileges to allow to edit themselves
     *
     * @todo This function does nothing
     */
    private function _verify_privileges()
    {
        return false;
        if (!$this->id)
        {
            return false;
        }
        $this_user = $_MIDCOM->auth->get_user($this->id);
        if (!is_object($this_user))
        {
            return false;
        }

        if (!isset($GLOBALS['org_openpsa_contacts_person__verify_privileges']))
        {
            $GLOBALS['org_openpsa_contacts_person__verify_privileges'] = array();
        }
        if (   isset($GLOBALS['org_openpsa_contacts_person__verify_privileges'][$this->id])
            && !empty($GLOBALS['org_openpsa_contacts_person__verify_privileges'][$this->id]))
        {
            debug_add("loop detected for person #{$this->id}, aborting this check silently");
            return true;
        }
        $GLOBALS['org_openpsa_contacts_person__verify_privileges'][$this->id] = true;

        // PONDER: Can't we just use midgard:owner ???
        debug_add("Checking privilege midgard:update for person #{$this->id}");
        if (!$_MIDCOM->auth->can_do('midgard:update', $this, $this_user))
        {
            debug_add("Person #{$this->id} lacks privilege midgard:update, adding");
            $_MIDCOM->auth->request_sudo();
            if (!$this->set_privilege('midgard:update', $this_user, MIDCOM_PRIVILEGE_ALLOW))
            {
                debug_add("\$this->set_privilege('midgard:update', {$this_user->guid}, MIDCOM_PRIVILEGE_ALLOW) failed, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
            else
            {
                debug_add("Added privilege 'midgard:update' for person #{$this->id}", MIDCOM_LOG_INFO);
            }
            $_MIDCOM->auth->drop_sudo();
        }
        //Could be useful, I'm not certain if absolutely needed.
        debug_add("Checking privilege midgard:parameters for person #{$this->id}");
        if (!$_MIDCOM->auth->can_do('midgard:parameters', $this, $this_user))
        {
            debug_add("Person #{$this->id} lacks privilege midgard:parameters, adding");
            $_MIDCOM->auth->request_sudo();
            if (!$this->set_privilege('midgard:parameters', $this_user, MIDCOM_PRIVILEGE_ALLOW))
            {
                debug_add("\$this->set_privilege('midgard:parameters', {$this_user->guid}, MIDCOM_PRIVILEGE_ALLOW) failed, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
            else
            {
                debug_add("Added privilege 'midgard:parameters' for person #{$this->id}", MIDCOM_LOG_INFO);
            }
            $_MIDCOM->auth->drop_sudo();
        }
        //Adding attachments requires both midgard:create and midgard:attachments
        debug_add("Checking privilege midgard:create for person #{$this->id}");
        if (!$_MIDCOM->auth->can_do('midgard:create', $this, $this_user))
        {
            debug_add("Person #{$this->id} lacks privilege midgard:create, adding");
            $_MIDCOM->auth->request_sudo();
            if (!$this->set_privilege('midgard:create', $this_user, MIDCOM_PRIVILEGE_ALLOW))
            {
                debug_add("\$this->set_privilege('midgard:create', {$this_user->guid}, MIDCOM_PRIVILEGE_ALLOW) failed, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
            else
            {
                debug_add("Added privilege 'midgard:create' for person #{$this->id}", MIDCOM_LOG_INFO);
            }
            $_MIDCOM->auth->drop_sudo();
        }
        debug_add("Checking privilege midgard:attachments for person #{$this->id}");
        if (!$_MIDCOM->auth->can_do('midgard:attachments', $this, $this_user))
        {
            debug_add("Person #{$this->id} lacks privilege midgard:attachments, adding");
            $_MIDCOM->auth->request_sudo();
            if (!$this->set_privilege('midgard:attachments', $this_user, MIDCOM_PRIVILEGE_ALLOW))
            {
                debug_add("\$this->set_privilege('midgard:attachments', {$this_user->guid}, MIDCOM_PRIVILEGE_ALLOW) failed, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
            else
            {
                debug_add("Added privilege 'midgard:attachments' for person #{$this->id}", MIDCOM_LOG_INFO);
            }
            $_MIDCOM->auth->drop_sudo();
        }

        $GLOBALS['org_openpsa_contacts_person__verify_privileges'][$this->id] = false;

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

    public function _on_updating()
    {
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

        return true;
    }

    public function _on_updated()
    {
        $this->_verify_privileges();
    }

    public function _on_created()
    {
        $this->_verify_privileges();
    }

    public function _on_deleting()
    {
        // FIXME: Call duplicate checker's dependency handling methods
        return true;
    }

    function get_label()
    {
        if ($this->rname)
        {
            $label = $this->rname;
        }
        else
        {
            $label = $this->username;
        }

        return $label;
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
    function check_password($password , $check_same_password = true)
    {
        //check current password
        if(($this->password == $password) && $check_same_password)
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password is the same as the current one' , 'org.openpsa.contacts') , 'error');
            return false;
        }

        //get last passwords
        $old_passwords = $this->get_old_passwords();

        //check last passwords
        if(in_array($password, $old_passwords))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password was already used' , 'org.openpsa.contacts') , 'error');
            return false;
        }
        return true;
    }

    /**
     * Function to set new password & adds old password to parameter old passwords - does not update()
     *
     * @param string password to set
     */
    function set_password($password)
    {
        $old_passwords_array = $this->get_old_passwords();
        $new_passwords_string = "";
        if(is_array($old_passwords_array))
        {
            array_unshift($old_passwords_array, $this->password);
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
        $this->password = $password;
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
        if($old_passwords_string != "")
        {
            $old_passwords_array = unserialize($old_passwords_string);
            $count = count($old_passwords_array);
            $max = (int) midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('max_old_passwords');
            if($count > $max)
            {
                //only as much passwords as given in config
                for($i = $max;$i <= $count;$i++)
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

    function encrypt_password($password , $plaintext)
    {
        static $rand = false;
        if (empty($rand))
        {
            if (function_exists('mt_rand'))
            {
                $rand = 'mt_rand';
            }
            else
            {
                $rand = 'rand';
            }
        }
        if ($plaintext)
        {
            $password = "**{$password}";
        }
        else
        {
            /*
              It seems having nonprintable characters in the password breaks replication
              Here we recreate salt and hash until we have a combination where only
              printable characters exist
            */
            $crypted = false;
            while (    empty($crypted)
                    || preg_match('/[\x00-\x20\x7f-\xff]/', $crypted))
            {
                $salt = chr($rand(33,125)) . chr($rand(33,125));
                $crypted = crypt($password, $salt);
            }
            $password = $crypted;
            unset($crypted);
        }
        return $password;
    }

    /**
     * Function to check if account with passed name already exists
     *
     * @param string contains name of the account to check
     */
    function check_account_exists($name)
    {
        if (method_exists('midgard_user', 'login'))
        {
            //Midgard2
            $mc = new midgard_collector('midgard_user', 'login', $name);
            $mc->set_key_property('login');
        }
        else
        {
            //Midgard1
            $mc = new midgard_collector($GLOBALS['midcom_config']['person_class'], 'username', $name);
            $mc->set_key_property('username');
        }
        $mc->execute();
        $keys = $mc->list_keys();
        if (empty($keys))
        {
            return false;
        }
        return true;
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

        // score for length & repition
        $pattern_length = 4;
        $score_char = 4;
        $score = $password_length * $score_char;
        for($count = 1;$count <= $pattern_length ; $count++)
        {
            $score += strlen($this->check_repetition($count , $password)) - $password_length ;
        }

        $max = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('min_password_length');
        $rules = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('password_match_score');

        $data_snippet = midcom_helper_misc::get_snippet_content($rules);
        $result = eval ("\$contents = array ( {$data_snippet}\n );");
        if ($result === false)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to parse the schema definition in '{$rules}', see above for PHP errors.");
            // This will exit.
        }
        if($password_length < $max)
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password too short' , 'org.openpsa.contacts') , 'error');
            return false;
        }
        //check $password with rules
        foreach($contents['rules'] as $rule)
        {
            $match = preg_match($rule['match'] , $password);
            if($match > 0)
            {
                $score += $rule['score'];
            }
        }
        if($score <= midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('min_password_score'))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('password weak' , 'org.openpsa.contacts') , 'error');
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
        for ($i=0; $i < strlen($password) ; $i++ )
        {
            $repeated=true;
            for ($j=0; $j < $plen && ($j+$i+$plen) < strlen($password); $j++)
            {
                if(    (substr($password , $j+$i , 1) == substr($password , $j+$i+$plen , 1))
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
                $result .= substr($password , $i, 1);
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
        if($max_age_days == 0)
        {
            return true;
        }
        $max_timeframe = time() - ($max_age_days * 24 * 60 * 60);
        $last_change = $this->get_parameter("org_openpsa_contacts_password", "last_change");

        if(empty($last_change))
        {
            return false;
        }

        if ( $max_timeframe < $last_change )
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
        $timeframe_minutes = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config')->get('password_block_timeframe_min');
        if($timeframe_minutes == 0)
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
        if(!$atstat)
        {
             $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to register interface for re_open the user account, last Midgard error was: " . midcom_connection::get_error_string());
        }
        $this->set_parameter("org_openpsa_contacts_blocked_account", "account_password" , $this->password);
        $this->password = "";
        $this->update();

        return true;
    }
}
?>