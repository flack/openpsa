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
class watcher
{
    private $classes = [];

    private $component;

    private $operations = [
        dbaevent::CREATE => \MIDCOM_OPERATION_DBA_CREATE,
        dbaevent::UPDATE => \MIDCOM_OPERATION_DBA_UPDATE,
        dbaevent::DELETE => \MIDCOM_OPERATION_DBA_DELETE,
        dbaevent::IMPORT => \MIDCOM_OPERATION_DBA_IMPORT,
    ];

    public function __construct($component, array $classes = [])
    {
        $this->component = $component;
        $this->classes = $classes;
    }

    public function handle_event(dbaevent $event, $name)
    {
        $object = $event->get_object();
        $found = empty($this->classes);
        foreach ($this->classes as $classname) {
            if (is_a($object, $classname)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return;
        }

        try {
            $interface = \midcom::get()->componentloader->get_interface_class($this->component);
        } catch (\midcom_error $e) {
            debug_add("Failed to load the component {$this->component}: " . $e->getMessage(), MIDCOM_LOG_INFO);
            return;
        }
        $operation = $this->operations[$name];
        debug_add("Calling [{$this->component}]_interface->trigger_watch({$operation}, \$object)");

        $interface->trigger_watch($operation, $object);
    }
}
