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

    private $loaded = false;

    abstract protected function prepare_data();

    /**
     * @return midcom_services_cache_module_nap
     */
    protected function get_cache()
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
        }
        $this->data[$name] = $value;
    }

    public function  __isset($name)
    {
        $data = $this->get_data();
        $name = $this->translate_name($name);

        return array_key_exists($name, $data);
    }

    private function translate_name($name)
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
     *
     * @return boolean Indicating visibility.
     */
    public function is_object_visible()
    {
        if (is_null($this->get_data())) {
            debug_add('Got a null value as napdata, so this object does not have any NAP info, so we cannot display it.');
            return false;
        }

        // Check the Metadata if and only if we are configured to do so.
        if (   is_object($this->object)
            && (   midcom::get()->config->get('show_hidden_objects') == false
                || midcom::get()->config->get('show_unapproved_objects') == false)) {
            // Check Hiding, Scheduling and Approval
            $metadata = $this->object->metadata;

            if (!$metadata) {
                // For some reason, the metadata for this object could not be retrieved. so we skip
                // Approval/Visibility checks.
                debug_add("Warning, no Metadata available for the {$this->type} {$this->guid}.", MIDCOM_LOG_INFO);
                return true;
            }

            return $metadata->is_object_visible_onsite();
        }

        return true;
    }

    public function get_data()
    {
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
            $this->loaded = true;
        }
        return $this->data;
    }
}