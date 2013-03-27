<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
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

    /**
     * org.openpsa.projects status types
     * //Templates/Drafts
     */
    const DRAFT = 6450;
    const TEMPLATE = 6451;
    const PROPOSED = 6500;
    const DECLINED = 6510;
    const ACCEPTED = 6520;
    const ONHOLD = 6530;
    const STARTED = 6540;
    const REJECTED = 6545;
    const REOPENED = 6550;
    const COMPLETED = 6560;
    const APPROVED = 6570;
    const CLOSED = 6580;

    //org.openpsa.projects acceptance negotiation types
    const ACCEPTANCE_ALLACCEPT = 6700;
    const ACCEPTANCE_ONEACCEPT = 6701;
    const ACCEPTANCE_ONEACCEPTDROP = 6702;

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($id);
        if (!$this->id)
        {
            $this->timestamp = $this->gmtime();
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
        if (   $this->type == self::DECLINED
            && $this->targetPerson)
        {
            $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
            $qb->add_constraint('task', '=', $this->task);
            $qb->add_constraint('person', '=', $this->targetPerson);
            $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_projects_task_resource_dba::RESOURCE);
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

        $this->_update_task();
    }

    private function _update_task()
    {
        $task = org_openpsa_projects_task_dba::get_cached($this->task);

        if ($this->type == self::PROPOSED)
        {
            try
            {
                $recipient = midcom_db_person::get_cached($this->targetPerson);

                //Creator will naturally accept his own proposal...
                if ($recipient->guid == $this->metadata->creator)
                {
                    return org_openpsa_projects_workflow::accept($task, 0, $this->comment);
                }
            }
            catch (midcom_error $e)
            {
                $e->log();
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
            if ($this->type == self::ACCEPTED)
            {
                switch ($task->acceptanceType)
                {
                    case self::ACCEPTANCE_ALLACCEPT:
                    case self::ACCEPTANCE_ONEACCEPTDROP:
                        debug_add('Acceptance mode not implemented', MIDCOM_LOG_ERROR);
                        return false;
                        break;
                    default:
                    case self::ACCEPTANCE_ONEACCEPT:
                        //PONDER: Should this be superseded by generic method for querying the status objects to set the latest status ??
                        debug_add("Required accept received, setting task status to accepted");
                        //
                        $needs_update = true;
                        break;
                }
            }
            //TODO Some more sophisticated checks, for now we just write everything
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
            case self::PROPOSED:
                return 'proposed to %s by %s';
            case self::DECLINED:
                return 'declined by %s';
            case self::ACCEPTED:
                return 'accepted by %s';
            case self::ONHOLD:
                return 'put on hold by %s';
            case self::STARTED:
                return 'work started by %s';
            case self::REJECTED:
                return 'rejected by %s';
            case self::REOPENED:
                return 're-opened by %s';
            case self::COMPLETED:
                return 'marked as completed by %s';
            case self::APPROVED:
                return 'approved by %s';
            case self::CLOSED:
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