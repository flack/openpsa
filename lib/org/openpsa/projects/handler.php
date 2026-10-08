<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.projects
 */
trait org_openpsa_projects_handler
{
    public function add_create_buttons()
    {
        $workflow = $this->get_workflow('datamanager');
        if (midcom::get()->auth->can_user_do('midgard:create', class: org_openpsa_projects_project::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('project-new'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                MIDCOM_TOOLBAR_GLYPHICON => 'tasks',
            ]));
        }
        if (midcom::get()->auth->can_user_do('midgard:create', class: org_openpsa_projects_task_dba::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('task-new'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_GLYPHICON => 'calendar-check-o',
            ]));
        }
    }
}
