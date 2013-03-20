<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Eventmember record with framework support.
 *
 * An event member has its event as explicit parent, *not* its person.
 *
 * @package midcom.db
 */
class midcom_db_eventmember extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_eventmember';

    public function get_label()
    {
        $person = new midcom_db_person($this->uid);
        $event = new midcom_db_event($this->eid);
        return sprintf(midcom::get('i18n')->get_string('%s in %s', 'midcom'), $person->name, $event->title);
    }
}
?>