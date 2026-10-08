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
class net_nehmer_comments_viewer extends midcom_baseclasses_components_viewer
{
    public static function add_head_elements()
    {
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/net.nehmer.comments/comments.css');
    }

    /**
     * Generic request startup work:
     * - Populate the Node Toolbar depengin on the user's rights
     */
    public function _on_handle($handler, array $args)
    {
        $buttons = [];
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('midcom:component_config')) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button('config/', [
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('component configuration helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'wrench',
            ]);
        }
        if (   $this->_topic->can_do('midgard:update')
            && $this->_topic->can_do('net.nehmer.comments:moderation')) {
            $views = [
                'reported_abuse' => ['reported_abuse', 'flag'],
                'abuse' => ['abuse', 'ban'],
                'junk' => ['junk', 'trash'],
                'latest' => ['latest comments', 'comments-o'],
                'latest_new' => ['only new', 'clock-o'],
                'latest_approved' => ['only approved', 'check'],
            ];
            foreach ($views as $url => [$label, $glyphicon]) {
                $buttons[] = [
                    MIDCOM_TOOLBAR_URL => 'moderate/' . $url . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($label),
                    MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n->get($label . ' helptext'),
                    MIDCOM_TOOLBAR_GLYPHICON => $glyphicon,
                ];
            }
        }
        $this->_node_toolbar->add_items($buttons);
    }
}
