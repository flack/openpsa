<?php
/**
 * @package midcom.events
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\events;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * midcom event dispatcher
 *
 * @package midcom.events
 */
class dispatcher extends EventDispatcher
{
    /**
     * This array contains all registered MidCOM operation watches. They are indexed by
     * operation and map to components / libraries which have registered to classes.
     * Values consist of an array whose first element is the component and subsequent
     * elements are the types involved (so a single count means all objects).
     *
     * @var array
     */
    private $watches = [
        \MIDCOM_OPERATION_DBA_CREATE => dbaevent::CREATE,
        \MIDCOM_OPERATION_DBA_UPDATE => dbaevent::UPDATE,
        \MIDCOM_OPERATION_DBA_DELETE => dbaevent::DELETE,
        \MIDCOM_OPERATION_DBA_IMPORT => dbaevent::IMPORT,
    ];

    /**
     * Compat function for ragnaroek-style events.
     *
     * @param int $operation_id One of the MIDCOM_OPERATION_DBA_ constants
     * @param \midcom_core_dbaobject $object The current object
     */
    public function trigger_watch($operation_id, $object)
    {
        $event_name = $this->watches[$operation_id];
        $event = new dbaevent($object);
        $this->dispatch($event_name, $event);
    }

    public function add_watches(array $watches, $component)
    {
        foreach ($watches as $watch) {
            foreach ($this->watches as $operation_id => $event_name) {
                // Check whether the operations flag list from the component
                // contains the operation_id we're checking a watch for.
                if ($watch['operations'] & $operation_id) {
                    $listener = new watcher($component, $watch['classes']);
                    $this->addListener($event_name, [$listener, 'handle_event']);
                }
            }
        }
    }
}
