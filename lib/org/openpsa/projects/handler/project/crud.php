<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Projects create/read/update/delete project handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_project_crud extends midcom_baseclasses_components_handler_crud
{
    public $_dba_class = 'org_openpsa_projects_project';
    public $_prefix = 'project';

    /**
     * Method for adding the supported operations into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _populate_toolbar($handler_id)
    {
        if (   $this->_mode == 'create'
            || $this->_mode == 'update')
        {
            org_openpsa_helpers::dm2_savecancel($this);
        }
        else if ($this->_mode == 'read')
        {
            $this->_add_read_toolbar($handler_id);
        }
    }

    /**
     * Special helper for adding the supported operations from read into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    private function _add_read_toolbar($handler_id)
    {
        // Add toolbar items
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "project/edit/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',

            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "task/new/project/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create task'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
            )
        );
        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_object->guid);
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_project'));
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    public function _update_breadcrumb($handler_id)
    {
        org_openpsa_projects_viewer::add_breadcrumb_path($this->_object, $this);

        switch ($handler_id)
        {
            case 'project_edit':
                $this->add_breadcrumb("project/edit/{$this->_object->guid}/", $this->_l10n_midcom->get('edit'));
                break;
            case 'project_delete':
                $this->add_breadcrumb("project/delete/{$this->_object->guid}/", $this->_l10n_midcom->get('delete'));
                break;
        }
    }

    /**
     * Method for updating title for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        switch ($this->_mode)
        {
            case 'create':
                $view_title = $this->_l10n->get('create project');
                break;
            case 'read':
                $object_title = $this->_object->get_label();
                $view_title = $object_title;
                break;
            case 'update':
                $view_title = sprintf($this->_l10n_midcom->get('edit %s'), $this->_object->get_label());
                break;
            case 'delete':
                $view_title = sprintf($this->_l10n_midcom->get('delete %s'), $this->_object->get_label());
                break;
        }

        midcom::get('head')->set_pagetitle($view_title);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_read($handler_id, array &$data)
    {
        midcom_show_style('show-project');
    }

    /**
     * Add toolbar items
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_callback($handler_id, array $args, array &$data)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        if ($handler_id == 'project')
        {
            $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
            org_openpsa_widgets_contact::add_head_elements();
        }
    }

    public function _load_defaults()
    {
        $this->_defaults['manager'] = midcom_connection::get_user();
    }

    /**
     * This is what Datamanager calls to actually create a project
     */
    function & dm2_create_callback(&$controller)
    {
        $project = new org_openpsa_projects_project();

        if (! $project->create())
        {
            debug_print_r('We operated on this object:', $project);
            throw new midcom_error("Failed to create a new project. Error: " . midcom_connection::get_error_string());
        }

        $this->_object = new org_openpsa_projects_project($project->id);

        return $this->_object;
    }

    /**
     * Method for adding or updating the project to the MidCOM indexer service.
     *
     * @param &$dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        return $indexer->index($dm);
    }
}
?>