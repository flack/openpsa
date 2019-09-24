<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

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
     * @var string
     */
    private $mode;

    /**
     * Generates an object creation view.
     */
    public function _handler_create(Request $request)
    {
        $this->mode = 'create';
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_projects_project::class);
        $this->project = new org_openpsa_projects_project();

        $defaults = [
            'manager' => midcom_connection::get_user()
        ];

        midcom::get()->head->set_pagetitle($this->_l10n->get('create project'));
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller($defaults),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        if ($this->mode === 'create') {
            return $this->router->generate('project', ['guid' => $this->project->guid]);
        }
    }

    /**
     * Generates an object update view.
     */
    public function _handler_update(Request $request, string $guid, array &$data)
    {
        $this->project = new org_openpsa_projects_project($guid);
        $this->project->require_do('midgard:update');
        $data['controller'] = $this->load_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('project')));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $data['controller'],
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    /**
     * Displays an object delete confirmation view.
     */
    public function _handler_delete(Request $request, string $guid)
    {
        $project = new org_openpsa_projects_project($guid);

        $workflow = $this->get_workflow('delete', ['object' => $project]);
        return $workflow->run($request);
    }

    private function load_controller(array $defaults = []) : controller
    {
        return datamanager::from_schemadb($this->_config->get('schemadb_project'))
            ->set_defaults($defaults)
            ->set_storage($this->project)
            ->get_controller();
    }
}
