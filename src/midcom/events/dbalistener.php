<?php
/**
 * @package midcom.events
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\events;

/**
 * midcom DBA listener
 *
 * @package midcom.events
 */
class dbalistener
{
    private $_classes = array();

    private $_component;

    private $_operations = array(
        dbaevent::CREATE => \MIDCOM_OPERATION_DBA_CREATE,
        dbaevent::UPDATE => \MIDCOM_OPERATION_DBA_UPDATE,
        dbaevent::DELETE => \MIDCOM_OPERATION_DBA_DELETE,
        dbaevent::IMPORT => \MIDCOM_OPERATION_DBA_IMPORT,
    );

    public function __construct($component, array $classes = array())
    {
        $this->_component = $component;
        $this->_classes = $classes;
    }

    public function handle_event(dbaevent $event, $name)
    {
        $object = $event->get_object();
        $found = empty($this->_classes);
        foreach ($this->_classes as $classname) {
            if (is_a($object, $classname)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return;
        }

        try {
            $interface = \midcom::get()->componentloader->get_interface_class($this->_component);
        } catch (\midcom_error $e) {
            debug_add("Failed to load the component {$this->_component}: " . $e->getMessage(), MIDCOM_LOG_INFO);
            return;
        }
        $operation = $this->_operations[$name];
        debug_add("Calling [{$this->_component}]_interface->trigger_watch({$operation}, \$object)");

        $interface->trigger_watch($operation, $object);
    }
}
