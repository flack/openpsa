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
     * Flag to tell us whether we run under midgard2 or not
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

    public function __construct(midcom_db_person &$person)
    {
        $this->_person =& $person;
        if (method_exists('midgard_user', 'login'))
        {
            $this->_midgard2 = true;
        }
        $this->_user = $this->_get_user();
    }

    public static function &get(midcom_db_person &$person)
    {
        if (!array_key_exists($person->guid, self::$_instances))
        {
            self::$_instances[$person->guid] = new self($person);
        }
        return self::$_instances[$person->guid];
    }

    public function save()
    {
        if (!$this->_is_username_unique())
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.contacts', 'org.openpsa.contacts'), $_MIDCOM->i18n->get_string('username already exists', 'org.openpsa.contacts'), 'error');
            return false;
        }

        if (!$this->_user->guid)
        {
            return $this->_create_user();
        }
        else
        {
            return $this->_update();
        }
    }

    public function delete()
    {
        if ($this->_midgard2)
        {
            // Ratatoskr
            if (!$this->_user)
            {
                return false;
            }
            return $this->_user->delete();
        }
        else
        {
            $this->_person->password = '';
            $this->_person->username = '';
            return $this->_person->update();
        }
    }

    public function set_username($username)
    {
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
        else
        {
            // Ragnaroek
            return $this->_person->username;
        }
    }

    /**
     * Modify a query instance for searching by username, with differences between
     * mgd1 and mgd2 abstracted away
     *
     * @param midcom_core_query &$query The QB or MC instance to work on
     * @param string $operator The operator for the username constraint
     * @param string $value The value for the username constraint
     */
    public static function add_username_constraint(midcom_core_query &$query, $operator, $value)
    {
        if (method_exists('midgard_user', 'login'))
        {
            $mc = new midgard_collector('midgard_user', 'metadata.deleted', false);
            $mc->set_key_property('person');
            $mc->add_constraint('username', $operator, $value);
            $mc->add_constraint('authtype', '=', $GLOBALS['midcom_config']['auth_type']);
            $mc->execute();
            $person_guids = $mc->list_keys();
            if (sizeof($person_guids) < 1)
            {
                //make sure we don't return any results
                $query->add_constraint('id', '=', 0);
            }
            else
            {
                $query->add_constraint('guid', 'IN', array_keys($person_guids));
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
     * @param midcom_core_query &$query The QB or MC instance to work on
     * @param string $direction The value for the username constraint
     */
    public static function add_username_order(midcom_core_query &$query, $direction)
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

    private function _create_user()
    {
        if ($this->_user->login == '')
        {
            return false;
        }
        $this->_user->authtype = $GLOBALS['midcom_config']['auth_type'];

        if ($GLOBALS['midcom_config']['person_class'] != 'midgard_person')
        {
            $mgd_person = new midgard_person($this->_person->guid);
        }
        else
        {
            $mgd_person = $this->_person;
        }

        $this->_user->set_person($mgd_person);

        try
        {
            $this->_user->create();
        }
        catch (midgard_error_exception $e)
        {
            return false;
        }
        return true;
    }

    private function _update()
    {
        if ($this->_midgard2)
        {
            $this->_user->login = $this->get_username();
            $this->_user->password = $this->get_password();
            try
            {
                $this->_user->update();
            }
            catch (midgard_error_exception $e)
            {
                return false;
            }
            return true;
        }
        else
        {
            // Ragnaroek
            $this->_person->username = $this->get_username();
            $this->_person->password = $this->get_password();
            return $this->_person->update();
        }
    }

    private function _get_user()
    {
        if ($this->_midgard2)
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
                    new midgard_query_value ($GLOBALS['midcom_config']['auth_type'])));
            $qs->set_constraint($group);
            $qs->toggle_readonly(false);
            $qs->execute();

            $result = $qs->list_objects();
            if (sizeof($result) != 1)
            {
                $tmp = new midgard_user();
                return $tmp;
            }
            return $result[0];
        }
        else
        {
            return $this->_person;
        }
    }

    private function _is_username_unique()
    {
        if ($this->_midgard2)
        {
            $qb = new midgard_query_builder('midgard_user');
            $qb->add_constraint('login', '=', $this->get_username());
            $qb->add_constraint('authtype', '=', $GLOBALS['midcom_config']['auth_type']);
        }
        else
        {
            $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
            $qb->add_constraint('username', '=', $this->get_username());
        }
        $qb->add_constraint('guid', '<>', $this->_user->guid);
        return ($qb->count() == 0);
    }

}
?>