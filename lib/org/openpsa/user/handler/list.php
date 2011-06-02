<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_list extends midcom_baseclasses_components_handler
implements org_openpsa_core_grid_provider_client
{
    /**
     * Handler method for listing users
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $auth = midcom::get('auth');
        if (!$auth->can_user_do('org.openpsa.user:access', null, 'org_openpsa_user_interface'))
        {
            $person = $auth->user->get_storage();
            midcom::get()->relocate('view/' . $person->guid . '/');
        }
        $data['grid'] = new org_openpsa_core_grid_widget('org_openpsa_user_grid', 'json');

        org_openpsa_core_ui::enable_dynatree();

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "create/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'midcom_db_person'),
            )
        );
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

    /**
     * Show list of the users
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('users-grid');
    }

    /**
     * Lists users in JSON format
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $_MIDCOM->skip_page_style = true;
    }

    /**
     * Show users in JSON format
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_json($handler_id, array &$data)
    {
        $data['provider'] = new org_openpsa_core_grid_provider($this);
        midcom_show_style('users-grid-json');
    }

    /**
     * Get querybuilder for JSON user list
     */
    public function get_qb($field = null, $direction = 'ASC')
    {
        $qb = midcom_db_person::new_collector('metadata.deleted', false);
        //@todo constraint username <> '' ?

        if (!is_null($field))
        {
            $field = str_replace('index_', '', $field);
            if ($field == 'username')
            {
                midcom_core_account::add_username_order($qb, $direction);
            }
            else
            {
                $qb->add_order($field, $direction);
            }
        }
        $qb->add_order('lastname');
        $qb->add_order('firstname');
        $qb->add_order('id');
        return $qb;
    }

    /**
     * Prepares user data for JSON display
     */
    public function get_row(midcom_core_dbaobject $user)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();
        $entry['id'] = $user->id;
        $lastname = trim($user->lastname);
        if (empty($lastname))
        {
            $lastname = $this->_l10n->get('person') . ' #' . $user->id;
        }
        $entry['lastname'] = "<a href='" . $prefix . 'view/' . $user->guid . "/'>" . $lastname . "</a>";
        $entry['index_lastname'] = $user->lastname;
        $entry['firstname'] = "<a href='" . $prefix . 'view/' . $user->guid . "/' >" . $user->firstname . "</a>";
        $entry['index_firstname'] = $user->firstname;
        $account = new midcom_core_account($user);
        $entry['username'] = $account->get_username();
        $entry['groups'] = array();

        //get groups
        $mc_member = midcom_db_member::new_collector('uid', $user->id);
        $mc_member->add_order('gid.official');
        $mc_member->add_order('gid.name');
        $gids = $mc_member->get_values('gid');

        foreach ($gids as $gid)
        {
            $group = org_openpsa_contacts_group_dba::get_cached($gid);
            $entry['groups'][] = '<a href="' . $prefix . 'group/' . $group->guid . '/">' . $group->get_label() . '</a>';
        }
        $entry['groups'] = implode(', ', $entry['groups']);

        return $entry;
    }
}
?>