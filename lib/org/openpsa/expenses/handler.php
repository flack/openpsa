<?php
/**
 * @package org.openpsa.expenses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.expenses
 */
trait org_openpsa_expenses_handler
{

    /**
     * Apply user filters to hour lists
     */
    public function add_list_filter(midcom_core_query $query, bool $add_time_filter = false)
    {
        $qf = new org_openpsa_core_queryfilter('org_openpsa_expenses_list');
        $person_filter = new org_openpsa_core_filter_multiselect('person');
        $person_filter->set_callback([$this, 'get_person_options']);
        $person_filter->set_label($this->_l10n->get("choose user"));
        $qf->add_filter($person_filter);

        if ($add_time_filter) {
            $date_filter = new org_openpsa_core_filter_timeframe('date');
            $date_filter->set_label($this->_l10n->get("timeframe"));
            $qf->add_filter($date_filter);
        }
        $qf->apply_filters($query);
        $this->_request_data["qf"] = $qf;
    }

    /**
     * List options for the person filter
     */
    public function get_person_options() : array
    {
        $qb = midcom_db_person::new_query_builder();
        midcom_core_account::add_username_constraint($qb, '<>', '');

        $person_array = [];
        foreach ($qb->execute() as $person) {
            $person_array[$person->id] = $person->get_label();
        }
        return $person_array;
    }
}
