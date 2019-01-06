<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Edit a group
 *
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_group_edit extends midcom_baseclasses_components_handler
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
        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get('midgard.admin.user'));
        $this->add_breadcrumb($this->router->generate('group_list'), $this->_l10n->get('groups'));

        $tmp = [];

        $grp = $this->_group;
        while ($grp) {
            $tmp[$grp->guid] = $grp->official;
            $grp = $grp->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $guid => $title) {
            $this->add_breadcrumb($this->router->generate('group_edit', ['guid' => $guid]), $title);
        }
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param string $guid The object's GUID
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, $guid, array &$data)
    {
        $this->_group = new midcom_db_group($guid);
        $this->_group->require_do('midgard:update');

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_group'));
        $dm->set_storage($this->_group);
        $form = $dm->get_form();

        if ($form->has('persons')) {
            $qb = midcom_db_member::new_query_builder();
            $qb->add_constraint('gid', '=', $this->_group->id);

            if ($qb->count_unchecked() > $this->_config->get('list_users_max')) {
                $form->remove('persons');
            }
        }
        $data['controller'] = $dm->get_controller();
        switch ($data['controller']->process()) {
            case 'save':
                // Show confirmation for the group
                midcom::get()->uimessages->add($this->_l10n->get('midgard.admin.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->name));
                return new midcom_response_relocate($this->router->generate('group_edit', ['guid' => $this->_group->guid]));

            case 'cancel':
                return new midcom_response_relocate($this->router->generate('user_list'));
        }

        $data['group'] = $this->_group;

        $ref = new midcom_helper_reflector($this->_group);
        $data['view_title'] = sprintf($this->_l10n_midcom->get('edit %s'), $ref->get_object_title($this->_group));

        $this->_update_breadcrumb();

        $data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('group_move', ['guid' => $this->_group->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('move group'),
            MIDCOM_TOOLBAR_GLYPHICON => 'arrows',
        ]);

        $data['asgard_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('group_folders', ['guid' => $this->_group->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('folders'),
            MIDCOM_TOOLBAR_GLYPHICON => 'folder',
        ]);
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
