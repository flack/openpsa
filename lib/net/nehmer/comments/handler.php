<?php
/**
 * @package net.nehmer.comments
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package net.nehmer.comments
 */
trait net_nehmer_comments_handler
{
    public function populate_post_toolbar(net_nehmer_comments_comment $comment, $viewtype = null)
    {
        $toolbar = new midcom_helper_toolbar();
        $buttons = [];
        if (   midcom::get()->auth->user
            && $comment->status < net_nehmer_comments_comment::MODERATED) {
            if (!$comment->can_do('net.nehmer.comments:moderation')) {
                // Regular users can only report abuse
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/abuse/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('report abuse'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'flag',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                    ];
            } else {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/confirm_abuse/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('confirm abuse'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'ban',
                    MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                    ];
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/confirm_junk/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('confirm junk'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'ban',
                    MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                    ];
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/not_abuse/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('not abuse'),
                    MIDCOM_TOOLBAR_GLYPHICON => 'check',
                    MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                    ];
                if (!empty($viewtype)) {
                    $buttons[] = [
                        MIDCOM_TOOLBAR_URL => 'moderate/ajax/' . $viewtype . '/',
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete'),
                        MIDCOM_TOOLBAR_GLYPHICON => 'trash',
                        MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                        MIDCOM_TOOLBAR_OPTIONS => [
                            'class' => 'moderate-ajax',
                            'data-guid' => $comment->guid,
                            'data-action' => 'action_delete',
                        ]
                    ];
                }
            }
        }
        $toolbar->add_items($buttons);
        return $toolbar;
    }
}
