<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Style editor class for listing style elements
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_list extends midcom_baseclasses_components_handler
{
    private $_persons = array();

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/midcom.admin.user/jquery.midcom_admin_user.js');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data)
    {
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create user'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_config->get('allow_manage_accounts'),
            )
        );
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('groups'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
            )
        );
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create group'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
            )
        );
    }

    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        // Set the passwords elsewhere, but check the request first
        if (   isset($_POST['midcom_admin_user_action'])
            && $_POST['midcom_admin_user_action'] === 'passwords')
        {
            if (empty($_POST['midcom_admin_user']))
            {
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), $this->_l10n->get('empty selection'));
            }
            else
            {
                $get = implode('&midcom_admin_user[]=', $_POST['midcom_admin_user']);

                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/password/batch/?midcom_admin_user[]={$get}");
            }
        }
        // See what fields we want to use in the search
        $data['search_fields'] = $this->_config->get('search_fields');
        $data['list_fields'] = $this->_config->get('list_fields');

        if (   isset($_POST['midcom_admin_user'])
            && is_array($_POST['midcom_admin_user'])
            && $_POST['midcom_admin_user_action'])
        {
            $this->_batch_process();
        }

        $this->_list_persons();

        // Used in many checks, keys are IDs, values objects
        $data['groups'] = array();

        // Used in select
        $data['groups_for_select'] = array();
        if (midcom::get('auth')->admin)
        {
            $data['groups_for_select'][] = array
            (
                'title' => 'Midgard Administrators',
                'level' => '0',
                'id' => 0,
            );
        }

        if (count($this->_persons) > 0)
        {
            $this->list_groups_for_select(0, $data, 0);
        }

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $data['view_title']);
        $this->_prepare_toolbar($data);
        midcom::get('head')->set_pagetitle($data['view_title']);
    }

    private function _list_persons()
    {
        $qb = midcom_db_person::new_query_builder();
        $qb->add_order('lastname');
        $qb->add_order('firstname');

        if (isset($_REQUEST['midcom_admin_user_search']))
        {
            // Run the person-seeking QB
            $qb->begin_group('OR');
                foreach ($this->_request_data['search_fields'] as $field)
                {
                    if ($field == 'username')
                    {
                        midcom_core_account::add_username_constraint($qb, 'LIKE', "{$_REQUEST['midcom_admin_user_search']}%");
                    }
                    else
                    {
                        $qb->add_constraint($field, 'LIKE', "{$_REQUEST['midcom_admin_user_search']}%");
                    }
                }
            $qb->end_group('OR');

            $this->_persons = $qb->execute();
        }
        else
        {
            // List all persons if there are less than N of them
            if ($qb->count_unchecked() < $this->_config->get('list_without_search'))
            {
                $this->_persons = $qb->execute();
            }
        }
    }

    private function _batch_process()
    {
        foreach ($_POST['midcom_admin_user'] as $person_id)
        {
            if (is_numeric($person_id))
            {
                $person_id = (int) $person_id;
            }
            try
            {
                $person = new midcom_db_person($person_id);
            }
            catch (midcom_error $e)
            {
                continue;
            }

            switch ($_POST['midcom_admin_user_action'])
            {
                case 'removeaccount':
                    if (!$this->_config->get('allow_manage_accounts'))
                    {
                        break;
                    }
                    $person->parameter('midcom.admin.user', 'username', $person->username);
                    $account = new midcom_core_account($person);
                    if ($account->delete())
                    {
                        midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('user account revoked for %s'), $person->name));
                    }
                    break;

                case 'groupadd':
                    if (isset($_POST['midcom_admin_user_group']))
                    {
                        $member = new midcom_db_member();
                        $member->uid = $person->id;
                        $member->gid = (int) $_POST['midcom_admin_user_group'];
                        if ($member->create())
                        {
                            midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('user %s added to group'), $person->name));
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Internal helper for showing the groups recursively
     *
     * @param int $id
     * @param array &$data
     * @param int $level
     */
    private function list_groups_for_select($id, &$data, $level)
    {
        $mc = midcom_db_group::new_collector('owner', (int) $id);
        $mc->add_value_property('name');
        $mc->add_value_property('official');
        $mc->add_value_property('id');

        // Set the order
        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('official');
        $mc->add_order('name');

        // Get the results
        $mc->execute();
        $keys = $mc->list_keys();

        // Hide empty groups
        if ($mc->count() === 0)
        {
            return;
        }

        $data['parent_id'] = $id;

        foreach ($keys as $guid => $array)
        {
            $group['guid'] = $guid;
            $group['id'] = $mc->get_subkey($guid, 'id');
            $group['name'] = $mc->get_subkey($guid, 'name');

            if (($title = $mc->get_subkey($guid, 'official')))
            {
                $group['title'] = $title;
            }
            else
            {
                $group['title'] = $group['name'];
            }

            if (!$group['title'])
            {
                $group['title'] = "#{$group['id']}";
            }
            $group['level'] = $level;

            $data['groups_for_select'][] = $group;
            $level++;
            $this->list_groups_for_select($group['id'], $data, $level);
            $level--;
        }
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_list($handler_id, array &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['config'] =& $this->_config;

        $data['persons'] =& $this->_persons;
        midcom_show_style('midcom-admin-user-personlist-header');

        $data['even'] = false;
        foreach ($data['persons'] as $person)
        {
            $data['person'] = $person;
            midcom_show_style('midcom-admin-user-personlist-item');
            if (!$data['even'])
            {
                $data['even'] = true;
            }
            else
            {
                $data['even'] = false;
            }
        }

        midcom_show_style('midcom-admin-user-personlist-footer');
        midgard_admin_asgard_plugin::asgard_footer();
    }
}
?>