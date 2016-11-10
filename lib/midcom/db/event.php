<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Legacy Database Abstraction Layer
 *
 * This class encapsulates a classic MidgardEvent with its original features.
 *
 * <i>Preliminary Implementation:</i>
 *
 * Be aware that this implementation is incomplete, and grows on a is-needed basis.
 *
 * @package midcom.db
 */
class midcom_db_event extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_event';

    public $autodelete_dependents = array(
        'midcom_db_eventmember' => 'eid'
    );

    public function get_label()
    {
        if ($this->start == 0) {
            return $this->title;
        }
        $formatter = midcom::get()->i18n->get_l10n()->get_formatter();
        return $formatter->date($this->start) . " {$this->title}";
    }

    /**
     * Returns a prepared query builder which lists all eventmember records for this event.
     * No translation to persons is done.
     *
     * @return midcom_core_querybuilder A prepared QB instance.
     */
    function get_event_members_qb()
    {
        $qb = midcom_db_eventmember::new_query_builder();
        $qb->add_constraint('eid', '=', $this->id);
        return $qb;
    }

    /**
     * Returns an unsorted list of event members for this event.
     *
     * @return midcom_db_eventmember[]
     */
    function list_event_members()
    {
        $qb = $this->get_event_members_qb();
        return $qb->execute();
    }
}
