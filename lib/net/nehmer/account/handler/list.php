<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: List users
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_handler_list extends midcom_baseclasses_components_handler
{
    /**
     * This is a list of visible field names of the current account. It is computed after
     * account loading. They are listed in the order they appear in the schema.
     *
     * @var Array
     * @access private
     */
    private $_visible_fields = Array();

    /**
     * This is an array extracted out of the parameter net.nehmer.account/visible_field_list,
     * which holds the names of all fields the user has marked visible. This is loaded once
     * when determining visibilities.
     *
     * @var Array
     * @access private
     */
    private $_visible_fields_user_selection = Array();

    /**
     * The datamanager used to load the account-related information.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    private $_datamanager = null;

    /**
     * This handler loads the account, validates permissions and starts up the
     * datamanager.
     *
     * This handler is responsible for both admin and user modes, distinguishing it
     * by the handler id (admin_edit vs. edit). In admin mode, admin privileges are
     * required unconditionally, the id/guid of the record to-be-edited is expected
     * in $args[0].
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $_MIDCOM->load_library('org.openpsa.qbpager');

        if (!$this->_config->get('allow_list'))
        {
            throw new midcom_error_notfound("Listing users not enabled.");
        }

        $qb = new org_openpsa_qbpager('midcom_db_person', 'net_nehmer_account_list');
        $data['qb'] =& $qb;

        if ($handler_id == 'list_by_alpha')
        {
            if (   !is_string($args[0])
                || strlen($args[0]) != 1)
            {
                // FIXME: strlen() may not handle multibyte chars correctly
                throw new midcom_error_notfound("Invalid letter \"{$args[0]}\" for alphabetical search");
            }

            $qb->add_constraint('lastname', 'LIKE', "{$args[0]}%");
        }

        $list_orders = $this->_config->get('list_order');
        foreach ($list_orders as $property => $order)
        {
            $qb->add_order($property, $order);
        }
        $qb->results_per_page = $this->_config->get('list_entries');

        $data['users'] = $qb->execute();

        $this->_view_toolbar->hide_item('list/');

        $data['view_title'] = $this->_l10n->get('user list');
        $_MIDCOM->set_pagetitle($data['view_title']);
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $this->_prepare_datamanager();
        midcom_show_style('show-list-header');

        foreach ($data['users'] as $user)
        {
            $data['user'] =& $user;
            $this->_compute_visible_fields($user);
            $data['visible_fields'] = $this->_visible_fields[$user->guid];
            midcom_show_style('show-list-item');
        }

        midcom_show_style('show-list-footer');
    }

    /**
     * This handler loads the account, validates permissions and starts up the
     * datamanager.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list_random($handler_id, array $args, array &$data)
    {
        if (!$this->_config->get('allow_list'))
        {
            throw new midcom_error_notfound("Listing users not enabled.");
        }

        $random = (int) $args[0];
        if ($random > 20)
        {
            throw new midcom_error_notfound("Maximum list is 20 users.");
        }

        $data['handler'] = $handler_id;

        $data['users'] = array();
        $data['user_guids'] = array();

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('username', '<>', '');
        $qb->add_constraint('username', '<>', 'admin');
        $qb->add_constraint('firstname', 'NOT LIKE', 'DELETE %');
        $qb->add_constraint('metadata.score', '>', '3');
        $qb->add_constraint('attachment.name', '=', 'avatar');

        $data['total_users'] = $qb->count_unchecked();

        while ($random > 0)
        {
            $qb = midcom_db_person::new_query_builder();
            $qb->add_constraint('username', '<>', '');
            $qb->add_constraint('username', '<>', 'admin');
            $qb->add_constraint('firstname', 'NOT LIKE', 'DELETE %');
            if (   isset($data['user_guids'])
                && count($data['user_guids']) > 0)
            {
                $qb->add_constraint('guid', 'NOT IN', $data['user_guids']);
            }
            $qb->add_constraint('metadata.score', '>', '3');
            $qb->add_constraint('attachment.name', '=', 'avatar');
            $qb->set_limit(1);

            if (!empty($data['user_guids']))
            {
                $offset = mt_rand(0, $data['total_users'] - count($data['user_guids']));
            }
            else
            {
                $offset = mt_rand(0, $data['total_users']);
            }
            $qb->set_offset($offset);
            $users = $qb->execute_unchecked();

            if (   !is_null($users)
                && isset($users[0]))
            {
                $data['users'][] = $users[0];
                $data['user_guids'][] = $users[0]->guid;
                $random--;
            }
        }
        $data['view_title'] = $this->_l10n->get('user list');
        $_MIDCOM->set_pagetitle($data['view_title']);
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list_random($handler_id, array &$data)
    {
        $this->_prepare_datamanager();
        midcom_show_style('show-list-header');

        foreach ($data['users'] as $user)
        {
            $data['user'] =& $user;
            $this->_compute_visible_fields($user);
            $data['visible_fields'] = $this->_visible_fields[$user->guid];
            midcom_show_style('show-list-item');
        }

        midcom_show_style('show-list-footer');
    }

    /**
     * This function iterates over the field list in the schema and puts a list
     * of fields the user may see together.
     *
     * @see is_field_visisble()
     */
    private function _compute_visible_fields($user)
    {
        $this->_visible_fields_user_selection[$user->guid] = explode(',', $user->get_parameter('net.nehmer.account', 'visible_field_list'));
        $this->_visible_fields[$user->guid] = array();

        foreach ($this->_datamanager->schema->field_order as $name)
        {
            if ($this->_is_field_visible($name, $user->guid))
            {
                $this->_visible_fields[$user->guid][] = $name;
            }
        }
    }

    /**
     * This helper uses the 'visible_mode' customdata member to compute actual visibility
     * of a field. Possible settings:
     *
     * 'always' shows a field unconditionally, 'user' lets the user choose whether he
     * wants it shown, 'never' hides the field unconditionally and 'link' links it to the
     * visibility state of another field. In the last case you need to set the 'visible_link'
     * customdata to the name of another field to make this work.
     *
     * @return boolean Indicating Visibility
     */
    private function _is_field_visible($name, $user_guid)
    {
        if ($_MIDCOM->auth->admin)
        {
            return true;
        }

        switch ($this->_datamanager->schema->fields[$name]['customdata']['visible_mode'])
        {
            case 'always':
                return true;

            case 'never':
            case 'skip':
                return false;

            case 'link':
                $target = $this->_datamanager->schema->fields[$name]['customdata']['visible_link'];
                if ($target == $name)
                {
                    throw new midcom_error("Tried to link the visibility of {$name} to itself.");
                }
                return $this->_is_field_visible($target, $user_guid);

            case 'user':
                return in_array($name, $this->_visible_fields_user_selection[$user_guid]);
        }
        throw new midcom_error("Unknown Visibility declaration in {$name}: {$this->_datamanager->schema->fields[$name]['customdata']['visible_mode']}.");
    }

    /**
     * Internal helper function, prepares a datamanager.
     */
    private function _prepare_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        $this->_datamanager->set_schema('account');
        foreach ($this->_datamanager->schema->field_order as $name)
        {
            if (! array_key_exists('visible_mode', $this->_datamanager->schema->fields[$name]['customdata']))
            {
                $this->_datamanager->schema->fields[$name]['customdata']['visible_mode'] = 'user';
            }
        }
    }
}
?>