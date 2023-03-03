<?php
/**
 * @package midcom.events
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\events;

use Symfony\Contracts\EventDispatcher\Event;
use midcom_core_dbaobject;

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
    const APPROVE = 'midcom:approve';
    const UNAPPROVE = 'midcom:unapprove';
    const PARAMETER = 'midcom:parameter';

    private midcom_core_dbaobject $_object;

    public function __construct(midcom_core_dbaobject $object)
    {
        $this->_object = $object;
    }

    public function get_object() : midcom_core_dbaobject
    {
        return $this->_object;
    }
}
