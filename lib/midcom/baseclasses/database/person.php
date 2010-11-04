<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: person.php 26119 2010-05-16 14:57:32Z bergie $
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
 * @package midcom.baseclasses
 * @see midcom_services_dbclassloader
 */
class midcom_baseclasses_database_person extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_person';

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

    function __construct($id = null)
    {
        $this->__mgdschema_class_name__ = $GLOBALS['midcom_config']['person_class'];
        parent::__construct($id);
    }

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
    function _on_loaded()
    {
        if (! parent::_on_loaded())
        {
            return false;
        }

        $this->_update_computed_members();
        return true;
    }

    /**
     * Deletes all group and event memberships of the original person record. SUDO privileges
     * are used at this point, since only memberships are associated to the groups, not persons
     * and event memberships belong to the event, again not to the person.
     */
    function _on_deleted()
    {
        parent::_on_deleted();

        if (! $_MIDCOM->auth->request_sudo('midcom'))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Failed to get SUDO privileges, skipping membership deletion silently.', MIDCOM_LOG_ERROR);
            debug_pop();
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
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Failed to delete membership record {$membership->id}, last Midgard error was: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_pop();
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
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Failed to delete event membership record {$membership->id}, last Midgard error was: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_pop();
                }
            }
        }

        $_MIDCOM->auth->drop_sudo();

        return;
    }

    /**
     * Updates all computed members and adds a midgard:owner privilege for the person itself
     * on the record.
     *
     * @access protected
     */
    function _on_created()
    {
        parent::_on_created();

        $this->set_privilege('midgard:owner', "user:{$this->guid}");

        $this->_update_computed_members();
        return true;
    }

    /**
     * Updates all computed members.
     *
     * @access protected
     */
    function _on_updated()
    {
        parent::_on_updated();
        $this->_update_computed_members();
        return true;
    }

    /**
     * Synchronizes the $name, $rname, $emaillink and $homepagelink members
     * with the members they are based on.
     *
     * @access protected
     */
    function _update_computed_members()
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
}

?>
