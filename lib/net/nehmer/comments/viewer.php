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
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/reported_abuse/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('reported abuse'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('reported abuse helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'flag',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/abuse/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('abuse'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('abuse helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'ban',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/junk/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('junk'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('junk helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'trash',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('latest comments'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('latest helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'comments-o',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest_new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('only new'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('only new helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'clock-o',
            ];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => 'moderate/latest_approved/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('only approved'),
                MIDCOM_TOOLBAR_HELPTEXT => $this->_l10n_midcom->get('only approved helptext'),
                MIDCOM_TOOLBAR_GLYPHICON => 'check',
            ];
        }
        $this->_node_toolbar->add_items($buttons);
    }
}
