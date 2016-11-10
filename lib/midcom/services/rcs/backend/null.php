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
class midcom_services_rcs_backend_null implements midcom_services_rcs_backend
{
    public function __construct($object, $config) {}

    public function update($object, $updatemessage = null)
    {
        return true;
    }

    public function get_revision($revision)
    {
        return false;
    }

    public function version_exists($version)
    {
        return false;
    }

    public function get_prev_version($version)
    {
        return false;
    }

    public function get_next_version($version)
    {
        return false;
    }

    public function list_history_numeric()
    {
        return array();
    }

    public function list_history()
    {
        return array();
    }

    public function get_diff($oldest_revision, $latest_revision)
    {
        return array();
    }

    public function get_comment($revision)
    {
        return false;
    }

    public function restore_to_revision($revision)
    {
        return false;
    }
}
