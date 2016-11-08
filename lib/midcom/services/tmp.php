<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Temporary data management service.
 *
 * Currently the class supports management of temporary MidCOM objects.
 * One example where they are used is the creation mode wrapper of DM2.
 * No sessioning is done on the side of this class, you need to do that
 * yourself.
 *
 * Temporary objects are identified by their object ID, they are not
 * safely replicateable. A MidCOM CRON handler will delete all temporary
 * objects which have not been accessed for longer than one hour. Nevertheless
 * you should explicitly delete temporary objects you do no longer need using
 * the default delete call.
 *
 * This service is available as midcom::get()->tmp.
 *
 * <b>Temporary object privileges</b>
 *
 * All temporary objects have no privilege restrictions whatsoever. Everybody
 * has full control (that is, midgard:owner privileges) on the temporary object.
 * Newly created temporary objects have all their privileges reset, so that
 * applications can store privileges on temporary object which will be transferred
 * using the move_extensions_to_object() call on the temporary object (see there).
 *
 * @package midcom.services
 */
class midcom_services_tmp
{
    /**
     * This class creates a new temporary object for use with the application.
     * The id member of the object is used in the future to reference it in
     * request and release operations. The GUID of the object should not be used
     * for further references.
     *
     * In case the temporary object cannot be created, midcom_error is thrown.
     *
     * All existing privileges (created by the DBA core) will be dropped, so that
     * privileges can be created at will by the application (f.x. using DM(2)
     * privilege types). Since EVERYONE owns all temporary objects using magic
     * default privileges, full access is ensured.
     *
     * @return midcom_core_temporary_object The newly created object.
     */
    function create_object()
    {
        midcom::get()->auth->require_user_do('midgard:create', null, 'midcom_core_temporary_object');

        $tmp = new midcom_core_temporary_object();
        if (!$tmp->create())
        {
            debug_print_r('Tried to create this object:', $tmp);
            throw new midcom_error('Failed to create a new temporary object, last Midgard error was: ' . midcom_connection::get_error_string());
        }

        $tmp->unset_all_privileges();

        return $tmp;
    }

    /**
     * Loads the temporary object identified by the argument from the DB, verifies
     * ownership (using the midgard:owner privilege) and returns the instance.
     *
     * The object timestamp is updated implicitly by the temporary object class.
     *
     * Objects which have exceeded their lifetime will no longer be returned for
     * security reasons.
     *
     * @param int $id The temporary object ID to load.
     * @return midcom_core_temporary_object The associated object or null in case that it
     *     is unavailable.
     */
    function request_object($id)
    {
        if (!$id)
        {
            debug_add("Invalid argument, may not evaluate to false", MIDCOM_LOG_INFO);
            debug_print_r('Got this argument:', $id);
            return null;
        }

        $tmp = new midcom_core_temporary_object((int) $id);
        if (!$tmp->can_do('midgard:owner'))
        {
            debug_add("The current user does not have owner privileges on the temporary object {$id}, denying access.",
                MIDCOM_LOG_INFO);
            debug_print_r('Got this object:', $tmp);
            return null;
        }

        // Check if the object has timeouted already.
        $timeout = time() - midcom::get()->config->get('midcom_temporary_resource_timeout');
        if ($tmp->timestamp < $timeout)
        {
            debug_add("The temporary object {$id}  has exceeded its maximum lifetime, rejecting access and dropping it",
                MIDCOM_LOG_INFO);
            debug_print_r("Object was:", $tmp);
            $tmp->delete();
            return null;
        }

        // Update the object timestamp
        $tmp->update();

        return $tmp;
    }
}
