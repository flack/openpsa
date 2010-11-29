<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: crud.php 26462 2010-06-28 12:03:04Z gudd $
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
    /**
     * Simple default constructor.
     */
    public function __construct()
    {
        $this->_dba_class = 'org_openpsa_projects_project';
        $this->_prefix = 'project';
    }

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
    private function _load_schemadb()
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

        $_MIDCOM->set_pagetitle($view_title);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_read($handler_id, &$data)
    {
        midcom_show_style('show-project');
    }

    /**
     * Add toolbar items
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_callback($handler_id, $args, &$data)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        if ($handler_id == 'project')
        {
            $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
            $_MIDCOM->load_library('org.openpsa.contactwidget');
        }
        return true;
    }

    private function _load_defaults()
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
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new project, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_object = new org_openpsa_projects_project($project->id);

        return $this->_object;
    }

    /**
     * Method for adding or updating the project to the MidCOM indexer service.
     *
     * @param $dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = $_MIDCOM->get_service('indexer');

        $nav = new midcom_helper_nav();
        //get the node to fill the required index-data for topic/component
        $node = $nav->get_node($nav->get_current_node());

        $document = $indexer->new_document($dm);
        $document->topic_guid = $node[MIDCOM_NAV_GUID];
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $node[MIDCOM_NAV_COMPONENT];

        if ($indexer->index($document))
        {
            return true;
        }
        return false;
    }
}
?>