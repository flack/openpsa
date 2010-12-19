<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: Account Publishing class
 *
 * This class implements the account publishing view, which lets the user choose from
 * all user-viewable tagged fields.
 *
 * Summary of available request keys:
 *
 * - datamanager: A reference to the DM2 Instance.
 * - fields: A list of the fields to show, indexed by their name, see below for contents.
 * - current_field: The current field to show during the display loop
 * - schema: A reference to the schema in use.
 * - account: A reference to the account in use.
 * - profile_url: Contains the URL to the full profile record.
 * - edit_url: Contains the URL to the edit record screen.
 * - form_submit_name: This is the name you should give your submit button, so that the processing
 *   code works correctly.
 * - processing_msg: This is the processing message originating from the last request. May be
 *   an empty string. It is localized.
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_handler_publish extends midcom_baseclasses_components_handler
{
    /**
     * The user account we are managing. This is taken from the currently active user
     * if no account is specified in the URL, or from the GUID passed to the system.
     *
     * @var midcom_db_person
     * @access private
     */
    private $_account = null;

    /**
     * The Avatar image, if set.
     *
     * @var midcom_db_attachment
     * @access private
     */
    private $_avatar = null;

    /**
     * The Avatar thumbnail image, if set.
     *
     * @var midcom_db_attachment
     * @access private
     */
    private $_avatar_thumbnail = null;

    /**
     * The datamanager used to load the account-related information.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    private $_datamanager = null;

    /**
     * This is a list of visible field names of the current account. It is computed after
     * account loading. They are listed in the order they appear in the schema.
     *
     * @var Array
     * @access private
     */
    private $_fields = Array();

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
     * Helper variable, containing a localized message to be shown to the user indicating the form's
     * processing state.
     *
     * @var string
     * @access private
     */
    private $_processing_msg = '';

    /**
     * The raw, untranslated processing message. Use this if you want to have your own translation
     * beside the defaults given by the component. The variable contains the l10n string IDs.
     *
     * @var string
     * @access private
     */
    private $_processing_msg_raw = '';

    /**
     * The handler provides publishing support. After creating and preparing all members,
     * it will first process the form. Afterwards, it provides the means to display the
     * publishing form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_publish($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_account = $_MIDCOM->auth->user->get_storage();
        net_nehmer_account_viewer::verify_person_privileges($this->_account);
        $this->_avatar = $this->_account->get_attachment('avatar');
        $this->_avatar_thumbnail = $this->_account->get_attachment('avatar_thumbnail');
        $_MIDCOM->auth->require_do('midgard:update', $this->_account);
        $_MIDCOM->auth->require_do('midgard:parameters', $this->_account);
        $_MIDCOM->auth->require_do('midgard:attachments', $this->_account);

        $this->_prepare_datamanager();
        $this->_process_form();
        // This might relocate to the ok screen.
        $this->_compute_fields();
        $this->_prepare_request_data();

        $_MIDCOM->bind_view_to_object($this->_account, $this->_datamanager->schema->name);

        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);

        $this->add_breadcrumb('publish/', $this->_l10n->get('publish account details'));
        $this->_view_toolbar->hide_item('publish/');

        $_MIDCOM->set_pagetitle($this->_l10n->get('publish account details'));

        return true;
    }

    /**
     * This function processes the form, computing the visible field list for the current
     * selection. If no form submission can be found, the method exits unconditionally.
     *
     * The online state privilege is set according to the field's presence in the request data.
     * Default is not to show online state when publishing, in case the field is missing.
     */
    private function _process_form()
    {
        if (array_key_exists('net_nehmer_account_publish_delete_avatar', $_REQUEST))
        {
            // We ignore errors at this point. Access control has been verified, if
            // we delete non existent attachments, we're fine as well.
            $this->_account->delete_attachment('avatar');
            $this->_account->delete_attachment('avatar_thumbnail');
            $this->_avatar = null;
            $this->_avatar_thumbnail = null;
            return;
        }

        if (! array_key_exists('net_nehmer_account_publish', $_REQUEST))
        {
            return;
        }

        $this->_process_image_upload();

        $published_fields = Array();
        foreach ($this->_datamanager->schema->fields as $name => $field)
        {
            if (   array_key_exists($name, $_REQUEST)
                && $_REQUEST[$name] == 'on')
            {
                $published_fields[] = $name;
            }
        }

        // Update online state field.
        if (array_key_exists('onlinestate', $_REQUEST))
        {
            $this->_account->set_privilege('midcom:isonline', 'USERS', MIDCOM_PRIVILEGE_ALLOW);
        }
        else
        {
            $this->_account->set_privilege('midcom:isonline', 'USERS', MIDCOM_PRIVILEGE_DENY);
        }


        $this->_account->set_parameter('net.nehmer.account', 'visible_field_list', implode(',', $published_fields));
        $this->_account->delete_parameter('net.nehmer.account', 'auto_published');

        $_MIDCOM->uimessages->add($this->_l10n->get('publish account details'), $this->_l10n->get('publishing successful.'));
        $_MIDCOM->relocate('');
    }

    /**
     * Helper function, called during form processing. Takes any image upload from the avatar
     * field and processes it accordingly.
     */
    private function _process_image_upload()
    {
        if (   array_key_exists('avatar', $_FILES)
            && $_FILES['avatar']['error'] == 0)
        {
            $file = $_FILES['avatar'];

            $filter = new midcom_helper_imagefilter();

            if (   ! $filter->set_file($file['tmp_name'])
                || ! $filter->rescale($this->_config->get('avatar_x'), $this->_config->get('avatar_y')))
            {
                throw new midcom_error('Failed to scale the avatar attachment.');
            }
            $this->_avatar = $this->_update_image_attachment('avatar', 'avatar', $file['type'], $file['tmp_name']);
            if (! $this->_avatar)
            {
                throw new midcom_error('Failed to update the avatar attachment.');
            }

            // Scale the avatar thumbnail
            if (! $filter->rescale($this->_config->get('avatar_thumbnail_x'), $this->_config->get('avatar_thumbnail_y')))
            {
                throw new midcom_error('Failed to scale the avatar thumbnail attachment.');
            }
            $this->_avatar_thumbnail = $this->_update_image_attachment('avatar_thumbnail', 'avatar_thumbnail', $file['type'], $file['tmp_name']);
            if (! $this->_avatar_thumbnail)
            {
                throw new midcom_error('Failed to update the avatar thumbnail attachment.');
            }

            unlink ($file['tmp_name']);
        }
    }

    /**
     * Internal helper, which takes a file and creates or updates the corresponding attachment
     * identified by its name.
     *
     * @param string $name The image name that should be added (must be one of the image fields
     *     defined in the configuration.
     * @param string $title The title to use.
     * @param string $mimetype The autodetected mimetype (will usually be normalized).
     * @param string $tmpname The file to load.
     * @return midcom_db_attachment The generated / updated attachment, or false on failure.
     */
    private function _update_image_attachment($name, $title, $mimetype, $tmpname)
    {
        $attachment = $this->_account->get_attachment($name);
        if (! $attachment)
        {
            $attachment = $this->_account->create_attachment($name, $title, $mimetype);
            if (! $attachment)
            {
                throw new midcom_error("Failed to create the attachment named {$name}, last Midgard error was: " . midcom_connection::get_error_string());
            }
        }

        if (! $attachment->copy_from_file($tmpname))
        {
            return false;
        }

        $data = @getimagesize($tmpname);
        if ($data)
        {
            $attachment->set_parameter("midcom.helper.datamanager2.type.blob", "size_x", $data[0]);
            $attachment->set_parameter("midcom.helper.datamanager2.type.blob", "size_y", $data[1]);
            $attachment->set_parameter("midcom.helper.datamanager2.type.blob", "size_line", $data[3]);
            switch ($data[2])
            {
                case 1:
                    $mime = "image/gif";
                    break;

                case 2:
                    $mime = "image/jpeg";
                    break;

                case 3:
                    $mime = "image/png";
                    break;

                case 6:
                    $mime = "image/bmp";
                    break;

                case 7:
                case 8:
                    $mime = "image/tiff";
                    break;

                default:
                    $mime = false;
                    break;
            }
            if ($mime !== false)
            {
                $attachment->mimetype = $mime;
                $attachment->update();
            }
        }

        return $attachment;
    }


    /**
     * This function prepares the requestdata with all computed values.
     * A special case is the visible_data array, which maps field names
     * to prepared values, which can be used in display directly. The
     * information returned is already HTML escaped.
     */
    private function _prepare_request_data()
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $att_prefix = $_MIDCOM->get_page_prefix();

        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['fields'] =& $this->_fields;
        $this->_request_data['schema'] =& $this->_datamanager->schema;
        $this->_request_data['account'] =& $this->_account;
        $this->_request_data['avatar'] =& $this->_avatar;
        $this->_request_data['avatar_thumbnail'] =& $this->_avatar_thumbnail;
        $this->_request_data['form_submit_name'] = 'net_nehmer_account_publish';
        $this->_request_data['processing_msg'] = $this->_processing_msg;
        $this->_request_data['processing_msg_raw'] = $this->_processing_msg_raw;
        $this->_request_data['profile_url'] = $prefix;
        $this->_request_data['edit_url'] = "{$prefix}edit/";

        $this->_request_data['account_revised'] = $this->_account->metadata->revised;
        $this->_request_data['account_published'] = $this->_account->metadata->published;

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

        $privilege = $this->_account->get_privilege('midcom:isonline', 'USERS');
        if (   $privilege
            && $privilege->value == MIDCOM_PRIVILEGE_ALLOW)
        {
            $this->_request_data['onlinestate_checked'] = 'checked="checked"';
        }
        else
        {
            $this->_request_data['onlinestate_checked'] = '';
        }
    }

    /**
     * Internal helper function, prepares a datamanager based on the current account.
     */
    private function _prepare_datamanager()
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
     * This function iterates over the field list and produces the information needed to render
     * the form. All linked fields are consolidated together in the order they appear, with the
     * master field being the only one in the main array, and the rest available as "linkers".
     *
     * @see is_field_visisble()
     */
    private function _compute_fields()
    {
        $this->_visible_fields_user_selection = explode(',', $this->_account->get_parameter('net.nehmer.account', 'visible_field_list'));
        $this->_fields = Array();
        $links = Array();

        // Compute data
        foreach ($this->_datamanager->schema->fields as $name => $field)
        {
            if ($field['customdata']['visible_mode'] == 'skip')
            {
                continue;
            }

            $data = Array();
            $data['name'] = $name;
            $data['title'] = $this->_datamanager->schema->translate_schema_string($field['title']);
            $data['mode'] = $field['customdata']['visible_mode'];
            $data['visible'] = $this->_is_field_visible($name);
            $data['content'] = $this->_render_field($name);
            $data['has_linkers'] = false;

            if ($data['mode'] == 'link')
            {
                $from = $name;
                $to = $field['customdata']['visible_link'];
                $links[$to][] = $from;
                $data['linked_to'] = $to;
            }

            $this->_fields[$name] = $data;
        }

        // Process links.
        foreach ($this->_fields as $name => $copy)
        {
            if (array_key_exists($name, $links))
            {
                foreach ($links[$name] as $linker)
                {
                    $this->_fields[$name]['linkers'][$linker] = $this->_fields[$linker];
                    $this->_fields[$name]['has_linkers'] = true;
                    unset($this->_fields[$linker]);
                }
            }
        }
    }

    /**
     * This helper uses the 'visible_mode' customdata member to compute actual visibility of a field.
     * Possible settings:
     *
     * 'always' shows a field unconditionally, 'user' lets the user choose whether he
     * wants it shown, 'never' hides the field unconditionally and 'link' links it to the
     * visibility state of another field. In the last case you need to set the 'visible_link'
     * customdata to the name of another field to make this work.
     *
     * @return boolean Indicating Visibility
     */
    private function _is_field_visible($name)
    {
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
                if ($this->_datamanager->schema->fields[$target]['customdata']['visible_mode'] == 'link')
                {
                    throw new midcom_error("Tried to link the visibility of {$name} to the field {$target}, which is a link field too.");
                }
                return $this->_is_field_visible($target);

            case 'user':
                return in_array($name, $this->_visible_fields_user_selection);
        }
        throw new midcom_error("Unknown Visibility declaration in {$name}: {$this->_datamanager->schema->fields[$name]['customdata']['visible_mode']}.");
    }

    /**
     * A little helper which extracts the view of the given type
     */
    private function _render_field($name)
    {
        return $this->_datamanager->types[$name]->convert_to_html();
    }

    /**
     * This handler loops over all fields, displaying them in turn. The sequence is
     *
     * - show-publish-start
     * - show-publish-field n times
     * - show-publish-end
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_publish($handler_id, &$data)
    {
        midcom_show_style('show-publish-start');
        foreach($this->_fields as $name => $field)
        {
            if ($field['has_linkers'])
            {
                // First go over the linked fields:
                $first_field = true;
                $this->_request_data['total_fields'] = count($field['linkers']) + 1;
                $this->_request_data['linked_field'] = $field;
                $this->_request_data['linked_mode'] = true;
                foreach ($field['linkers'] as $linker)
                {
                    $this->_request_data['current_field'] =& $linker;
                    if ($first_field)
                    {
                        midcom_show_style('show-publish-field-linked-first');
                    }
                    else
                    {
                        midcom_show_style('show-publish-field-linked-next');
                    }
                }
                $this->_request_data['current_field'] =& $this->_fields[$name];
                midcom_show_style('show-publish-field-linked-last');
            }
            else
            {
                $this->_request_data['linked_mode'] = false;
                $this->_request_data['current_field'] =& $this->_fields[$name];
                midcom_show_style('show-publish-field-nolinkers');
            }
        }
        midcom_show_style('show-publish-end');
    }

    /**
     * This handler is shown upon successful processing. While it is always valid, no actual
     * operation is done at this point, the publish handler does this and relocates here on
     * success.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_publish_ok($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_account = $_MIDCOM->auth->user->get_storage();
        $this->_avatar = $this->_account->get_attachment('avatar');
        $this->_avatar_thumbnail = $this->_account->get_attachment('avatar_thumbnail');

        $this->_prepare_datamanager();
        $this->_compute_fields();
        $this->_prepare_request_data();

        $_MIDCOM->substyle_append($this->_datamanager->schema->name);
        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);
        $this->set_active_leaf(NET_NEHMER_ACCOUNT_LEAFID_PUBLISH);
        $_MIDCOM->set_pagetitle($this->_l10n->get('publish account details'));

        return true;
    }

    /**
     * This handler shows the successful-publishing message, it has the same information
     * available, as the actual publishing handler, but it consists of only one style
     * element, containing a simple "your details have been submitted message".
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_publish_ok($handler_id, &$data)
    {
        midcom_show_style('show-publish-ok');
    }
}
?>