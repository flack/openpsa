<?php
/**
 * @package midgard.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midgard.admin.user
 */
class midgard_admin_user_handler_group_permissions extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private midcom_db_group $_group;

    /**
     * Populate breadcrumb
     */
    private function _update_breadcrumb()
    {
        $this->add_breadcrumb($this->router->generate('user_list'), $this->_l10n->get($this->_component));

        $tmp = [];
        $grp = $this->_group;

        while ($grp) {
            $tmp[$grp->guid] = $grp->official;
            $grp = $grp->get_parent();
        }

        foreach (array_reverse($tmp) as $guid => $title) {
            $this->add_breadcrumb($this->router->generate('group_edit', ['guid' => $guid]), $title);
        }
        $this->add_breadcrumb('', $this->_l10n->get('folders'));
    }

    public function _handler_folders(string $handler_id, string $guid, array &$data)
    {
        $this->_group = new midcom_db_group($guid);
        midgard_admin_asgard_plugin::bind_to_object($this->_group, $handler_id, $data);

        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('assignee', '=', "group:{$this->_group->guid}");
        $data['objects'] = [];
        $data['privileges'] = [];
        foreach ($qb->execute() as $privilege) {
            $data['privileges'][$privilege->privilegename] = $this->_i18n->get_string($privilege->privilegename, 'midgard.admin.asgard');
            $data['objects'][$privilege->objectguid] ??= [];
            $data['objects'][$privilege->objectguid][$privilege->privilegename] = $privilege->value;
        }

        $data['view_title'] = sprintf($this->_l10n->get('folders of %s'), $this->_group->official);

        $this->_update_breadcrumb();

        return $this->get_response('midgard-admin-user-group-folders');
    }
}
