<?php
/**
 * @package midcom.helper.activitystream
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Activity Log library interface
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package midcom.helper.activitystream
 */
class midcom_helper_activitystream_interface extends midcom_baseclasses_components_interface
{
    public function _on_watched_operation($operation, $object)
    {
        if (!$object->_use_activitystream)
        {
            // Activity Log not used for this object
            return;
        }

        // Create an activity log entry
        if (!midcom::get('auth')->request_sudo('midcom.helper.activitystream'))
        {
            // Not allowed to create activity logs
            return;
        }

        $activity = new midcom_helper_activitystream_activity_dba();
        $activity->target = $object->guid;

        if ($object->_activitystream_verb)
        {
            $activity->verb = $object->_activitystream_verb;
        }
        else
        {
            $activity->verb = midcom_helper_activitystream_activity_dba::operation_to_verb($operation);
        }
        if (!$activity->verb)
        {
            debug_add('Cannot generate a verb for the activity, skipping');
            midcom::get('auth')->drop_sudo();
            return;
        }

        static $handled_targets = array();
        if (isset($handled_targets["{$activity->target}_{$activity->actor}"]))
        {
            // We have already created an entry for this object in this request, skip
            return;
        }

        if ($object->_rcs_message)
        {
            $activity->summary = $object->_rcs_message;
        }

        if (midcom::get('auth')->user)
        {
            $actor = midcom::get('auth')->user->get_storage();
            $activity->actor = $actor->id;
        }

        $activity->application = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);

        if ($activity->create())
        {
            $handled_targets["{$activity->target}_{$activity->actor}"] = true;
        }

        midcom::get('auth')->drop_sudo();
    }
}
?>