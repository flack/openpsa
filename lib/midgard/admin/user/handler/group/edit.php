<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Edit a group
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_group_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    private $_group = null;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midgard.admin.user'), $this->_request_data);
    }

    /**
     * Populate breadcrumb
     */
    private function _update_breadcrumb()
    {
        $this->add_breadcrumb("__mfa/asgard_midgard.admin.user/", $this->_l10n->get('midgard.admin.user'));
        $this->add_breadcrumb('__mfa/asgard_midgard.admin.user/group', $this->_l10n->get('groups'));

        $tmp = array();

        $grp = $this->_group;
        while ($grp) {
            $tmp[$grp->guid] = $grp->official;
            $grp = $grp->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $guid => $title) {
            $this->add_breadcrumb('__mfa/asgard_midgard.admin.user/group/edit/' . $guid . '/', $title);
        }
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->_group->id);
        if (   $qb->count_unchecked() > $this->_config->get('list_users_max')
            && isset($schemadb['default']->fields['persons'])) {
            unset($schemadb['default']->fields['persons']);
            $field_order_key = array_search('persons', $schemadb['default']->field_order);
            if ($field_order_key !== false) {
                unset($schemadb['default']->field_order[$field_order_key]);
            }
        }
        return $schemadb;
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_group = new midcom_db_group($args[0]);
        $this->_group->require_do('midgard:update');

        $controller = $this->get_controller('simple', $this->_group);
        switch ($controller->process_form()) {
            case 'save':
                // Show confirmation for the group
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->name));
                return new midcom_response_relocate("__mfa/asgard_midgard.admin.user/group/edit/{$this->_group->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midgard.admin.user/');
        }

        $data['group'] = $this->_group;
        $data['controller'] = $controller;

        $ref = new midcom_helper_reflector($this->_group);
        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $ref->get_object_title($this->_group));

        $this->_update_breadcrumb();

        $data['asgard_toolbar']->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/group/move/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('move group'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
            )
        );

        $data['asgard_toolbar']->add_item(
            array(
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midgard.admin.user/group/folders/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('folders'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/folder.png',
            )
        );
        midgard_admin_asgard_plugin::bind_to_object($this->_group, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('midgard-admin-user-group-edit');
    }
}
