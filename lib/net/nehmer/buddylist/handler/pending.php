<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Pending requests handler
 *
 * @package net.nehmer.buddylist
 */
class net_nehmer_buddylist_handler_pending extends midcom_baseclasses_components_handler
{
    /**
     * A list of pending buddylist requests, each entry is a map containing these elements:
     *
     * - net_nehmer_buddylist_entry entry The actual buddylist entry object.
     * - midcom_core_user account_user The user requesting approval.
     * - string form_action The form action to use for processing.
     * - string guid_hidden_value The value of this field must be added as a hidden form field named "guid".
     * - string reject_submit_name The name of the reject button.
     * - string approve_submit_name The name of the approve button.
     * - string approve_and_add_submit_name The name of the approve & add buttonl.
     *
     * Only valid for the list_pending target.
     *
     * @var Array
     * @access protected
     */
    private $_pending = null;

    /**
     * The entry being processed, only valid for the process targets.
     *
     * @var net_nehmer_buddylist_entry
     * @access protected
     */
    private $_entry = null;

    /**
     * Processing message, only valid for the process targets.
     *
     * @var net_nehmer_buddylist_entry
     * @access protected
     */
    private $_processing_msg = null;

    /**
     * Untranslated processing message, only valid for the process targets.
     *
     * @var net_nehmer_buddylist_entry
     * @access protected
     */
    private $_processing_msg_raw = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        if ($this->_processing_msg_raw)
        {
            $this->_processing_msg = $this->_l10n->get($this->_processing_msg_raw);
        }

        $this->_request_data['pending'] =& $this->_pending;
        $this->_request_data['entry'] =& $this->_entry;
        $this->_request_data['processing_msg_raw'] =& $this->_processing_msg_raw;
        $this->_request_data['processing_msg'] =& $this->_processing_msg;
    }

    /**
     * The welcome handler loads the newest asks / bids according to the configuration
     * settings and prepares the type listings.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_load_pending();

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata(time(), null);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: " . $this->_l10n->get('buddy requests'));
        $this->set_active_leaf(NET_NEHMER_BUDDYLIST_LEAFID_PENDING);

        return true;
    }

    /**
     * Prepares the $_pending member.
     */
    private function _load_pending()
    {
        $this->_pending = Array();

        $pending = net_nehmer_buddylist_entry::list_unapproved();
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'pending/';
        if ($this->_config->get('net_nehmer_account_integration'))
        {
            $account_prefix = $_MIDCOM->get_page_prefix() . $this->_config->get('net_nehmer_account_integration') . 'view/';
        }
        else
        {
            $account_prefix = null;
        }

        foreach ($pending as $entry)
        {
            $tmp = Array();
            $tmp['entry'] = $entry;
            $tmp['account_user'] =& $entry->get_account_user();
            $tmp['form_action'] = "{$prefix}process.html";
            $tmp['guid_hidden_value'] = $entry->guid;
            $tmp['reject_submit_name'] = 'net_nehmer_buddylist_reject';
            $tmp['approve_submit_name'] = 'net_nehmer_buddylist_approve';
            if (net_nehmer_buddylist_entry::is_on_buddy_list($tmp['account_user']))
            {
                $tmp['approve_and_add_submit_name'] = null;
            }
            else
            {
                $tmp['approve_and_add_submit_name'] = 'net_nehmer_buddylist_approve_and_add';
            }
            if ($account_prefix)
            {
                $tmp['view_account_url'] = "{$account_prefix}{$entry->account}.html";
            }
            else
            {
                $tmp['view_account_url'] = null;
            }
            $this->_pending[] = $tmp;
        }
    }

    /**
     * Shows the pending list page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        midcom_show_style('pending-start');

        foreach ($this->_pending as $entry)
        {
            $data['entry'] = $entry;
            midcom_show_style('pending-item');
        }

        midcom_show_style('pending-end');
    }

    /**
     * The welcome handler loads the newest asks / bids according to the configuration
     * settings and prepares the type listings.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_process($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Validate request integrity as far as we can.
        if (! array_key_exists('guid', $_REQUEST))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Incomplete Request');
            // This will exit.
        }

        $this->_entry = new net_nehmer_buddylist_entry($_REQUEST['guid']);
        if (! $this->_entry)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Request invalid: Failed to load {$_REQUEST['guid']}.");
            // This will exit.
        }

        if (array_key_exists('net_nehmer_buddylist_reject', $_REQUEST))
        {
            $this->_process_reject();
        }
        else if (array_key_exists('net_nehmer_buddylist_approve', $_REQUEST))
        {
            $this->_process_approve();
        }
        else if (array_key_exists('net_nehmer_buddylist_approve_and_add', $_REQUEST))
        {
            $this->_process_approve_and_add();
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Incomplete Request');
            // This will exit.
        }

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata(time(), null);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: " . $this->_l10n->get('buddy requests'));
        $this->set_active_leaf(NET_NEHMER_BUDDYLIST_LEAFID_PENDING);

        return true;
    }

    /**
     * Processes buddy rejection.
     */
    private function _process_reject()
    {
        $this->_entry->reject();
        $this->_processing_msg_raw = 'request rejected.';
    }

    /**
     * Processes buddy approval.
     */
    private function _process_approve()
    {
        $this->_entry->approve();
        $this->_processing_msg_raw = 'request approved.';
    }

    /**
     * Processes buddy approval and opens a buddy request for the original account.
     */
    private function _process_approve_and_add()
    {
        $this->_entry->approve();
        $this->_processing_msg_raw = 'request approved and buddy request sent.';

        // We protect this against f5 accidents, this way we avoid duplicates at this point.
        if (! net_nehmer_buddylist_entry::is_on_buddy_list($this->_entry->get_account_user()))
        {
            $buddy = new net_nehmer_buddylist_entry();
            $buddy->buddy = $this->_entry->account;
            $buddy->account = $this->_entry->buddy;
            $buddy->create();

            // If the auto-approve config option for these return requests is set,
            // we enter sudo mode and auto-approve the new record.
            if ($this->_config->get('auto_approve_return_requests'))
            {
                if (! $_MIDCOM->auth->request_sudo($this->_component))
                {
                    debug_add('Failed to auto-approve the return request, could not acquire sudo. This will be ignored, the user will have to clear the record manually.',
                        MIDCOM_LOG_ERROR);
                    return;
                }

                $buddy->approve();
                $this->_processing_msg_raw = 'request approved and buddy added to own list.';

                $_MIDCOM->auth->drop_sudo();
            }
        }
    }

    /**
     * Shows the processing result page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_process($handler_id, &$data)
    {
        if (net_nehmer_buddylist_entry::get_unapproved_count() > 0)
        {
            $data['return_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . 'pending/list.html';
        }
        else
        {
            $data['return_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        }
        midcom_show_style('pending-processed');
    }
}
?>