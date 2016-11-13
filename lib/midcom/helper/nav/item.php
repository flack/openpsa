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

    public function __get($name)
    {
        $name = $this->translate_name($name);
        $data = $this->get_data();
        if (!array_key_exists($name, $data)) {
            return null;
        }
        return $data[$name];
    }

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
        return array_key_exists($name, $data);
    }

    public function get_data()
    {
        if (!$this->loaded) {
            $this->data = $this->prepare_data();
            $this->loaded = true;
        }
        return $this->data;
    }

    private function translate_name($name)
    {
        $const = 'MIDCOM_NAV_' . strtoupper($name);
        if (defined($const)) {
            $name = constant($const);
        }
        return $name;
    }
}