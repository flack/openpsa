<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_csv extends midcom_baseclasses_components_handler_dataexport
{
    public function __construct()
    {
        $this->include_guid = false;
        $this->include_totals = true;
    }

    function _load_schemadb($handler_id, &$args, &$data)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $_MIDCOM->skip_page_style = true;
        if(isset($args[0]))
        {
            $data['schemadb_to_use'] = 'schemadb_csvexport_' . $args[0];
        }
        if (   isset($_GET['filename'])
            && is_string($_GET['filename'])
            && strpos($_GET['filename'], '.csv'))
        {
            $data['filename'] = $_GET['filename'];
        }

        $this->_schema = 'default';

        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get($data['schemadb_to_use']));
        return $schemadb;
    }

    function _load_data($handler_id, &$args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        if (   !isset($_POST['guids'])
            || !is_array($_POST['guids'])
            || empty($_POST['guids']))
        {
            throw new midcom_error("No GUIDs found, aborting.");
        }
        $guids =& $_POST['guids'];

        $dummy_object = $_MIDCOM->dbfactory->get_object_by_guid($guids[0]);
        $dba_class = get_class($dummy_object);

        $qb = call_user_func(array($dba_class, 'new_query_builder'));
        $qb->add_constraint('guid', 'IN', $guids);
        if (   isset($_POST['order'])
            && is_array($_POST['order']))
        {
            foreach ($_POST['order'] as $field=> $order)
            {
                $qb->add_order($field, $order);
            }
        }

        $results = $qb->execute();

        return $results;
    }
}
?>