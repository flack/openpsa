<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: status.php 24778 2010-01-18 11:23:02Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped access to the MgdSchema class, keep logic here
 * @package org.openpsa.projects
 */
class org_openpsa_projects_task_status_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_task_status';

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        $ret = parent::__construct($id);
        if (!$this->id)
        {
            if (is_object($this))
            {
                $this->timestamp = $this->gmtime();
            }
        }
        return $ret;
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

    function get_parent_guid_uncached()
    {
        if ($this->task != 0)
        {
            $parent = new org_openpsa_projects_task_dba($this->task);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    public function _on_creating()
    {
        //Make sure we have timestamp
        if ($this->timestamp == 0)
        {
            $this->timestamp = $this->gmtime();
        }

        //Check for duplicate(s) (for some reason at times the automagic actions in task object try to create duplicate statuses)
        $mc = self::new_collector('task', '=', $this->task);
        $mc->add_constraint('type', '=', $this->type);
        $mc->add_constraint('timestamp', '=', $this->timestamp);
        $mc->add_constraint('comment', '=', $this->comment);
        if ($this->targetPerson)
        {
            $mc->add_constraint('targetPerson', '=', $this->targetPerson);
        }
        $mc->execute();
        if ( $mc->count() > 0)
        {
            debug_add('Duplicate statuses found, aborting create', MIDCOM_LOG_WARN);
            debug_print_r("List of duplicate status objects:", $mc->list_keys());
            return false;
        }

        return true;
    }

    public function _on_created()
    {
        //Remove the resource if necessary
        if ($this->type == ORG_OPENPSA_TASKSTATUS_DECLINED
            && $this->targetPerson)
        {
            $qb = org_openpsa_projects_task_resource_dba();
            $qb->add_constraint('task', '=', $this->task);
            $qb->add_constraint('person', '=', $this->targetPerson);
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
            if ($qb->count() > 0)
            {
                $results = $qb->execute();
                foreach ($results as $result)
                {
                    debug_add("removing user #{$this->targetPerson} from resources");
                    $result->delete();
                }
            }
        }

        $task = org_openpsa_projects_task_dba::get_cached($this->task);

        if ($this->type == ORG_OPENPSA_TASKSTATUS_PROPOSED)
        {
            $recipient = midcom_db_person::get_cached($this->targetPerson);

            //Creator will naturally accept his own proposal...
            if ($recipient->guid == $this->metadata->creator)
            {
                return org_openpsa_projects_workflow::accept($task);
            }
        }


        //See if the parent status needs updating
        if ($task->status == $this->type)
        {
            debug_add("Task status is up to date, returning");
            return;
        }

        $needs_update = false;

        if ($task->status < $this->type)
        {
            // This doesn't really do anything yet, it's moved here from workflow.php
            if ($this->type == ORG_OPENPSA_TASKSTATUS_ACCEPTED)
            {
                switch ($task->acceptanceType)
                {
                    case ORG_OPENPSA_TASKACCEPTANCE_ALLACCEPT:
                        debug_add('Acceptance mode not implemented', MIDCOM_LOG_ERROR);
                        return false;
                        break;
                    case ORG_OPENPSA_TASKACCEPTANCE_ONEACCEPTDROP:
                        debug_add('Acceptance mode not implemented', MIDCOM_LOG_ERROR);
                        return false;
                        break;
                    default:
                    case ORG_OPENPSA_TASKACCEPTANCE_ONEACCEPT:
                        //PONDER: Should this be superseded by generic method for querying the status objects to set the latest status ??
                        debug_add("Required accept received, setting task status to accepted");
                        //
                        $needs_update = true;
                        break;
                }
            }
            //@todo Some more sophisticated checks, for now we just write everything
            else
            {
                $needs_update = true;
            }
        }
        else
        {
            $needs_update = true;
        }

        if ($needs_update)
        {
            debug_add("Setting task status to {$this->type}");
            $task->status = $this->type;

            $task->_skip_acl_refresh = true;
            $task->update();
        }
    }

    function get_status_message()
    {
        switch ($this->type)
        {
            case ORG_OPENPSA_TASKSTATUS_PROPOSED:
                return 'proposed to %s by %s';
            case ORG_OPENPSA_TASKSTATUS_DECLINED:
                return 'declined by %s';
            case ORG_OPENPSA_TASKSTATUS_ACCEPTED:
                return 'accepted by %s';
            case ORG_OPENPSA_TASKSTATUS_ONHOLD:
                return 'put on hold by %s';
            case ORG_OPENPSA_TASKSTATUS_STARTED:
                return 'work started by %s';
            case ORG_OPENPSA_TASKSTATUS_REJECTED:
                return 'rejected by %s';
            case ORG_OPENPSA_TASKSTATUS_REOPENED:
                return 're-opened by %s';
            case ORG_OPENPSA_TASKSTATUS_COMPLETED:
                return 'marked as completed by %s';
            case ORG_OPENPSA_TASKSTATUS_APPROVED:
                return 'approved by %s';
            case ORG_OPENPSA_TASKSTATUS_CLOSED:
                return 'closed by %s';
            default:
                return "{$this->type} by %s";
        }
    }

    function gmtime()
    {
        return gmmktime(date('G'), date('i'), date('s'), date('n'), date('j'), date('Y'));
    }
}
?>