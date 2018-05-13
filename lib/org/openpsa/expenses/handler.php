<?php
/**
 * @package org.openpsa.expenses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;

/**
 * Handler addons
 *
 * @package org.openpsa.expenses
 */
trait org_openpsa_expenses_handler
{
    /**
     * Populates the node toolbar depending on the user's rights.
     */
    public function populate_view_toolbar($prefix = '', $suffix = '')
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_hours'));
        $workflow = $this->get_workflow('datamanager');
        foreach ($schemadb->all() as $name => $schema) {
            $create_url = "hours/create/{$prefix}{$name}/{$suffix}";
            $this->_view_toolbar->add_item($workflow->get_button($create_url, [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($schema->get('description'))),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
            ]));
        }
    }

    /**
     * Apply user filters to hour lists
     *
     * @param midcom_core_query $query The query object to work on
     */
    public function add_list_filter(midcom_core_query $query, $add_time_filter = false)
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
    public function get_person_options()
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
