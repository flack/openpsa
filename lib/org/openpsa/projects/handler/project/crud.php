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
     * @inheritdoc
     */
    public function _get_object_url(midcom_core_dbaobject $object)
    {
        return 'project/' . $object->guid . '/';
    }

    /**
     * Addg the supported operations into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _populate_toolbar($handler_id)
    {
        if ($this->_mode == 'read') {
            $this->_add_read_toolbar($handler_id);
        }
    }

    /**
     * Add the supported operations from read into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    private function _add_read_toolbar($handler_id)
    {
        $workflow = $this->get_workflow('datamanager2');
        $buttons = array();
        if ($this->_object->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("project/edit/{$this->_object->guid}/", array(
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ));
            $buttons[] = $workflow->get_button("task/new/project/{$this->_object->guid}/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
            ));
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($sales_url = $siteconfig->get_node_full_url('org.openpsa.sales')) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => $sales_url . "salesproject/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('salesproject', 'org.openpsa.sales'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
            );
        }
        $this->_view_toolbar->add_items($buttons);
        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_object->guid);
    }

    /**
     * Load and prepare the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_project'));
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param string $handler_id
     */
    public function _update_breadcrumb($handler_id)
    {
        org_openpsa_projects_viewer::add_breadcrumb_path($this->_object, $this);
    }

    /**
     * Update title for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        switch ($this->_mode) {
            case 'create':
                $view_title = $this->_l10n->get('create project');
                break;
            case 'read':
                $view_title = $this->_object->get_label();
                break;
            case 'update':
                $view_title = sprintf($this->_l10n->get('edit project %s'), $this->_object->get_label());
                break;
        }

        midcom::get()->head->set_pagetitle($view_title);
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
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_callback($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'project') {
            org_openpsa_widgets_grid::add_head_elements();
            org_openpsa_widgets_contact::add_head_elements();
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.js');
            midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/filter.css');
        }
    }

    public function _load_defaults()
    {
        $this->_defaults['manager'] = midcom_connection::get_user();
    }

    /**
     * This is what Datamanager calls to actually create a project
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_object = new org_openpsa_projects_project();

        if (!$this->_object->create()) {
            debug_print_r('We operated on this object:', $this->_object);
            throw new midcom_error("Failed to create a new project. Error: " . midcom_connection::get_error_string());
        }

        return $this->_object;
    }

    /**
     * Add or update the project to the MidCOM indexer service.
     *
     * @param &$dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        return $indexer->index($dm);
    }
}
