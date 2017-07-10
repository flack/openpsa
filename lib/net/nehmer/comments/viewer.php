<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Comments site interface class
 *
 * See the various handler classes for details.
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_viewer extends midcom_baseclasses_components_request
{
    public static function add_head_elements()
    {
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/net.nehmer.comments/comments.css');
    }

    /**
     * Populates the node toolbar depending on the user's rights.
     */
    private function _populate_node_toolbar()
    {
        $buttons = [];
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button('config/', [
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_folder-properties.png',
            ]);
        }
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('net.nehmer.comments:moderation')) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/reported_abuse/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('reported abuse'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('reported abuse helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/abuse/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('abuse'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('abuse helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/junk/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('junk'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('junk helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('latest comments'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('latest helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest_new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('only new'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('only new helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest_approved/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('only approved'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('only approved helptext'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_news.png',
            ];
        }
        $this->_node_toolbar->add_items($buttons);
    }

    public function _populate_post_toolbar(net_nehmer_comments_comment $comment, $viewtype = null)
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
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_help-agent.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                ];
            } else {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/confirm_abuse/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('confirm abuse'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                ];
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/confirm_junk/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('confirm junk'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $comment->can_do('net.nehmer.comments:moderation'),
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                        'return_url' => midcom_connection::get_url('uri'),
                    ]
                ];
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => "report/not_abuse/{$comment->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('not abuse'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/ok.png',
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
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editdelete.png',
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

    /**
     * Generic request startup work:
     * - Populate the Node Toolbar
     */
    public function _on_handle($handler, array $args)
    {
        $this->_populate_node_toolbar();
    }
}
