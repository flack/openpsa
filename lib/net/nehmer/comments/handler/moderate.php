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
     * Marks comment as possible abuse
     *
     * @param array $args The argument list.
     */
    public function _handler_abuse(array $args)
    {
        $this->load_comment($args[0], false);
        $moderators = $this->_config->get('moderators');
        if (   $this->_comment->report_abuse()
            && $moderators) {
            // Prepare notification message
            $message = [];
            $message['title'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $this->_comment->title);
            $message['content'] = '';
            $logs = $this->_comment->get_logs();
            if (count($logs) > 0) {
                $message['content'] .= $this->_l10n->get('moderation history').":\n\n";
                foreach ($logs as $time => $log) {
                    $reported = $this->_l10n->get_formatter()->datetime(strtotime("{$time}Z"));
                    $message['content'] .= $this->_l10n->get(sprintf('%s: %s by %s (from %s)', "$reported:\n", $this->_l10n->get($log['action']), $log['reporter'], $log['ip'])) . "\n\n";
                }
            }
            $message['content'] = "\n\n" . midcom::get()->permalinks->create_permalink($this->_comment->objectguid);

            $message['abstract'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $this->_comment->title);
            $message['abstract'] .= " " . midcom::get()->permalinks->create_permalink($this->_comment->objectguid);

            // Notify moderators
            $moderator_guids = explode('|', $moderators);
            foreach (array_filter($moderator_guids) as $moderator_guid) {
                org_openpsa_notifications::notify('net.nehmer.comments:report_abuse', $moderator_guid, $message);
            }
        }
        return $this->reply();
    }

    /**
     * Marks comment as not abuse
     *
     * @param array $args The argument list.
     */
    public function _handler_not_abuse(array $args)
    {
        $this->load_comment($args[0]);
        $this->_comment->report_not_abuse();
        return $this->reply();
    }

    /**
     * Marks comment as confirmed abuse
     *
     * @param array $args The argument list.
     */
    public function _handler_confirm_abuse(array $args)
    {
        $this->load_comment($args[0]);
        $this->_comment->confirm_abuse();

        $indexer = midcom::get()->indexer;
        $indexer->delete($this->_comment->guid);
        return $this->reply();
    }

    /**
     * Marks comment as confirmed junk
     *
     * @param array $args The argument list.
     */
    public function _handler_confirm_junk(array $args)
    {
        $this->load_comment($args[0]);
        $this->_comment->confirm_junk();

        $indexer = midcom::get()->indexer;
        $indexer->delete($this->_comment->guid);
        return $this->reply();
    }

    private function load_comment($identifier, $require_moderation_privilege = true)
    {
        $this->_comment = new net_nehmer_comments_comment($identifier);

        if (!$this->_comment->can_do('midgard:update')) {
            $this->_comment->_sudo_requested = midcom::get()->auth->request_sudo('net.nehmer.comments');;
        }
        if ($require_moderation_privilege) {
            $this->_comment->require_do('net.nehmer.comments:moderation');
        }
    }

    private function reply()
    {
        if ($this->_comment->_sudo_requested) {
            midcom::get()->auth->drop_sudo();
        }

        if (isset($_POST['return_url'])) {
            return new midcom_response_relocate($_POST['return_url']);
        }

        return new midcom_response_relocate('');
    }
}
