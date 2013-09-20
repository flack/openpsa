<?php
/**
 * @package midcom.events
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\events;

use Symfony\Component\EventDispatcher\Event;

/**
 * midcom DBA listener
 *
 * @package midcom.events
 */
class dbaevent extends Event
{
    const CREATE = 'midcom:create';
    const UPDATE = 'midcom:update';
    const DELETE = 'midcom:delete';
    const IMPORT = 'midcom:import';

    private $_object;

    public function __construct($object)
    {
        $this->_object = $object;
    }

    public function get_object()
    {
        return $this->_object;
    }
}
