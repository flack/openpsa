<?php
/**
 * @package midcom.events
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom_core_dbaobject;
use midcom_helper__componentloader;

/**
 * midcom DBA subscriber
 *
 * @package midcom.events
 */
class watcher implements EventSubscriberInterface
{
    private $watches;

    private $map = [
        dbaevent::CREATE => \MIDCOM_OPERATION_DBA_CREATE,
        dbaevent::UPDATE => \MIDCOM_OPERATION_DBA_UPDATE,
        dbaevent::DELETE => \MIDCOM_OPERATION_DBA_DELETE,
        dbaevent::IMPORT => \MIDCOM_OPERATION_DBA_IMPORT,
    ];

    /**
     * @var midcom_helper__componentloader
     */
    private $loader;

    public function __construct(midcom_helper__componentloader $loader, array $watches)
    {
        $this->loader = $loader;
        $this->watches = $watches;
    }

    public static function getSubscribedEvents()
    {
        return [
            dbaevent::CREATE => ['handle_event'],
            dbaevent::UPDATE => ['handle_event'],
            dbaevent::DELETE => ['handle_event'],
            dbaevent::IMPORT => ['handle_event'],
        ];
    }

    private function check_class(midcom_core_dbaobject $object, array $classes) : bool
    {
        $found = empty($classes);
        foreach ($classes as $classname) {
            if (is_a($object, $classname)) {
                return true;
            }
        }
        return $found;
    }

    public function handle_event(dbaevent $event, $name)
    {
        $object = $event->get_object();
        $operation = $this->map[$name];
        foreach ($this->watches[$operation] as $watch) {
            $classes = current($watch);
            if (!$this->check_class($object, $classes)) {
                continue;
            }
            $component = key($watch);
            try {
                $interface = $this->loader->get_interface_class($component);
            } catch (\midcom_error $e) {
                debug_add("Failed to load the component {$component}: " . $e->getMessage(), MIDCOM_LOG_INFO);
                continue;
            }
            debug_add("Calling [{$component}]_interface->trigger_watch({$operation}, \$object)");

            $interface->trigger_watch($operation, $object);
        }
    }
}
