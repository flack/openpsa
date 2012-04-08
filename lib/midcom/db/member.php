<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Membership record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_member extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_member';

    public function get_label()
    {
        $person = new midcom_db_person($this->uid);
        $grp = new midcom_db_group($this->gid);
        return sprintf(midcom::get('i18n')->get_string('%s in %s', 'midcom'), $person->name, $grp->official);
    }

    /**
     * Returns the group the membership record is associated with. This allows group
     * owners to manage their members.
     *
     * @return midcom_db_group The owning group or null if the gid is undefined.
     */
    function get_parent_guid_uncached()
    {
        if ($this->gid)
        {
            try
            {
                $parent = new midcom_db_group($this->gid);
            }
            catch (midcom_error $e)
            {
                debug_add("Could not load Group ID {$this->gid} from the database, aborting.",
                    MIDCOM_LOG_INFO);
                return null;
            }
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    /**
     * Invalidate person's cache when a member record changes
     */
    private function _invalidate_person_cache()
    {
        if (!$this->uid)
        {
            return;
        }
        try
        {
            $person = new midcom_db_person($this->uid);
        }
        catch (midcom_error $e)
        {
            return;
        }
        midcom::get('cache')->invalidate($person->guid);
    }

    public function _on_creating()
    {
        // Allow root group membership creation only for admins
        if ($this->gid == 0)
        {
            if (!midcom::get('auth')->admin)
            {
                debug_add("Group #0 membership creation only allowed for admins");
                debug_print_function_stack('Forbidden ROOT member creation called from');
                return false;
            }
        }

        // Disable automatic activity stream entry, we use custom here
        $this->_use_activitystream = false;

        return true;
    }

    public function _on_updating()
    {
        // Allow root group membership creation only for admins (check update as well to avoid sneaky bastards
        if ($this->gid == 0)
        {
            if (midcom::get('auth')->admin)
            {
                debug_add("Group #0 membership creation only allowed for admins");
                debug_print_function_stack('Forbidden ROOT member creation called from');
                return false;
            }
        }
        return true;
    }

    public function _on_deleting()
    {
        // Disable automatic activity stream entry, we use custom here
        $this->_use_activitystream = false;

        return true;
    }

    public function _on_created()
    {
        $this->_invalidate_person_cache();

        if (!midcom::get('auth')->request_sudo('midcom'))
        {
            return true;
        }

        // Create an Activity Log entry for the membership addition
        $actor = midcom_db_person::get_cached($this->uid);
        $target = midcom_db_group::get_cached($this->gid);
        $activity = new midcom_helper_activitystream_activity_dba();
        $activity->target = $target->guid;
        $activity->actor = $actor->id;
        $this->verb = 'http://activitystrea.ms/schema/1.0/join';
        if (   isset(midcom::get('auth')->user)
            && isset(midcom::get('auth')->user->guid)
            && $actor->guid == midcom::get('auth')->user->guid)
        {
            $this->summary = sprintf(midcom::get('i18n')->get_string('%s joined group %s', 'midcom'), $actor->name, $target->official);
        }
        else
        {
            $this->summary = sprintf(midcom::get('i18n')->get_string('%s was added to group %s', 'midcom'), $actor->name, $target->official);
        }
        $activity->create();

        midcom::get('auth')->drop_sudo();
    }

    public function _on_updated()
    {
        $this->_invalidate_person_cache();
    }

    public function _on_deleted()
    {
        $this->_invalidate_person_cache();

        if (!midcom::get('auth')->request_sudo('midcom'))
        {
            return;
        }

        // Create an Activity Log entry for the membership addition
        try
        {
            $actor = midcom_db_person::get_cached($this->uid);
            $target = midcom_db_group::get_cached($this->gid);
        }
        catch (midcom_error $e)
        {
            $e->log();
            return;
        }
        $activity = new midcom_helper_activitystream_activity_dba();
        $activity->target = $target->guid;
        $activity->actor = $actor->id;
        $activity->verb = 'http://community-equity.org/schema/1.0/leave';
        if (    midcom::get('auth')->is_valid_user()
             && $actor->guid == midcom::get('auth')->user->guid)
        {
            $activity->summary = sprintf(midcom::get('i18n')->get_string('%s left group %s', 'midcom'), $actor->name, $target->official);
        }
        else
        {
            $activity->summary = sprintf(midcom::get('i18n')->get_string('%s was removed from group %s', 'midcom'), $actor->name, $target->official);
        }
        $activity->create();

        midcom::get('auth')->drop_sudo();
    }
}
?>