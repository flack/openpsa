<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: edit.php 25957 2010-05-03 15:21:57Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Edit a group
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_group_edit extends midcom_baseclasses_components_handler
{
    var $_group = null;

    /**
     * Simple constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->_component = 'midcom.admin.user';
    }

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_l10n = $_MIDCOM->i18n->get_l10n('midcom.admin.user');
        $this->_request_data['l10n'] = $this->_l10n;

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css',
            )
        );

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'),$this->_request_data);
    }

    function _update_breadcrumb()
    {
        // Populate breadcrumb
        $tmp = Array();

        $grp = $this->_group;
        while ($grp)
        {
            $tmp[] = Array
            (
                MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.user/group/edit/{$grp->guid}/",
                MIDCOM_NAV_NAME => $grp->official,
            );
            $grp = $grp->get_parent();
        }
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => '__mfa/asgard_midcom.admin.user/group',
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('groups', 'midcom.admin.user'),
        );
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.user/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('midcom.admin.user', 'midcom.admin.user'),
        );
        $tmp = array_reverse($tmp);

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     * Loads and prepares the schema database.
     */
    function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    /**
     * Internal helper, loads the controller for the current group. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $this->_load_schemadb();

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->_group->id);
        if (   $qb->count_unchecked() > $this->_config->get('list_users_max')
            && isset($this->_schemadb['default']->fields['persons']))
        {
            unset($this->_schemadb['default']->fields['persons']);
            $field_order_key = array_search('persons', $this->_schemadb['default']->field_order);
            if ($field_order_key !== false)
            {
                unset($this->_schemadb['default']->field_order[$field_order_key]);
            }
        }
        unset($qb);

        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_group, 'default');
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for group {$this->_group->id}.");
            // This will exit.
        }
    }


    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_group = new midcom_db_group($args[0]);
        if (   !$this->_group
            || !$this->_group->guid)
        {
            return false;
        }
        $this->_group->require_do('midgard:update');

        $data['asgard_toolbar'] = new midcom_helper_toolbar();

        $this->_load_controller();
        switch ($this->_controller->process_form())
        {
            case 'save':
                // Show confirmation for the group
                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('group %s saved'), $this->_group->name));
                $_MIDCOM->relocate("__mfa/asgard_midcom.admin.user/group/edit/{$this->_group->guid}/");
                // This will exit.

            case 'cancel':
                $_MIDCOM->relocate('__mfa/asgard_midcom.admin.user/');
                // This will exit.
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_group, $handler_id, $data);

        $ref = new midcom_helper_reflector($this->_group);
        $data['view_title'] = sprintf($_MIDCOM->i18n->get_string('edit %s', 'midcom.admin.user'), $ref->get_object_title($this->_group));
        $_MIDCOM->set_pagetitle($data['view_title']);

        $this->_update_breadcrumb();

        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/folders/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('folders', 'midcom.admin.user'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/folder.png',
            ),
            $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
        );
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/group/move/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('move group', 'midcom.admin.user'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
            ),
            $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
        );

        return true;
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_edit($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['group'] =& $this->_group;
        $data['controller'] =& $this->_controller;
        midcom_show_style('midcom-admin-user-group-edit');

        midgard_admin_asgard_plugin::asgard_footer();
    }
}
?>
