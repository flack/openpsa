<?php
/**
 * @package midcom.helper.nav
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property string $guid
 * @property mixed $id
 * @property string $type
 * @property string $name
 * @property string $component
 * @property string $url
 * @property string $relativeurl
 * @property string $absoluteurl
 * @property string $fullurl
 * @property string $permalink
 * @property mixed $object
 * @property boolean $noentry
 * @property int $nodeid
 * @package midcom.helper.nav
 */
abstract class midcom_helper_nav_item
{
    protected $data;

    protected $loaded = false;

    abstract protected function prepare_data() : array;

    abstract public function is_readable_by(string $user_id) : bool;

    protected function get_cache() : midcom_services_cache_module_nap
    {
        return midcom::get()->cache->nap;
    }

    public function __get($name)
    {
        $name = $this->translate_name($name);
        $data = $this->get_data();
        if (!array_key_exists($name, $data)) {
            return null;
        }
        return $data[$name];
    }

    /**
     * Magic setter. Also allows setting arbitrary non-constant keys for backward compatibility
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $name = $this->translate_name($name);
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
            $this->loaded = true;
        }
        $this->data[$name] = $value;
    }

    public function  __isset($name)
    {
        $data = $this->get_data();
        $name = $this->translate_name($name);

        return array_key_exists($name, $data);
    }

    private function translate_name(string $name)
    {
        $const = 'MIDCOM_NAV_' . strtoupper($name);
        if (defined($const)) {
            $name = constant($const);
        }
        return $name;
    }

    /**
     * Checks if the NAP object is visible within the current runtime environment.
     * This includes checks for:
     *
     * - Nonexistent NAP information (null values)
     * - Scheduling/Hiding (only on-site)
     * - Approval (only on-site)
     * - ACL
     */
    public function is_visible() : bool
    {
        if (empty($this->get_data())) {
            debug_add('Got a null value as napdata, so this object does not have any NAP info, so we cannot display it.');
            return false;
        }

        $ret = true;

        if (is_object($this->object)) {
            // Check Hiding, Scheduling and Approval
            if ($this->object->metadata) {
                $ret = $this->object->metadata->is_object_visible_onsite();
            } else {
                // For some reason, the metadata for this object could not be retrieved. so we skip
                // Approval/Visibility checks.
                debug_add("Warning, no Metadata available for the {$this->type} {$this->guid}.", MIDCOM_LOG_INFO);
            }
        }

        if ($ret) {
            $user_id = midcom::get()->auth->admin ? false : midcom::get()->auth->acl->get_user_id();
            return $this->is_readable_by($user_id);
        }

        return $ret;
    }

    public function get_data() : array
    {
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
            $this->loaded = true;
        }
        return $this->data;
    }
}
