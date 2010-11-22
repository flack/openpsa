<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: edit.php 11541 2007-08-10 10:02:57Z adrenalin $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: List users by karma
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
    var $_visible_fields = Array();

    /**
     * This is an array extracted out of the parameter net.nehmer.account/visible_field_list,
     * which holds the names of all fields the user has marked visible. This is loaded once
     * when determining visibilities.
     *
     * @var Array
     * @access private
     */
    var $_visible_fields_user_selection = Array();

    /**
     * The datamanager used to load the account-related information.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

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
     * @return boolean Indicating success.
     */
    function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->load_library('org.openpsa.qbpager');

        if (!$this->_config->get('allow_list'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Listing users not enabled.");
        }

        $data['list_categories'] = $this->_config->get('list_categories');

        $qb = new org_openpsa_qbpager('midcom_db_person', 'net_nehmer_account_list');
        $data['qb'] =& $qb;
        
        if ($handler_id == 'list_by_alpha')
        {
            if (   !is_string($args[0])
                || strlen($args[0]) != 1)
            {
                // FIXME: strlen() may not handle multibyte chars correctly
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Invalid letter \"{$args[0]}\" for alphabetical search");
                // This will exit
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

        return true;
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_list($handler_id, &$data)
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
     * This handler is responsible for both admin and user modes, distinguishing it
     * by the handler id (admin_edit vs. edit). In admin mode, admin privileges are
     * required unconditionally, the id/guid of the record to-be-edited is expected
     * in $args[0].
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_list_by_category($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_list'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Listing users not enabled.");
        }

        $data['handler'] = $handler_id;
        $data['category'] = $args[0];
        $data['list_categories'] = $this->_config->get('list_categories');

        if (!in_array($data['category'], $data['list_categories']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "List {$data['category']} not found");
            // This will exit
        }

        $person_ids = array();
        $data['karma_map'] = array();
        $mc = net_nehmer_account_karma_dba::new_collector('module', $data['category']);
        $mc->add_value_property('karma');
        $mc->add_value_property('person');
        $mc->add_order('karma', 'DESC');
        $mc->add_order('person.metadata.score', 'DESC');
        $mc->set_limit($this->_config->get('list_categories_number'));
        $mc->execute();
        $keys = $mc->list_keys();
        foreach ($keys as $key => $values)
        {
            $person_id = $mc->get_subkey($key, 'person');
            $person_ids[] = $person_id;
            $data['karma_map'][$person_id] = $mc->get_subkey($key, 'karma');
        }
        unset($mc);

        $data['users'] = array();
        
        foreach ($person_ids as $person_id)
        {
            $user = new midcom_db_person();
            $user->get_by_id($person_id);
            if ($user->guid)
            {
                $data['users'][] = $user;
            }
        }
        unset($person_ids);

        // At this point our $data['users'] array is ordered by the desired karma-category

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "list/category/{$args[0]}/",
            MIDCOM_NAV_NAME => $this->_l10n->get("by {$args[0]}"),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $data['view_title'] = $this->_l10n->get('user list') . ': ' . $this->_l10n->get("by {$args[0]}");
        $_MIDCOM->set_pagetitle($data['view_title']);

        return true;
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_list_by_category($handler_id, &$data)
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
     * @return boolean Indicating success.
     */
    function _handler_list_random($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_list'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Listing users not enabled.");
        }

        $random = (int) $args[0];
        if ($random > 20)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Maximum list is 20 users.");
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

        return true;
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_list_random($handler_id, &$data)
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
    function _compute_visible_fields($user)
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
    function _is_field_visible($name, $user_guid)
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
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "Tried to link the visibility of {$name} to itself.");
                    // this will exit()
                }
                return $this->_is_field_visible($target, $user_guid);

            case 'user':
                return in_array($name, $this->_visible_fields_user_selection[$user_guid]);

        }
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
            "Unknown Visibility declaration in {$name}: {$this->_datamanager->schema->fields[$name]['customdata']['visible_mode']}.");
        // This will exit()
    }

    /**
     * Internal helper function, prepares a datamanager.
     */
    function _prepare_datamanager()
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