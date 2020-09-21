<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

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
     * @var midcom_db_person
     */
    private $_person;

    /**
     * The current account
     *
     * @var midgard_user
     */
    private $_user;

    /**
     * Change tracking variable
     *
     * @var string
     */
    private $_old_username;

    /**
     * @param object $person midgard_person, midcom_db_person or similar
     */
    public function __construct($person)
    {
        $this->_person = $person;
        $this->_user = $this->_get_user();
    }

    public function save() : bool
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
     * the user that is managed by the core (like privilege records).
     *
     * This call requires the delete privilege on the person object, this is enforced using
     * require_do.
     *
     * @return boolean Indicating success.
     */
    public function delete() : bool
    {
        midcom::get()->auth->require_do('midgard:delete', $this->_person);
        if (!$this->_user->delete()) {
            return false;
        }
        $user = new midcom_core_user($this->_person);

        // Delete all ACL records which have the user as assignee
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('assignee', '=', $user->id);
        foreach ($qb->execute() as $entry) {
            debug_add("Deleting privilege {$entry->privilegename} on {$entry->objectguid}");
            $entry->purge();
        }

        return true;
    }

    public function set_username(string $username)
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
    public function set_password(string $password, bool $encode = true)
    {
        if ($encode) {
            $password = midcom_connection::prepare_password($password);
        }
        $this->_user->password = $password;
    }

    public function set_usertype(int $type)
    {
        $this->_user->usertype = $type;
    }

    public function get_password() : string
    {
        return $this->_user->password;
    }

    public function get_username() : string
    {
        return $this->_user->login;
    }

    public function get_usertype() : int
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
    public static function add_username_constraint(midcom_core_query $query, string $operator, $value)
    {
        $qb = $query->get_doctrine();
        if (empty($query->join_added)) {
            $qb->leftJoin('midgard_user', 'u', Join::WITH, 'u.person = c.guid');
            $query->join_added = true;
        }
        $query->get_current_group()->add('u.login ' . $operator . ' :value AND u.authtype = :authtype');
        $qb->setParameter('value', $value);
        $qb->setParameter('authtype', midcom::get()->config->get('auth_type'));
    }

    /**
     * Add username order to a query instance
     */
    public static function add_username_order(midcom_core_query $query, string $direction)
    {
        $qb = $query->get_doctrine();
        if (empty($query->join_added)) {
            $qb->leftJoin('midgard_user', 'u', Join::WITH, 'u.person = c.guid');
            $query->join_added = true;
        }
        $qb->addOrderBy('u.login', $direction);
    }

    public function is_admin() : bool
    {
        return $this->_user->is_admin();
    }

    private function _create_user() : bool
    {
        if ($this->_user->login == '') {
            return false;
        }
        $this->_user->authtype = midcom::get()->config->get('auth_type');
        $this->_user->set_person(new midgard_person($this->_person->guid));
        $this->_user->active = true;

        return $this->_user->create();
    }

    private function _update() : bool
    {
        $new_username = $this->get_username();
        $new_password = $this->get_password();

        $this->_user->login = $new_username;
        $this->_user->password = $new_password;
        if (!$this->_user->update()) {
            return false;
        }

        if (   !empty($this->_old_username)
            && $this->_old_username !== $new_username) {
            if (!$history = @unserialize($this->_person->get_parameter('midcom', 'username_history'))) {
                $history = [];
            }
            $history[time()] = ['old' => $this->_old_username, 'new' => $new_username];
            $this->_person->set_parameter('midcom', 'username_history', serialize($history));
        }
        return true;
    }

    private function _get_user() : midgard_user
    {
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('person', '=', $this->_person->guid);
        $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        $result = $qb->execute();
        if (count($result) != 1) {
            return new midgard_user();
        }
        return $result[0];
    }

    private function _is_username_unique() : bool
    {
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('login', '=', $this->get_username());
        $qb->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        $qb->add_constraint('guid', '<>', $this->_user->guid);
        return $qb->count() == 0;
    }
}
