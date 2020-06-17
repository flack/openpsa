<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Comments moderation handler
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_handler_moderate extends midcom_baseclasses_components_handler
{
    /**
     * Marks comment as possible abuse
     */
    public function _handler_report_abuse(Request $request, string $guid)
    {
        $comment = $this->load_comment($guid, false);

        if ($comment->status !== net_nehmer_comments_comment::MODERATED) {
            if ($comment->can_do('net.nehmer.comments:moderation')) {
                $status = net_nehmer_comments_comment::ABUSE;
            } else {
                $status = net_nehmer_comments_comment::REPORTED_ABUSE;
            }
            $sudo = false;
            if (!$comment->can_do('midgard:update')) {
                $sudo = midcom::get()->auth->request_sudo('net.nehmer.comments');
            }

            if ($comment->moderate($status, 'reported_abuse')) {
                if ($moderators = $this->_config->get('moderators')) {
                    $this->notify_moderators($moderators, $comment);
                }
            }
            if ($sudo) {
                midcom::get()->auth->drop_sudo();
            }
        }

        return $this->reply($request);
    }

    private function notify_moderators(string $moderators, net_nehmer_comments_comment $comment)
    {
        // Prepare notification message
        $message = [];
        $message['title'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $comment->title);
        $message['content'] = '';
        $logs = $comment->get_logs();
        if (!empty($logs)) {
            $message['content'] .= $this->_l10n->get('moderation history').":\n\n";
            foreach ($logs as $time => $log) {
                $reported = $this->_l10n->get_formatter()->datetime(strtotime("{$time}Z"));
                $message['content'] .= $this->_l10n->get(sprintf('%s: %s by %s (from %s)', "$reported:\n", $this->_l10n->get($log['action']), $log['reporter'], $log['ip'])) . "\n\n";
            }
        }
        $message['content'] = "\n\n" . midcom::get()->permalinks->create_permalink($comment->objectguid);

        $message['abstract'] = sprintf($this->_l10n->get('comment %s reported as abuse'), $comment->title);
        $message['abstract'] .= " " . midcom::get()->permalinks->create_permalink($comment->objectguid);

        // Notify moderators
        $moderator_guids = explode('|', $moderators);
        foreach (array_filter($moderator_guids) as $moderator_guid) {
            org_openpsa_notifications::notify('net.nehmer.comments:report_abuse', $moderator_guid, $message);
        }
    }

    /**
     * Marks comment as not abuse
     */
    public function _handler_not_abuse(Request $request, string $guid)
    {
        $comment = $this->load_comment($guid);
        $comment->moderate(net_nehmer_comments_comment::MODERATED, 'reported_not_abuse');
        return $this->reply($request);
    }

    public function _handler_confirm_report(Request $request, string $action, string $guid)
    {
        $comment = $this->load_comment($guid);
        if ($comment->status !== net_nehmer_comments_comment::MODERATED) {
            if ($action == 'confirm_abuse') {
                $comment->moderate(net_nehmer_comments_comment::ABUSE, 'confirmed_abuse');
            } else {
                $comment->moderate(net_nehmer_comments_comment::JUNK, 'confirmed_junk');
            }
        }
        midcom::get()->indexer->delete([$comment->guid]);
        return $this->reply($request);
    }

    private function load_comment(string $identifier, bool $require_moderation_privilege = true) : net_nehmer_comments_comment
    {
        $comment = new net_nehmer_comments_comment($identifier);

        if ($require_moderation_privilege) {
            $comment->require_do('net.nehmer.comments:moderation');
        }
        return $comment;
    }

    private function reply(Request $request) : midcom_response_relocate
    {
        return new midcom_response_relocate($request->request->get('return_url', ''));
    }
}
