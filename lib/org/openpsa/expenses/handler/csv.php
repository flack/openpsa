<?php
/**
 * @package org.openpsa.expenses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;

/**
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_csv extends midcom_baseclasses_components_handler_dataexport
{
    public $include_guid = false;
    public $include_totals = true;
    public $_schema = 'hour_report';

    public function _load_schemadbs($handler_id, array &$args, array &$data)
    {
        if (   isset($_GET['filename'])
            && is_string($_GET['filename'])
            && strpos($_GET['filename'], '.csv')) {
            $data['filename'] = $_GET['filename'];
        }
        return [schemadb::from_path($this->_config->get('schemadb_hours'))];
    }

    public function _load_data($handler_id, array &$args, array &$data)
    {
        if (   empty($_POST['guids'])
            || !is_array($_POST['guids'])) {
            throw new midcom_error("No GUIDs found, aborting.");
        }

        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('guid', 'IN', $_POST['guids']);
        if (   isset($_POST['order'])
            && is_array($_POST['order'])) {
            foreach ($_POST['order'] as $field => $order) {
                $qb->add_order($field, $order);
            }
        }

        return $qb->execute();
    }
}
