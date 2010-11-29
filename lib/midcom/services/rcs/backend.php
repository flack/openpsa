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
    public function __construct(&$object, &$config);

    /**
     * This function saves a revision of an object, creating a revision
     * if none exists
     *
     * @return boolean true if save succeeded.
     * @throws MIDCOM_ERRCRIT on serious errors.
     * @param string $comment the message to be saved with the object.
     */
    public function update(&$object, $comment);

    public function get_revision($revision);

    public function version_exists($version);

    public function get_prev_version($version);

    public function get_next_version($version);

    public function list_history_numeric();

    public function list_history();

    public function get_diff($oldest_revision, $latest_revision);

    public function get_comment($revision);

    public function restore_to_revision($revision);
}
?>