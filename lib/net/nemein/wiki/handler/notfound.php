<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage "page not found" handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_notfound extends midcom_baseclasses_components_handler
{
    /**
     * @param string $wikiword The page's name
     * @param array &$data The local request data.
     */
    public function _handler_notfound($wikiword, array &$data)
    {
        $data['wikiword'] = $wikiword;
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('title', '=', $wikiword);
        $result = $qb->execute();
        if (count($result) > 0) {
            // This wiki page actually exists, so go there as "Permanent Redirect"
            return new midcom_response_relocate("{$result[0]->name}/", 301);
        }

        // This is a custom "not found" page, send appropriate headers to prevent indexing
        midcom::get()->header('Not found', 404);
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('"%s" not found'), $wikiword));

        // TODO: List pages containing the wikiword via indexer

        $data['wiki_name'] = $this->_topic->extra;

        // Populate toolbar for actions available
        $data['wiki_tools'] = new midcom_helper_toolbar();
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->_topic->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button('create/?wikiword=' . rawurlencode($wikiword), [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('create page %s'), $wikiword),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
                MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
            ]);
            $buttons[] = $workflow->get_button('create/redirect/?wikiword=' . rawurlencode($wikiword), [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('create redirection page %s'), $wikiword),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
                MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
            ]);
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => 'http://' . $this->_i18n->get_current_language() . '.wikipedia.org/wiki/' . rawurlencode($wikiword),
            MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('look for %s in wikipedia'), $wikiword),
            MIDCOM_TOOLBAR_GLYPHICON => 'search',
            MIDCOM_TOOLBAR_OPTIONS => [
                'rel' => 'directlink',
            ]
        ];
        $data['wiki_tools']->add_items($buttons);
        $this->add_breadcrumb('notfound/' . rawurlencode($wikiword), $wikiword);

        return $this->show('view-notfound');
    }
}
