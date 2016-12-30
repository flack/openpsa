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
     * Change tracking variable
     *
     * @var string
     */
    private $_old_username;

    /**
     * @param object midgard_person, midcom_db_person or similar
     */
    public function __construct($person)
    {
        $this->_person = $person;
        $this->_user = $this->_get_user();
    }

    public function save()
    {
        midcom::get()->auth->require_do('midgard:update', $this->_person);
        if (!$this->_is_username_unique()) {
            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('midcom'), midcom::get()->i18n->get_string('username already exists', 'org.openpsa.contacts'), 'error');
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }

        if (!$this->_user->guid) {
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
        if (!$this->_user->delete()) {
            return false;
        }
        $user = new midcom_core_user($this->_person);
        midcom::get()->auth->sessionmgr->_delete_user_sessions($user);

        // Delete all ACL records which have the user as assignee
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('assignee', '=', $user->id);
        if ($result = $qb->execute()) {
            foreach ($result as $entry) {
                debug_add("Deleting privilege {$entry->privilegename} ID {$entry->id} on {$entry->objectguid}");
                $entry->delete();
            }
        }

        return true;
    }

    public function set_username($username)
    {
        $this->_old_username = $this->get_username();
        $this->_user->login = $username;
    }

    /**
     * Set the account's password
     *
     * @param string $password The password to set
     * @param boolean $encode Should the password be encoded according to the configured auth type
     */
    public function set_password($password, $encode = true)
    {
        if ($encode) {
            $password = midcom_connection::prepare_password($password);
        }
        $this->_user->password = $password;
    }

    public function set_usertype($type)
    {
        $this->_user->usertype = $type;
    }

    public function get_password()
    {
        return $this->_user->password;
    }

    public function get_username()
    {
        return $this->_user->login;
    }

    public function get_usertype()
    {
        return $this->_user->usertype;
    }

    /**
     * Modify a query instance for searching by username
     *
     * @param midcom_core_query $query The QB or MC instance to work on
     * @param string $operator The operator for the username constraint
     * @param string $value The value for the username constraint
     */
    public static function add_username_constraint(midcom_core_query $query, $operator, $value)
    {
        $mc = new midgard_collector('midgard_user', 'authtype', midcom::get()->config->get('auth_type'));
        $mc->set_key_property('person');

        if (   $operator !== '='
            || $value !== '') {
            $mc->add_constraint('login', $operator, $value);
        }
        $mc->execute();
        $user_results = $mc->list_keys();

        if (   $operator === '='
            && $value === '') {
            $query->add_constraint('guid', 'NOT IN', array_keys($user_results));
        } elseif (count($user_results) < 1) {
            // make sure we don't return any results if no midgard_user entry was found
            $query->add_constraint('id', '=', 0);
        } else {
            $query->add_constraint('guid', 'IN', array_keys($user_results));
        }
    }

    /**
     * Add username order to a query instance
     *
     * Note that it actually does nothing right now, because it's still
     * unclear how this could be implemented
     *
     * @param midcom_core_query $query The QB or MC instance to work on
     * @param string $direction The value for the username constraint
     */
    public static function add_username_order(midcom_core_query $query, $direction)
    {
        debug_add('Ordering persons by username is not yet implemented for Midgard2', MIDCOM_LOG_ERROR);
        //@todo Find a way to do this
    }

    public function is_admin()
    {
        return $this->_user->is_admin();
    }

    private function _create_user()
    {
        if ($this->_user->login == '') {
            return false;
        }
        $this->_user->authtype = midcom::get()->config->get('auth_type');
        $this->_user->set_person(new midgard_person($this->_person->guid));
        $this->_user->active = true;

        return $this->_user->create();
    }

    private function _update()
    {
        $stat = false;
        $new_username = $this->get_username();
        $new_password = $this->get_password();

        $this->_user->login = $new_username;
        $this->_user->password = $new_password;
        if (!$this->_user->update()) {
            return false;
        }

        if (   !empty($this->_old_username)
            && $this->_old_username !== $new_username) {
            $user = new midcom_core_user($this->_person);
            midcom::get()->auth->sessionmgr->_update_user_username($user, $new_username);
            if (!$history = @unserialize($this->_person->get_parameter('midcom', 'username_history'))) {
                $history = array();
            }
            $history[time()] = array('old' => $this->_old_username, 'new' => $new_username);
            $this->_person->set_parameter('midcom', 'username_history', serialize($history));
        }
        return true;
    }

    private function _get_user()
    {
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('person', '=', $this->_person->guid);
        $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        $result = $qb->execute();
        if (sizeof($result) != 1) {
            return new midgard_user();
        }
        return $result[0];
    }

    private function _is_username_unique()
    {
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('login', '=', $this->get_username());
        $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        $qb->add_constraint('guid', '<>', $this->_user->guid);
        return ($qb->count() == 0);
    }
}
