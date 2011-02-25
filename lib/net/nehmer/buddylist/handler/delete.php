<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Buddylist deletion handler
 *
 * This handler just processes the delete operations and displays a simple success page.
 *
 * @package net.nehmer.buddylist
 */

class net_nehmer_buddylist_handler_delete extends midcom_baseclasses_components_handler
{
    /**
     * The welcome handler loads the newest asks / bids according to the configuration
     * settings and prepares the type listings.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Check for basic resultset validity
        if (! array_key_exists('net_nehmer_buddylist_delete', $_REQUEST))
        {
            throw new midcom_error('Incomplete request.');
        }

        $relocate_to = '';
        if (array_key_exists('relocate_to', $_REQUEST))
        {
            $relocate_to = $_REQUEST['relocate_to'];
        }

        // Filter all account_* request params out so that we know which buddies we do no
        // longer want.
        $to_delete = Array();
        foreach ($_REQUEST as $key => $value)
        {
            if (substr($key, 0, 8) == 'account_')
            {
                $to_delete[] = substr($key, 8);
            }
        }
        if (! $to_delete)
        {
            // In case we have no checks in the request, we just return to the welcome
            // page and do nothing.
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.buddylist'), $this->_l10n->get('no entries to be deleted.'), 'ok');
            $_MIDCOM->relocate($relocate_to);
            // This will exit.
        }

        $user_guid = $_MIDCOM->auth->user->guid;
        foreach ($to_delete as $buddy_guid)
        {
            $qb = net_nehmer_buddylist_entry::new_query_builder();
            $qb->add_constraint('account', '=', $user_guid);
            $qb->add_constraint('buddy', '=', $buddy_guid);
            $result = $qb->execute();
            if (! $result)
            {
                // Tampered request data? We cannot find the record. Skipping...
                debug_add("Failed to retrieve the buddy {$buddy_guid} for the account {$user_guid}. Skipping this request key.", MIDCOM_LOG_INFO);
                continue;
            }

            $result[0]->delete();
        }

        if ($relocate_to != '')
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.buddylist'), $this->_l10n->get('the buddies have been deleted.'), 'ok');
            $_MIDCOM->relocate($relocate_to);
        }

        $_MIDCOM->set_26_request_metadata(time(), null);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: " . $this->_l10n->get('buddies deleted'));
        $this->add_breadcrumb("delete/", $this->_l10n->get('buddies deleted'));
    }

    /**
     * Displays a simple success page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, &$data)
    {
        midcom_show_style('delete-ok');
    }
}
?>