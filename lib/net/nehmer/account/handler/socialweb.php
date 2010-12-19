<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: Edit Social Web settings
 *
 * This class allows you to edit social web settingsyour own account.
 * It consists of a standard DM2 edit loop.
 *
 * Summary of available request keys:
 *
 * - controller: A reference to the DM2 controller instance.
 * - schema: A reference to the schema in use.
 * - account: A reference to the account in use.
 * - profile_url: Only applicable in the quick-view mode, it contains the URL
 *   to the full profile record. Use this to link back to the view mode.
 *
 * The class has only a single event handler, with no arguments, as the system always edits
 * the current user account.
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_handler_socialweb extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
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
    public function _handler_edit($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_socialweb'))
        {
            return false;
        }

        if ($handler_id == 'admin_edit')
        {
            $_MIDCOM->auth->require_admin_user();
            $this->_account = new midcom_db_person($args[0]);
            if (   !$this->_account
                || !$this->_account->guid)
            {
                throw new midcom_error_notfound("The account '{$args[0]}' could not be loaded, reason: " . midcom_connection::get_error_string());
            }
            net_nehmer_account_viewer::verify_person_privileges($this->_account);
            $return_url = "view/{$this->_account->guid}/";
        }
        else
        {
            $_MIDCOM->auth->require_valid_user();
            $this->_account = $_MIDCOM->auth->user->get_storage();
            net_nehmer_account_viewer::verify_person_privileges($this->_account);
            $_MIDCOM->auth->require_do('midgard:update', $this->_account);
            $_MIDCOM->auth->require_do('midgard:parameters', $this->_account);
            $return_url = '';
        }

        // This will shortcut without creating any datamanager to avoid the possibly
        // expensive creation process.
        if (midcom_helper_datamanager2_formmanager::get_clicked_button() == 'cancel')
        {
            // Relocate back to view

            $_MIDCOM->relocate($return_url);
            // This will exit.
        }

        $data['datamanager'] = $this->get_controller('simple', $this->_account);

        if ($data['datamanager']->process_form() == 'save')
        {
            // Relocate back to view
            $_MIDCOM->relocate($return_url);
            // This will exit.
        }

        $this->_prepare_request_data($return_url);

        $this->add_breadcrumb('socialweb/', $this->_l10n->get('social web settings'));
        $this->_view_toolbar->hide_item('socialweb/');

        $_MIDCOM->bind_view_to_object($this->_account, $data['datamanager']->datamanager->schema->name);
        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);
        $_MIDCOM->set_pagetitle($this->_l10n->get('social web settings'));

        return true;
    }

    /**
     * This function prepares the request data with all computed values.
     *
     * @param string $return_url The URL to return to the profile page (different for admin-
     *     and no-admin mode).
     */
    private function _prepare_request_data($return_url)
    {
        $this->_request_data['schema'] =& $this->_request_data['datamanager']->datamanager->schema;
        $this->_request_data['account'] =& $this->_account;
        $this->_request_data['profile_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
            . $return_url;
    }

    public function load_schemadb()
    {
    	$schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_socialweb'));
        $customdata = $_MIDCOM->componentloader->get_all_manifest_customdata('net.nehmer.account.socialweb');

        foreach ($customdata as $component => $settings)
        {
            if (   $component == 'org.routamc.positioning'
                && !$GLOBALS['midcom_config']['positioning_enable'])
            {
                // Skip
                continue;
            }
            foreach ($settings as $label => $field_config)
            {
                if (!isset($field_config['type']))
                {
                    $field_config['type'] = 'text';
                }

                if (!isset($field_config['widget']))
                {
                    $field_config['widget'] = 'text';
                }

                if (!isset($field_config['title']))
                {
                    $field_config['title'] = $_MIDCOM->i18n->get_string($label, $component);;
                }

                $schemadb['socialweb']->append_field(str_replace('.', '_', $component) . "_{$label}", $field_config);
            }
        }
        return $schemadb;
    }

    public function get_schema_name()
    {
    	return 'socialweb';
    }

    /**
     * The rendering code consists of a standard init/loop/end construct.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit($handler_id, &$data)
    {
        midcom_show_style('show-edit-socialweb');
    }
}
?>