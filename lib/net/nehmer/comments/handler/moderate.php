<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Comments moderation handler
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_handler_moderate extends midcom_baseclasses_components_handler
{
    /**
     * Comment we are currently working with.
     *
     * @var net_nehmer_comments_comment
     */
    private $_comment;

    /**
     * The GUID of the object we're bound to.
     *
     * @var string GUID
     */
    private $_objectguid;

    /**
     * Marks comment as possible abuse
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_report($handler_id, array $args, array &$data)
    {
        if (!array_key_exists('mark', $_POST))
        {
            throw new midcom_error('No post data found');
        }

        $this->_comment = new net_nehmer_comments_comment($args[0]);
        $this->_comment->_sudo_requested = false;

        if (!$this->_comment->can_do('midgard:update'))
        {
            $this->_comment->_sudo_requested = true;
            midcom::get()->auth->request_sudo('net.nehmer.comments');
        }

        switch ($_POST['mark'])
        {
            case 'abuse':
                $this->_report_abuse();
                break;

            case 'confirm_abuse':
                $this->_comment->require_do('net.nehmer.comments:moderation');
                // Confirm the message is abuse
                $this->_comment->confirm_abuse();

                // Update the index
                $indexer = midcom::get()->indexer;
                $indexer->delete($this->_comment->guid);
                break;

            case 'confirm_junk':
                $this->_comment->require_do('net.nehmer.comments:moderation');
                // Confirm the message is abuse
                $this->_comment->confirm_junk();

                // Update the index
                $indexer = midcom::get()->indexer;
                $indexer->delete($this->_comment->guid);
                break;

            case 'not_abuse':
                $this->_comment->require_do('net.nehmer.comments:moderation');
                // Confirm the message is abuse
                $this->_comment->report_not_abuse();
                break;
        }
        if ($this->_comment->_sudo_requested)
        {
            $this->_comment->_sudo_requested = false;
            midcom::get()->auth->drop_sudo();
        }

        if (isset($_POST['return_url']))
        {
            return new midcom_response_relocate($_POST['return_url']);
        }

        return new midcom_response_relocate('');
    }

    private function _report_abuse()
    {
        // Report the abuse
        $moderators = $this->_config->get('moderators');
        if (   $this->_comment->report_abuse()
            && $moderators)
        {
            // Prepare notification message
            $message = array();
            $message['title'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $this->_comment->title);
            $message['content'] = '';
            $logs = $this->_comment->get_logs();
            if (count($logs) > 0)
            {
                $message['content'] .= $this->_l10n->get('moderation history').":\n\n";
                foreach ($logs as $time => $log)
                {
                    $reported = $this->_l10n->get_formatter()->datetime(strtotime("{$time}Z"));
                    $message['content'] .= $this->_l10n->get(sprintf('%s: %s by %s (from %s)', "$reported:\n", $this->_l10n->get($log['action']), $log['reporter'], $log['ip'])) . "\n\n";
                }
            }
            $message['content'] = "\n\n" . midcom::get()->permalinks->create_permalink($this->_comment->objectguid);

            $message['abstract'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $this->_comment->title);
            $message['abstract'] .= " " . midcom::get()->permalinks->create_permalink($this->_comment->objectguid);

            // Notify moderators
            $moderator_guids = explode('|', $moderators);
            foreach (array_filter($moderator_guids) as $moderator_guid)
            {
                org_openpsa_notifications::notify('net.nehmer.comments:report_abuse', $moderator_guid, $message);
            }
        }
    }
}
