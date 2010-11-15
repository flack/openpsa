<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: view.php 25323 2010-03-18 15:54:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** We need the PEAR Date class. See http://pear.php.net/package/Date/docs/latest/
 * @ignore
 */
require_once('Date.php');

/**
 * Account Management handler class: View Account
 *
 * This class implements the regular account view modes, both a full fledged mode, and a
 * quick view which provides only the information which is always visible.
 *
 * For the URLs being handled here, see the main class' documentation.
 *
 * Summary of available request keys:
 *
 * - datamanager: A reference to the DM2 Instance.
 * - visible_fields: A plain list of all visible field names.
 * - visible_data: The rendered data associated with the visible fields.
 * - schema: A reference to the schema in use.
 * - account: A reference to the account in use.
 * - view_self: A boolean indicating whether we display our own account, or not.
 * - profile_url: Only applicable in the quick-view mode, it contains the URL
 *   to the full profile record.
 * - edit_url: Only applicable if in view-self mode, it contains the URL to the
 *   edit record screen.
 *
 * This class listens to the handlers IDs 'self', 'self_quick', 'other', 'other_direct' and
 * 'other_quick', invoking the appropriate view code. The 'other_direct' ID will only be
 * used when 'allow_by_username_only' => true. Unknown handler IDs will be rejected
 * with generate_error. It expects the following URL structures, relative to ANCHOR_PREFIX:
 *
 *
 * - 'self': /
 * - 'self_quick': /quick/
 * - 'other': /view/$guid/
 * - 'other_direct': /$guid/
 * - 'other_quick': /view/quick/$guid/
 *
 * @package net.nehmer.account
 */

class net_nehmer_account_handler_view extends midcom_baseclasses_components_handler
{
    function __construct()
    {
        parent::__construct();
    }

    public function _on_initialize()
    {
        if ($_openid_root_url = $this->_config->get('openidprovider_link'))
        {
            $_MIDCOM->header('X-XRDS-Location: '.$_openid_root_url.'/xrds/');
            $_MIDCOM->add_link_head(array('rel' => 'openid2.provider', 'href' => $_openid_root_url));
            $_MIDCOM->add_link_head(array('rel' => 'openid.server',    'href' => $_openid_root_url));
        }
    }

    /**
     * The user account we are managing. This is taken from the currently active user
     * if no account is specified in the URL, or from the GUID passed to the system.
     *
     * @var midcom_db_person
     * @access private
     */
    var $_account = null;

    /**
     * The Avatar image, if set.
     *
     * @var midcom_db_attachment
     * @access private
     */
    var $_avatar = null;

    /**
     * The Avatar thumbnail image, if set.
     *
     * @var midcom_db_attachment
     * @access private
     */
    var $_avatar_thumbnail = null;

    /**
     * The midcom_core_user object matching the loaded account. This is useful for
     * isonline checkes and the like.
     *
     * @var midcom_core_user
     * @access private
     */
    var $_user = null;

    /**
     * This flag is set to true if we are viewing the account of the currently registered
     * user. This influences the access control of the account display.
     *
     * @var boolean
     * @access private
     */
    var $_view_self = false;

    /**
     * This is true if we are in the quick-view mode, which displays only the administratively
     * assigned fields, along with a link to the full profile view. This makes live a bit
     * easier when including profiles in other components.
     *
     * @var boolean
     * @access private
     */
    var $_view_quick = false;

    /**
     * The datamanager used to load the account-related information.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

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

    var $person_toolbar = false;
    var $person_toolbar_html = '';

    /**
     * Can-Handle check against account name.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean True if the request can be handled, false otherwise.
     */
    function _can_handle_view($handler_id, $args, &$data)
    {
        if ($handler_id == 'self' || $handler_id == 'self_quick' || $handler_id == 'root')
        {
            return true;
        }

        if (   isset($args[0])
            && $args[0] != ''
            && $this->_get_account($args[0]))
        {
            return true;
        }

        return false;
    }

    /**
     * The view handler will load the account and set the appropriate flags for startup preparation
     * according to the handler name.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_view($handler_id, $args, &$data)
    {
        switch ($handler_id)
        {
            case 'root':
                // Show the list
                if ($this->_config->get('allow_list'))
                {
                    $_MIDCOM->relocate('list/');
                }
                
                // Show authenticated user the profile page
                if ($_MIDCOM->auth->user)
                {
                    $_MIDCOM->relocate('me/');
                }
                
                // Go to registration if allowed
                if ($this->_config->get('allow_register'))
                {
                    $_MIDCOM->relocate('register/');
                }
                
                // As the last resort, show login page
                $_MIDCOM->auth->require_valid_user();
                
                break;

            case 'self':
                if (   !$_MIDCOM->auth->user
                    && $this->_config->get('allow_register'))
                {
                    $_MIDCOM->relocate('register/');
                }
                $_MIDCOM->auth->require_valid_user();
                $this->_account = $_MIDCOM->auth->user->get_storage();
                net_nehmer_account_viewer::verify_person_privileges($this->_account);
                $this->_view_self = true;
                $this->_view_quick = false;
                break;

            case 'self_quick':
                $_MIDCOM->auth->require_valid_user();
                $this->_account = $_MIDCOM->auth->user->get_storage();
                net_nehmer_account_viewer::verify_person_privileges($this->_account);
                $this->_view_self = true;
                $this->_view_quick = true;
                break;

            case 'other':
                if (!$this->_get_account($args[0]))
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The account '{$args[0]}' could not be loaded, reason: " . midcom_application::get_error_string());
                }
                break;

            case 'other_direct':
                if (!$this->_get_account($args[0]))
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The account '{$args[0]}' could not be loaded, reason: " . midcom_application::get_error_string());
                }
                $this->_view_self = false;
                $this->_view_quick = false;
                break;

            case 'other_quick':
                if (!$this->_get_account($args[0]))
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The account '{$args[0]}' could not be loaded, reason: " . midcom_application::get_error_string());
                }
                $this->_view_self = false;
                $this->_view_quick = true;
                break;

            default:
                $this->errstr = "Unknown handler ID {$handler_id} encountered.";
                $this->errcode = MIDCOM_ERRCRIT;
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Unknown handler ID {$handler_id} encountered.");
        }

        if (   !$this->_account
            || !$this->_account->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, $this->_l10n->get('the account was not found.'));
            // this will exit
        }
        $this->_user = $_MIDCOM->auth->get_user($this->_account);
        $this->_avatar = $this->_account->get_attachment('avatar');
        $this->_avatar_thumbnail = $this->_account->get_attachment('avatar_thumbnail');

        $this->_prepare_datamanager();
        $this->_compute_visible_fields();
        $this->_prepare_request_data();
        $this->_populate_toolbar();
        $this->_populate_person_toolbar();
        $_MIDCOM->bind_view_to_object($this->_account, $this->_datamanager->schema->name);
        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);
        $_MIDCOM->set_pagetitle($this->_user->name);

        if (   $handler_id == 'other'
            || $handler_id == 'other_quick')
        {
            $tmp[] = Array
            (
                MIDCOM_NAV_URL => '',
                MIDCOM_NAV_NAME => $this->_user->name,
            );
            $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
        }

        return true;
    }

    function _populate_person_toolbar()
    {
        if (!$_MIDCOM->auth->user)
        {
            return;
        }


        if (   $_MIDCOM->auth->user
            && $this->_account->guid == $_MIDCOM->auth->user->guid)
        {
            if (   $GLOBALS['midcom_config']['toolbars_enable_centralized']
                && $_MIDCOM->auth->can_user_do('midcom:centralized_toolbar', null, 'midcom_services_toolbars'))
            {
                return;
            }
            $this->person_toolbar = new midcom_helper_toolbar();

            // Own profile page

            $this->person_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "edit/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );

            if ($this->_config->get('allow_publish'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "publish/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('publish account details'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                    )
                );
            }

            if ($this->_config->get('allow_invite'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "invite/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import contacts'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_mail-send.png',
                    )
                );
            }

            if ($this->_config->get('allow_socialweb'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "socialweb/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('social web settings'),
                        MIDCOM_TOOLBAR_ICON => 'net.nehmer.account/data-import.png',
                    )
                );
            }

            if ($this->_config->get('allow_change_password'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "password/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('change password'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    )
                );
            }

            if ($this->_config->get('allow_change_username'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "username/",
                        MIDCOM_TOOLBAR_LABEL => $this->_config->get('username_is_email') ?
                            $this->_l10n->get('change email') : $this->_l10n->get('change username'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    )
                );
            }

            if ($this->_config->get('allow_cancel_membership'))
            {
                $this->person_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "cancel_membership/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('cancel membership'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    )
                );
            }
        }
        else
        {
            // Someones profile page
            if (   $GLOBALS['midcom_config']['toolbars_enable_centralized']
                && $_MIDCOM->auth->can_user_do('midcom:centralized_toolbar', null, 'midcom_services_toolbars'))
            {
                $buddy_toolbar =& $this->_view_toolbar;
            }
            else
            {
                $this->person_toolbar = new midcom_helper_toolbar();
                $buddy_toolbar =& $this->person_toolbar;
            }

            if ($this->_config->get('net_nehmer_buddylist_integration'))
            {

                $buddylist_path = $this->_config->get('net_nehmer_buddylist_integration');
                $current_prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $view_url = $this->_get_view_url();

                $_MIDCOM->componentloader->load_graceful('net.nehmer.buddylist');

                $qb = net_nehmer_buddylist_entry::new_query_builder();
                $user = $_MIDCOM->auth->user->get_storage();
                $qb->add_constraint('account', '=', $user->guid);
                $qb->add_constraint('buddy', '=', $this->_account->guid);
                $qb->add_constraint('blacklisted', '=', false);
                $buddies = $qb->execute();

                if (count($buddies) > 0)
                {
                    // We're buddies, show remove button
                    $buddy_toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$buddylist_path}delete",
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('remove buddy'),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:delete', $buddies[0]),
                            MIDCOM_TOOLBAR_POST => true,
                            MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                            (
                                'net_nehmer_buddylist_delete' => '1',
                                "account_{$this->_account->guid}" => '1',
                                'relocate_to' => $view_url,
                            )
                        )
                    );
                }
                else
                {
                    // We're not buddies, show add button
                    $buddy_toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$buddylist_path}request/{$this->_account->guid}?relocate_to={$view_url}",
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add buddy'),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:create', $user),
                        )
                    );
                }
            }
        }

        $this->_render_person_toolbar();
    }

    function _render_person_toolbar()
    {
        if (! $this->person_toolbar)
        {
            return false;
        }

        $output = '<ul';
        if (! is_null($this->person_toolbar->class_style))
        {
            $output .= " class='{$this->person_toolbar->class_style}'";
        }
        if (! is_null($this->person_toolbar->id_style))
        {
            $output .= " id='{$this->person_toolbar->id_style}'";
        }
        $output .= ">\n";

        foreach ($this->person_toolbar->items as $item)
        {
            $label = $item[MIDCOM_TOOLBAR_LABEL];

            if (   $item[MIDCOM_TOOLBAR_HIDDEN]
                || !$item[MIDCOM_TOOLBAR_ENABLED])
            {
                continue;
            }

            $output .= "  <li>";

            if ($item[MIDCOM_TOOLBAR_POST])
            {
                $output .= "<form method=\"post\" action=\"{$item[MIDCOM_TOOLBAR_URL]}\">";
                $output .= "<button type=\"submit\" name=\"midcom_helper_toolbar_submit\"";

                if ( count($item[MIDCOM_TOOLBAR_OPTIONS]) > 0 )
                {
                    foreach ($item[MIDCOM_TOOLBAR_OPTIONS] as $key => $val)
                    {
                        $output .= " $key=\"$val\" ";
                    }
                }
                if ($item[MIDCOM_TOOLBAR_ACCESSKEY])
                {
                    $output .= " class=\"accesskey\" accesskey=\"{$item[MIDCOM_TOOLBAR_ACCESSKEY]}\" ";
                }
                $output .= " title=\"${label}\">";

                $url = MIDCOM_STATIC_URL . "/{$item[MIDCOM_TOOLBAR_ICON]}";
                $output .= "<img src='{$url}' alt='{$label}' />";

                $output .= "</button>";

                if ($item[MIDCOM_TOOLBAR_POST_HIDDENARGS])
                {
                    foreach ($item[MIDCOM_TOOLBAR_POST_HIDDENARGS] as $key => $value)
                    {
                        $key = htmlspecialchars($key);
                        $value = htmlspecialchars($value);
                        $output .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>";
                    }
                }
                $output .= "</form>\n";
            }
            else
            {
                $output .= "<a href='{$item[MIDCOM_TOOLBAR_URL]}'";
                $output .= " title='{$label}'";

                if ( count($item[MIDCOM_TOOLBAR_OPTIONS]) > 0 )
                {
                    foreach ($item[MIDCOM_TOOLBAR_OPTIONS] as $key => $val)
                    {
                        $output .= " $key=\"$val\" ";
                    }
                }
                if (! is_null($item[MIDCOM_TOOLBAR_ACCESSKEY]))
                {
                    $output .= " class=\"accesskey \" accesskey='{$item[MIDCOM_TOOLBAR_ACCESSKEY]}' ";
                }
                $output .= ">";

                $url = MIDCOM_STATIC_URL . "/{$item[MIDCOM_TOOLBAR_ICON]}";
                $output .= "<img src='{$url}' alt='{$label}' />";

                $output .= "</a>";
            }

            $output .= "</li>\n";
        }

        $output .= '</ul>';

        $this->person_toolbar_html = $output;
    }

    function _get_view_url()
    {
        $url = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'view/';

        if (!$this->_config->get('allow_view_by_username'))
        {
            return "{$url}{$this->_account->guid}";
        }

        return "{$url}{$this->_account->username}";
    }

    /**
     * Populates $this->_account by guid or by username
     *
     * @param string $id GUID or username
     * @return boolean false on critical failure, true otherwise.
     */
    function _get_account($id)
    {
        if (mgd_is_guid($id))
        {
            $this->_account = midcom_db_person::get_cached($id);
            return true;
        }
        if (!$this->_config->get('allow_view_by_username'))
        {
            // Silently ignore
            return true;
        }

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('username', '=', $id);
        $results = $qb->execute();
        unset($qb);

        if ($results === false)
        {
            // QB fatal error
            return false;
        }
        if (empty($results))
        {
            // no accounts is not a fatal error
            return true;
        }
        if (count($results) > 1)
        {
            // More than one result, what to do ??
            return false;
        }
        $this->_account = $results[0];
        unset($results);
        return true;
    }

    /**
     * This function prepares the requestdata with all computed values.
     * A special case is the visible_data array, which maps field names
     * to prepared values, which can be used in display directly. The
     * information returned is already HTML escaped.
     *
     * @access private
     */
    function _prepare_request_data()
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $att_prefix = $_MIDCOM->get_page_prefix();

        $visible_data = Array();
        foreach ($this->_visible_fields as $name)
        {
            $visible_data[$name] = $this->_render_field($name);
        }

        $revised = new Date($this->_account->metadata->revised);
        $published = new Date($this->_account->metadata->published);

        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['visible_fields'] =& $this->_visible_fields;
        $this->_request_data['visible_data'] = $visible_data;
        $this->_request_data['schema'] =& $this->_datamanager->schema;
        $this->_request_data['account'] =& $this->_account;
        $this->_request_data['avatar'] =& $this->_avatar;
        $this->_request_data['avatar_thumbnail'] =& $this->_avatar_thumbnail;
        $this->_request_data['user'] =& $this->_user;
        $this->_request_data['revised'] = $revised;
        $this->_request_data['published'] = $published;
        $this->_request_data['view_self'] = $this->_view_self;
        $this->_request_data['person_toolbar'] =& $this->person_toolbar;
        $this->_request_data['person_toolbar_html'] =& $this->person_toolbar_html;

        if ($this->_view_quick)
        {
            if ($this->_view_self)
            {
                $this->_request_data['profile_url'] = $prefix;
            }
            else
            {
                $arg = $this->_account->guid;
                if (   $this->_account->username
                    && strpos($this->_account->username, '/') === false)
                {
                    $arg = rawurlencode($this->_account->username);
                }
                $this->_request_data['profile_url'] = "{$prefix}view/{$arg}/";
            }
        }
        else
        {
            $this->_request_data['profile_url'] = null;
        }

        if ($this->_view_self)
        {
            $this->_request_data['edit_url'] = "{$prefix}edit/";
        }
        else if ($_MIDCOM->auth->admin)
        {
            $this->_request_data['edit_url'] = "{$prefix}admin/edit/{$this->_account->guid}/";
        }
        else
        {
            $this->_request_data['edit_url'] = null;
        }

        if ($this->_avatar)
        {
            $this->_request_data['avatar_url'] = "{$att_prefix}midcom-serveattachmentguid-{$this->_avatar->guid}/avatar";
        }
        else
        {
            $this->_request_data['avatar_url'] = null;
        }
        if ($this->_avatar_thumbnail)
        {
            $this->_request_data['avatar_thumbnail_url'] = "{$att_prefix}midcom-serveattachmentguid-{$this->_avatar_thumbnail->guid}/avatar_thumbnail";
        }
        else
        {
            $this->_request_data['avatar_thumbnail_url'] = null;
        }
    }

    /**
     * A little helper which extracts the view of the given type
     */
    function _render_field($name)
    {
        return $this->_datamanager->types[$name]->convert_to_html();
    }

    /**
     * Internal helper function, prepares a datamanager based on the current account.
     */
    function _prepare_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        $this->_datamanager->autoset_storage($this->_account);
        foreach ($this->_datamanager->schema->field_order as $name)
        {
            if (! array_key_exists('visible_mode', $this->_datamanager->schema->fields[$name]['customdata']))
            {
                $this->_datamanager->schema->fields[$name]['customdata']['visible_mode'] = 'user';
            }
        }
    }

    /**
     * This function iterates over the field list in the schema and puts a list
     * of fields the user may see together.
     *
     * @see is_field_visisble()
     */
    function _compute_visible_fields()
    {
        if ($this->_view_quick)
        {
            // This will effectively hide all user-defined fields.
            $this->_visible_fields_user_selection = Array();
        }
        else
        {
            $this->_visible_fields_user_selection = explode(',', $this->_account->get_parameter('net.nehmer.account', 'visible_field_list'));
        }
        $this->_visible_fields = Array();

        foreach ($this->_datamanager->schema->field_order as $name)
        {
            if ($this->_is_field_visible($name))
            {
                $this->_visible_fields[] = $name;
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
    function _is_field_visible($name)
    {
        if (   $_MIDCOM->auth->admin
            || (   $this->_view_self
                && ! $this->_view_quick))
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
                return $this->_is_field_visible($target);

            case 'user':
                return in_array($name, $this->_visible_fields_user_selection);

        }
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
            "Unknown Visibility declaration in {$name}: {$this->_datamanager->schema->fields[$name]['customdata']['visible_mode']}.");
        // This will exit()
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_view($handler_id, &$data)
    {
        if ($this->_view_quick)
        {
            midcom_show_style('show-quick-account');
        }
        else
        {
            midcom_show_style('show-full-account');
        }
    }

    function _populate_toolbar()
    {
        if ($_MIDCOM->auth->user == null)
        {
            return;
        }

        if ($this->_account->guid == $_MIDCOM->auth->user->guid)
        {
            // Own profile page
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "edit/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );

            if ($this->_config->get('allow_publish'))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "publish/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('publish account details'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                    )
                );
            }

            if ($this->_config->get('allow_socialweb'))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "socialweb/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('social web settings'),
                        MIDCOM_TOOLBAR_ICON => 'net.nehmer.account/data-import.png',
                    )
                );
            }

            if ($this->_config->get('allow_change_password'))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "password/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('change password'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    )
                );
            }

            if ($this->_config->get('allow_change_username'))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "username/",
                        MIDCOM_TOOLBAR_LABEL => $this->_config->get('username_is_email') ?
                            $this->_l10n->get('change email') : $this->_l10n->get('change username'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    )
                );
            }

            if ($this->_config->get('allow_cancel_membership'))
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "cancel_membership/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('cancel membership'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    )
                );
            }
        }
        else
        {
            // Someone elses profile

            if ($this->_config->get('net_nehmer_buddylist_integration'))
            {
                $buddylist_path = $this->_config->get('net_nehmer_buddylist_integration');
                $current_prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $view_url = $this->_get_view_url();

                $_MIDCOM->componentloader->load_graceful('net.nehmer.buddylist');

                $qb = net_nehmer_buddylist_entry::new_query_builder();
                $user = $_MIDCOM->auth->user->get_storage();
                $qb->add_constraint('account', '=', $user->guid);
                $qb->add_constraint('buddy', '=', $this->_account->guid);
                $qb->add_constraint('blacklisted', '=', false);
                $buddies = $qb->execute();

                if (count($buddies) > 0)
                {
                    // We're buddies, show remove button
                    $this->_view_toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$buddylist_path}delete",
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('remove buddy'),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:delete', $buddies[0]),
                            MIDCOM_TOOLBAR_POST => true,
                            MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                            (
                                'net_nehmer_buddylist_delete' => '1',
                                "account_{$this->_account->guid}" => '1',
                                'relocate_to' => $view_url,
                            )
                        )
                    );
                }
                else
                {
                    // We're not buddies, show add button
                    $this->_view_toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$buddylist_path}request/{$this->_account->guid}?relocate_to={$view_url}",
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add buddy'),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:create', $user),
                        )
                    );
                }
            }
        }

        if ($_MIDCOM->auth->admin)
        {
            // Admin viewing another profile
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "admin/edit/{$this->_account->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

    }

}

?>