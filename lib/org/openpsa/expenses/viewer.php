<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the class that defines which URLs should be handled by this module.
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_viewer extends midcom_baseclasses_components_request
{
    /**
     * Initialize the request switch, which contains URL handlers for the component
     *
     * @access protected
     */
    function _on_initialize()
    {
        // Handle /hours/task/batch/
        $this->_request_switch['hours_task_action'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_admin', 'batch'),
            'fixed_args' => array('hours', 'task', 'batch'),
        );

        // Handle /hours/task/<guid>
        $this->_request_switch['list_hours_task'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
            'fixed_args' => array('hours', 'task'),
            'variable_args' => 1,
        );

        // Handle /hours/task/all/<guid>
        $this->_request_switch['list_hours_task_all'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
            'fixed_args' => array('hours', 'task', 'all'),
            'variable_args' => 1,
        );

        // Handle /hours/between/<from>/<to>
        $this->_request_switch['list_hours_between'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
            'fixed_args' => array('hours', 'between'),
            'variable_args' => 2,
        );

        // Handle /hours/between/all/<from>/<to>
        $this->_request_switch['list_hours_between_all'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_list', 'list'),
            'fixed_args' => array('hours', 'between', 'all'),
            'variable_args' => 2,
        );

        // Handle /hours/edit/<guid>
        $this->_request_switch['hours_edit'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_admin', 'edit'),
            'fixed_args' => array('hours', 'edit'),
            'variable_args' => 1,
        );

        // Handle /hours/delete/<guid>
        $this->_request_switch['hours_delete'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_admin', 'delete'),
            'fixed_args' => array('hours', 'delete'),
            'variable_args' => 1,
        );

        // Handle /hours/create/<schema>
        $this->_request_switch['hours_create'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
            'fixed_args' => array('hours', 'create'),
            'variable_args' => 1,
        );

        // Handle /hours/create/<schema>/<task>
        $this->_request_switch['hours_create_task'] = array
        (
            'handler' => array('org_openpsa_expenses_handler_hours_admin', 'create'),
            'fixed_args' => array('hours', 'create'),
            'variable_args' => 2,
        );

        // Handle /<timestamp>
        $this->_request_switch['index_timestamp'] = array
        (
            'handler' => Array('org_openpsa_expenses_handler_index', 'index'),
            'variable_args' => 1,
        );
        // Handle /
        $this->_request_switch['index'] = array
        (
            'handler' => Array('org_openpsa_expenses_handler_index', 'index'),
        );
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     *
     * @access protected
     */
    function _populate_node_toolbar($task)
    {
        foreach (array_keys($this->_request_data['schemadb_hours_simple']) as $name)
        {
            $create_url = "hours/create/{$name}/";

            /*
             * @todo Normally, the create links would be under the view toolbar, but
             * this creates problems with the consistency of the button order, f.x.
             * the DM2 save/cancel buttons should always be first. So we move the
             * create link to the node toolbar unless the current page is a task-
             * specific one until a more elegant solution can be found
             */
            $toolbar = '_node_toolbar';

            if ($task)
            {
                $create_url .= $task . "/";
                $toolbar = '_view_toolbar';
            }

            $this->{$toolbar}->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $create_url,
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n_midcom->get('create %s'),
                        $this->_l10n->get($this->_request_data['schemadb_hours_simple'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                )
            );
        }
    }

    /**
     * The handle callback populates the toolbars.
     */
    function _on_handle($handler, $args)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->_request_data['schemadb_hours_simple'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_hours_simple'));
        $task = false;
        if ($handler == 'list_hours_task'
            || $handler == 'list_hours_task_all')
        {
            $task = $args[0];
        }
        if ($handler != 'hours_create'
            && $handler != 'hours_create_task')
        {
            $this->_populate_node_toolbar($task);
        }
        return true;
    }

}

?>