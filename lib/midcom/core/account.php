<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for account management
 *
 * @package midcom
 */
class midcom_core_account
{
    /**
     * The person the account belongs to
     *
     * @param midcom_db_person
     */
    private $_person;

    /**
     * The current account
     *
     * @param object
     */
    private $_user;

    /**
     * Flag to tell us whether we use midgard2 API or not
     *
     * @param boolean
     */
    private $_midgard2 = false;

    /**
     * Currently open accounts
     *
     * @param array
     */
    private static $_instances = array();

    /**
     * Change tracking variables
     *
     * @var string
     */
    private $_new_password;
    private $_old_password;
    private $_old_username;

    /**
     * @param object midgard_person, midcom_db_person or similar
     */
    public function __construct($person)
    {
        $this->_person = $person;
        if (method_exists('midgard_user', 'login'))
        {
            $this->_midgard2 = true;
        }
        $this->_user = $this->_get_user();
    }

    /**
     * Static account getter
     *
     * @param midcom_db_person $person
     * @throws midcom_error
     * @return midcom_core_account
     * @deprecated This can cause all kinds of headaches, especially under midgard1, use only for read access
     */
    public static function get($person)
    {
        if (empty($person->guid))
        {
            throw new midcom_error('Empty person GUID');
        }
        if (!array_key_exists($person->guid, self::$_instances))
        {
            self::$_instances[$person->guid] = new self($person);
        }
        return self::$_instances[$person->guid];
    }

    public function save()
    {
        midcom::get()->auth->require_do('midgard:update', $this->_person);
        if (!$this->_is_username_unique())
        {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('midcom'), midcom::get()->i18n->get_string('username already exists', 'org.openpsa.contacts'), 'error');
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }
        //empty cache, since we might have come from a separate instantiation
        unset(self::$_instances[$this->_person->guid]);
        if (!$this->_user->guid)
        {
            return $this->_create_user();
        }
        return $this->_update();
    }

    /**
     * Deletes the current user account.
     *
     * This will cleanup all information associated with
     * the user that is managed by the core (like login sessions and privilege records).
     *
     * This call requires the delete privilege on the person object, this is enforced using
     * require_do.
     *
     * @return boolean Indicating success.
     */
    public function delete()
    {
        midcom::get()->auth->require_do('midgard:delete', $this->_person);
        if ($this->_midgard2)
        {
            // Ratatoskr
            if (!$this->_user)
            {
                return false;
            }
            $stat = $this->_user->delete();
        }
        else
        {
            $this->_person->password = '';
            $this->_person->username = '';
            $stat = $this->_person->update();
        }
        if (!$stat)
        {
            return false;
        }
        $user = new midcom_core_user($this->_person);
        midcom::get()->auth->sessionmgr->_delete_user_sessions($user);

        // Delete all ACL records which have the user as assignee
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('assignee', '=', $user->id);
        if ($result = @$qb->execute())
        {
            foreach ($result as $entry)
            {
                debug_add("Deleting privilege {$entry->privilegename} ID {$entry->id} on {$entry->objectguid}");
                $entry->delete();
            }
        }
        unset(self::$_instances[$this->_person->guid]);
        return true;
    }

    public function set_username($username)
    {
        $this->_old_username = $this->get_username();
        if ($this->_midgard2)
        {
            $this->_user->login = $username;
        }
        else
        {
            $this->_person->username = $username;
        }
    }

    /**
     * Set the account's password
     *
     * @param string $password The password to set
     * @param boolean $encode Should the password be encoded according to the configured auth type
     */
    public function set_password($password, $encode = true)
    {
        $this->_new_password = $password;
        $this->_old_password = $this->get_password();
        if ($encode)
        {
            $password = midcom_connection::prepare_password($password);
        }
        if ($this->_midgard2)
        {
            $this->_user->password = $password;
        }
        else
        {
            $this->_person->password = $password;
        }
    }

    public function set_usertype($type)
    {
        if (!$this->_midgard2)
        {
            throw new midcom_error('Currently unsupported under midgard 1');
        }
        $this->_user->usertype = $type;
    }

    public function get_password()
    {
        return $this->_user->password;
    }

    public function get_username()
    {
        if ($this->_midgard2)
        {
            // Ratatoskr
            return $this->_user->login;
        }
        // Ragnaroek
        return $this->_person->username;
    }

    public function get_usertype()
    {
        if (!$this->_midgard2)
        {
            throw new midcom_error('Currently unsupported under midgard 1');
        }
        return $this->_user->usertype;
    }

    /**
     * Modify a query instance for searching by username, with differences between
     * mgd1 and mgd2 abstracted away
     *
     * @param midcom_core_query $query The QB or MC instance to work on
     * @param string $operator The operator for the username constraint
     * @param string $value The value for the username constraint
     */
    public static function add_username_constraint(midcom_core_query $query, $operator, $value)
    {
        if (method_exists('midgard_user', 'login'))
        {
            $mc = new midgard_collector('midgard_user', 'authtype', midcom::get()->config->get('auth_type'));
            $mc->set_key_property('person');

            if (   $operator !== '='
                || $value !== '')
            {
                $mc->add_constraint('login', $operator, $value);
            }
            $mc->execute();
            $user_results = $mc->list_keys();

            if (   $operator === '='
                && $value === '')
            {
                $query->add_constraint('guid', 'NOT IN', array_keys($user_results));
            }
            else if (count($user_results) < 1)
            {
                // make sure we don't return any results if no midgard_user entry was found
                $query->add_constraint('id', '=', 0);
            }
            else
            {
                $query->add_constraint('guid', 'IN', array_keys($user_results));
            }
        }
        else
        {
            $query->add_constraint('username', $operator, $value);
        }
    }

    /**
     * Add username order to a query instance, with differences between
     * mgd1 and mgd2 abstracted away.
     *
     * Note that it actually does nothing under mgd2 right now, because it's still
     * unclear how this could be implemented
     *
     * @param midcom_core_query $query The QB or MC instance to work on
     * @param string $direction The value for the username constraint
     */
    public static function add_username_order(midcom_core_query $query, $direction)
    {
        if (method_exists('midgard_user', 'login'))
        {
            debug_add('Ordering persons by username is not yet implemented for Midgard2', MIDCOM_LOG_ERROR);
            //@todo Find a way to do this
        }
        else
        {
            $query->add_order('username', $direction);
        }
    }

    public function is_admin()
    {
        // mgd2
        if ($this->_user instanceof midgard_user)
        {
            return $this->_user->is_admin();
        }
        /*
          In principle, this code works under midgard1, but it causes

          invalid uninstantiatable type `(null)' in cast to `midgard_user'

          in later code, so we can only return false for users other than the current one

          $user = new midgard_user(midcom::get()->dbfactory->convert_midcom_to_midgard($this->_person));
          return $user->is_admin();
        */
        if (   midcom::get()->auth->user
            && midcom::get()->auth->user->guid === $this->_person->guid)
        {
            return midcom::get()->auth->admin;
        }
        return false;
    }

    private function _create_user()
    {
        if ($this->_user->login == '')
        {
            return false;
        }
        $this->_user->authtype = midcom::get()->config->get('auth_type');

        if (midcom::get()->config->get('person_class') != 'midgard_person')
        {
            $mgd_person = new midgard_person($this->_person->guid);
        }
        else
        {
            $mgd_person = $this->_person;
        }
        $this->_user->set_person($mgd_person);
        $this->_user->active = true;

        try
        {
            return $this->_user->create();
        }
        catch (midgard_error_exception $e)
        {
            return false;
        }
    }

    private function _update()
    {
        $stat = false;
        $new_username = $this->get_username();
        $new_password = $this->get_password();

        if ($this->_midgard2)
        {
            $this->_user->login = $new_username;
            $this->_user->password = $new_password;
            try
            {
                $stat = $this->_user->update();
            }
            catch (midgard_error_exception $e)
            {
                $e->log();
            }
        }
        else
        {
            // Ragnaroek
            $this->_person->username = $new_username;
            $this->_person->password = $new_password;
            $stat = $this->_person->update();
        }
        if (!$stat)
        {
            return false;
        }

        $user = new midcom_core_user($this->_person);

        if (   !empty($this->_old_password)
            && $this->_old_password !== $new_password)
        {
            midcom::get()->auth->sessionmgr->_update_user_password($user, $this->_new_password);
        }
        if (   !empty($this->_old_username)
            && $this->_old_username !== $new_username)
        {
            midcom::get()->auth->sessionmgr->_update_user_username($user, $new_username);
            if (!$history = @unserialize($this->_person->get_parameter('midcom', 'username_history')))
            {
                $history = array();
            }
            $history[time()] = array('old' => $this->_old_username, 'new' => $new_username);
            $this->_person->set_parameter('midcom', 'username_history', serialize($history));
        }
        return true;
    }

    private function _get_user()
    {
        if ($this->_midgard2)
        {
            if (class_exists('midgard_query_storage'))
            {
                $storage = new midgard_query_storage('midgard_user');
                $qs = new midgard_query_select($storage);

                $group = new midgard_query_constraint_group('AND');
                $group->add_constraint (
                    new midgard_query_constraint (
                        new midgard_query_property ('person'),
                        '=',
                        new midgard_query_value ($this->_person->guid)));
                $group->add_constraint (
                    new midgard_query_constraint (
                        new midgard_query_property ('authtype'),
                        '=',
                        new midgard_query_value (midcom::get()->config->get('auth_type'))));
                $qs->set_constraint($group);
                $qs->toggle_readonly(false);
                $qs->execute();

                $result = $qs->list_objects();
            }
            else
            {
                $qb = new midgard_query_builder('midgard_user');
                $qb->add_constraint('person', '=', $this->_person->guid);
                $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
                $result = $qb->execute();
            }
            if (sizeof($result) != 1)
            {
                return new midgard_user();
            }
            return $result[0];
        }
        return $this->_person;
    }

    private function _is_username_unique()
    {
        if ($this->_midgard2)
        {
            $qb = new midgard_query_builder('midgard_user');
            $qb->add_constraint('login', '=', $this->get_username());
            $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        }
        else
        {
            $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
            $qb->add_constraint('username', '=', $this->get_username());
        }
        $qb->add_constraint('guid', '<>', $this->_user->guid);
        return ($qb->count() == 0);
    }

}
