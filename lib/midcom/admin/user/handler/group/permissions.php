<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_group_permissions extends midcom_baseclasses_components_handler
{
    private $_group = null;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    /**
     * Populate breadcrumb
     */
    private function _update_breadcrumb()
    {
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get('midcom.admin.user'));

        $tmp = array();
        $grp = $this->_group;

        while ($grp) {
            $tmp[$grp->guid] = $grp->official;
            $grp = $grp->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $guid => $title) {
            $this->add_breadcrumb('__mfa/asgard_midcom.admin.user/group/edit/' . $guid . '/', $title);
        }
        $this->add_breadcrumb('', $this->_l10n->get('folders'));
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_folders($handler_id, array $args, array &$data)
    {
        $this->_group = new midcom_db_group($args[0]);
        midgard_admin_asgard_plugin::bind_to_object($this->_group, $handler_id, $data);

        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('assignee', '=', "group:{$this->_group->guid}");
        $qb->add_constraint('objectguid', '<>', '');
        $privileges = $qb->execute();
        $data['objects'] = array();
        $data['privileges'] = array();
        foreach ($privileges as $privilege) {
            $data['privileges'][$privilege->privilegename] = $this->_i18n->get_string($privilege->privilegename, 'midgard.admin.asgard');
            if (!isset($data['objects'][$privilege->objectguid])) {
                $data['objects'][$privilege->objectguid] = array();
            }
            $data['objects'][$privilege->objectguid][$privilege->privilegename] = $privilege->value;
        }

        $data['view_title'] = sprintf($this->_l10n->get('folders of %s'), $this->_group->official);

        $this->_update_breadcrumb();
        return new midgard_admin_asgard_response($this, '_show_folders');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_folders($handler_id, array &$data)
    {
        $data['group'] = $this->_group;
        midcom_show_style('midcom-admin-user-group-folders');
    }
}
