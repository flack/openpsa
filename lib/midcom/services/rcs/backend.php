<?php
/**
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.services.rcs
 */
interface midcom_services_rcs_backend
{
    public function __construct($object, midcom_services_rcs_config $config);

    /**
     * Save a revision of an object, or create a revision if none exists
     *
     * @param midcom_core_dbaobject $object the object to save.
     * @param string $updatemessage the message to be saved with the object.
     * @return boolean true if save succeeded.
     * @throws midcom_error on serious errors.
     */
    public function update($updatemessage = null) : bool;

    public function get_revision($revision) : array;

    public function get_history() : ?midcom_services_rcs_history;

    public function get_diff($oldest_revision, $latest_revision) : array;

    public function restore_to_revision($revision) : bool;
}
