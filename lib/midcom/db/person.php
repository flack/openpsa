<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Person record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * @package midcom.db
 * @see midcom_services_dbclassloader
 */
class midcom_db_person extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_person';

    /**
     * Read-Only variable, consisting of "$firstname $lastname".
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $name = '';

    /**
     * Read-Only variable, consisting of "$lastname, $firstname".
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $rname = '';

    /**
     * Read-Only variable, consisting of a complete A HREF tag to homepage
     * if set.
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $homepagelink = '';

    /**
     * Read-Only variable, consisting of ta complete mailto A HREF tag to
     * the set email addres.
     *
     * Updated during all DB operations.
     *
     * @var string
     */
    public $emaillink = '';

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    public function __construct($id = null)
    {
        $this->__mgdschema_class_name__ = $GLOBALS['midcom_config']['person_class'];
        parent::__construct($id);
    }

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     */
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * Updates all computed members.
     *
     * @access protected
     */
    public function _on_loaded()
    {
        $this->_update_computed_members();
        return true;
    }

    /**
     * Deletes all group and event memberships of the original person record. SUDO privileges
     * are used at this point, since only memberships are associated to the groups, not persons
     * and event memberships belong to the event, again not to the person.
     */
    public function _on_deleted()
    {
        if (! $_MIDCOM->auth->request_sudo('midcom'))
        {
            debug_add('Failed to get SUDO privileges, skipping membership deletion silently.', MIDCOM_LOG_ERROR);
            return;
        }

        // Delete group memberships
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $this->id);
        $result = $qb->execute();
        if ($result)
        {
            foreach ($result as $membership)
            {
                if (! $membership->delete())
                {
                    debug_add("Failed to delete membership record {$membership->id}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }

        // Delete event memberships
        $qb = midcom_db_eventmember::new_query_builder();
        $qb->add_constraint('uid', '=', $this->id);
        $result = $qb->execute();
        if ($result)
        {
            foreach ($result as $membership)
            {
                if (! $membership->delete())
                {
                    debug_add("Failed to delete event membership record {$membership->id}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }

        $_MIDCOM->auth->drop_sudo();
    }

    /**
     * Updates all computed members and adds a midgard:owner privilege for the person itself
     * on the record.
     *
     * @access protected
     */
    public function _on_created()
    {
        $this->set_privilege('midgard:owner', "user:{$this->guid}");

        $this->_update_computed_members();
    }

    /**
     * Updates all computed members.
     *
     * @access protected
     */
    public function _on_updated()
    {
        $this->_update_computed_members();
    }

    /**
     * Synchronizes the $name, $rname, $emaillink and $homepagelink members
     * with the members they are based on.
     *
     * @access protected
     */
    private function _update_computed_members()
    {
        @$this->name = trim("{$this->firstname} {$this->lastname}");

        @$this->rname = trim($this->lastname);
        if ($this->rname == '')
        {
            @$this->rname = $this->firstname;
        }
        else
        {
            @$this->rname .= ", {$this->firstname}";
        }

        if ($this->homepage != '')
        {
            $title = htmlspecialchars($this->name);
            $url = htmlspecialchars($this->homepage);
            @$this->homepagelink = "<a href=\"{$url}\" title=\"{$title}\">{$url}</a>";
        }

        if ($this->email != '')
        {
            $title = htmlspecialchars($this->name);
            $url = htmlspecialchars($this->email);
            @$this->emaillink = "<a href=\"mailto:{$url}\" title=\"{$title}\">{$url}</a>";
        }
    }

    public function get_label()
    {
        if ($this->rname)
        {
            return $this->rname;
        }
        else if ($this->username)
        {
            return $this->username;
        }
        else
        {
        	return '#' . $this->id;
        }
    }

    /**
     * Adds a user to a given Midgard Group. Caller must ensure access permissions
     * are right.
     *
     * @param string $name The name of the group we should be added to.
     * @return boolean Indicating success.
     *
     * @todo Check if user is already assigned to the group.
     */
    function add_to_group($name)
    {
        $group = $_MIDCOM->auth->get_midgard_group_by_name($name);
        if (! $group)
        {
            debug_add("Failed to add the person {$this->id} to group {$name}, the group does not exist.", MIDCOM_LOG_WARN);
            return false;
        }
        $storage = $group->get_storage();
        $member = new midcom_db_member();
        $member->uid = $this->id;
        $member->gid = $storage->id;
        if (! $member->create())
        {
            debug_add("Failed to add the person {$this->id} to group {$name}, object could not be created.", MIDCOM_LOG_WARN);
            debug_add('Last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            debug_print_r('Tried to create this object:', $member);
            return false;
        }
        return true;
    }
}
?>