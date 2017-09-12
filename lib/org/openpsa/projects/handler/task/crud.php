<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Projects create/update/delete task handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_crud extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_projects_task_dba
     */
    private $task;

    private $mode;

    /**
     * Generates an object creation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->mode = 'create';
        $this->task = new org_openpsa_projects_task_dba;
        $defaults = [
            'manager' => midcom_connection::get_user()
        ];
        if (count($args) > 0) {
            if ($args[0] == 'project') {
                $parent = new org_openpsa_projects_project($args[1]);
                $defaults['project'] = $parent->id;
            } elseif ($args[0] == 'task') {
                $parent = new org_openpsa_projects_task_dba($args[1]);
                $defaults['project'] = $parent->project;
                $defaults['up'] = $parent->id;
            }
            $parent->require_do('midgard:create');

            $defaults['orgOpenpsaAccesstype'] = $parent->orgOpenpsaAccesstype;
            $defaults['orgOpenpsaOwnerWg'] = $parent->orgOpenpsaOwnerWg;

            // Copy resources and contacts from project
            $parent->get_members();
            $defaults['resources'] = array_keys($parent->resources);
            $defaults['contacts'] = array_keys($parent->contacts);
        } else {
            midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_projects_task_dba');
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('create task'));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller($defaults),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        //Ugly workaround to http://trac.openpsa2.org/ticket/31
        $this->task->refresh_status();

        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        if ($this->mode === 'create') {
            return 'task/' . $this->task->guid . '/';
        }
    }

    /**
     * Generates an object update view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_update($handler_id, array $args, array &$data)
    {
        $this->task = new org_openpsa_projects_task_dba($args[0]);
        $this->task->require_do('midgard:update');
        $data['controller'] = $this->load_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('task')));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    private function load_controller(array $defaults = [])
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_task'));

        $fields = $schemadb->get('default')->get('fields');
        if (!empty($defaults['up']) || !empty($this->task->up)) {
            $fields['project']['widget'] = 'hidden';
            $fields['up']['widget_config']['constraints'] = [
                'field' => 'project',
                'op' => '=',
                'value' => $this->task->project || $defaults['project']
            ];
            $fields['agreement']['widget'] = 'hidden';
        } else {
            $fields['up']['widget'] = 'hidden';
        }
        $schemadb->get('default')->set('fields', $fields);

        $dm = new datamanager($schemadb);
        return $dm->set_defaults($defaults)
            ->set_storage($this->task)
            ->get_controller();
    }

    /**
     * Displays an object delete confirmation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->task = new org_openpsa_projects_task_dba($args[0]);

        $options = ['object' => $this->task];
        try {
            $parent = new org_openpsa_projects_project($this->task->project);
            $options['success_url'] = 'project/' . $parent->guid . '/';
        } catch (midcom_error $e) {
            $e->log();
        }

        $workflow = $this->get_workflow('delete', $options);
        return $workflow->run();
    }
}
