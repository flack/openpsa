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

    public function set_password($password)
    {
        if ($this->_midgard2)
        {
            $this->_user->password = midcom_connection::prepare_password($password);
        }
        else
        {
            $this->_person->password = midcom_connection::prepare_password($password);
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
            $qb = new midgard_query_builder('midgard_user');
            $qb->add_constraint('person', '=', $this->_person->guid);
            $qb->add_constraint('authtype', '=', $GLOBALS['midcom_config']['auth_type']);
            $result = $qb->execute();
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