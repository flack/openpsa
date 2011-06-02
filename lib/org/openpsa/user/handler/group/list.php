<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Group listing class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_group_list extends midcom_baseclasses_components_handler
{
    /**
     * Handle the group listing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:access', null, 'org_openpsa_user_interface');
        $data['groups'] = $this->_list_groups(0);
        $data['handler'] = $this;

        org_openpsa_core_ui::enable_dynatree();

        $this->add_breadcrumb("", $this->_l10n->get('groups'));
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create group'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'midcom_db_group'),
            )
        );
    }

    public function render_groups(array $groups)
    {
        if (sizeof($groups) == 0)
        {
            return;
        }
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        echo "<ul>\n";
        foreach ($groups as $group)
        {
            echo '<li id="g_' . $group['guid'] . '" data="url: \'' . $prefix . 'group/' . $group['guid'] . '/\'"><span>' . $group['title'] . "</span>\n";
            if (!empty($group['children']))
            {
                $this->render_groups($group['children']);
            }
            echo "</li>\n";
        }
        echo "</ul>\n";
    }

    /**
     * Show the group listing
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('group-list');
    }

    /**
     * Internal helper for showing the groups recursively
     *
     * @param int $id The parent group ID
     */
    private function _list_groups($id)
    {
        $data = array();

        $mc = midcom_db_group::new_collector('owner', (int) $id);
        $mc->add_value_property('id');
        $mc->add_value_property('name');
        $mc->add_value_property('official');

        $mc->add_order('official');
        $mc->add_order('name');

        $mc->execute();
        $keys = $mc->list_keys();

        if (sizeof($keys) === 0)
        {
            return;
        }

        foreach ($keys as $guid => $array)
        {
            $entry = array('guid' => $guid);

            if (($title = $mc->get_subkey($guid, 'official')))
            {
                $entry['title'] = $title;
            }
            else
            {
                $entry['title'] = $mc->get_subkey($guid, 'name');
            }

            if (!$entry['title'])
            {
                $entry['title'] = $this->_l10n->get('unknown');
            }
            $entry['children'] = $this->_list_groups($mc->get_subkey($guid, 'id'));
            $data[] = $entry;
        }
        return $data;
    }

    /**
     * Internal helper to check if the requested group belongs to the haystack
     *
     * @static
     * @param int $id
     * @param int $owner
     */
    public function belongs_to($id, $owner)
    {
        do
        {
            if ($id === $owner)
            {
                return true;
            }

            $mc = midcom_db_group::new_collector('id', $id);
            $mc->set_limit(1);
            $keys = $mc->get_values('owner');

            // Get the first array key
            foreach ($keys as $key)
            {
                if ($key === 0)
                {
                    return false;
                }

                $id = $key;
            }
        }
        while ($mc->count() > 0);

        return false;
    }
}
?>