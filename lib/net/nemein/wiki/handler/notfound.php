<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: notfound.php 3757 2006-07-27 14:32:42Z bergie $
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
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_notfound($handler_id, $args, &$data)
    {
        $data['wikiword'] = $args[0];
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('title', '=', $data['wikiword']);
        $result = $qb->execute();
        if (count($result) > 0)
        {
            // This wiki page actually exists, so go there as "Permanent Redirect"
            $_MIDCOM->relocate("{$result[0]->name}/", 301);
        }

        // This is a custom "not found" page, send appropriate headers to prevent indexing
        $_MIDCOM->header('Not found', 404);
        $_MIDCOM->set_pagetitle(sprintf($this->_l10n->get('"%s" not found'), $data['wikiword']));

        // TODO: List pages containing the wikiword via indexer

        $data['wiki_name'] = $this->_topic->extra;

        // Populate toolbar for actions available
        $data['wiki_tools'] = new midcom_helper_toolbar();
        $data['wiki_tools']->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => 'create/?wikiword=' . rawurlencode($data['wikiword']),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('create page %s'), $data['wikiword']),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-html.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
            )
        );

        $data['wiki_tools']->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => 'create/redirect/?wikiword=' . rawurlencode($data['wikiword']),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('create redirection page %s'), $data['wikiword']),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-html.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_topic->can_do('midgard:create'),
            )
        );

        $data['wiki_tools']->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => 'http://' . $_MIDCOM->i18n->get_current_language() . '.wikipedia.org/wiki/' . rawurlencode($data['wikiword']),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('look for %s in wikipedia'), $data['wikiword']),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/search.png',
                MIDCOM_TOOLBAR_ENABLED => true,
                MIDCOM_TOOLBAR_OPTIONS => array
                (
                    'rel' => 'directlink',
                )
            )
        );

        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => 'notfound/' . rawurlencode($data['wikiword']),
            MIDCOM_NAV_NAME => $data['wikiword'],
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_notfound($handler_id, &$data)
    {
        midcom_show_style('view-notfound');
    }
}
?>