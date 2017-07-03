<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Projects create/update/delete project handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_project_crud extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_projects_project
     */
    private $project;

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
        midcom::get()->auth->require_user_do('midgard:create', null, $this->_dba_class);
        $this->project = new org_openpsa_projects_project();

        $defaults = [
            'manager' => midcom_connection::get_user()
        ];

        midcom::get()->head->set_pagetitle($this->_l10n->get('create project'));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller($defaults),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        if ($this->_mode === 'create') {
            return 'project/' . $this->project->guid . '/';
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
        $this->project = new org_openpsa_projects_project($args[0]);
        $this->project->require_do('midgard:update');
        $data['controller'] = $this->load_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('edit project %s'), $this->project->get_label()));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
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
        $this->project = new org_openpsa_projects_project($args[0]);

        $workflow = $this->get_workflow('delete', ['object' => $this->project]);
        return $workflow->run();
    }

    private function load_controller(array $defaults = [])
    {
        return datamanager::from_schemadb($this->_config->get('schemadb_project'))
            ->set_defaults($defaults)
            ->set_storage($this->project)
            ->get_controller();
    }
}
