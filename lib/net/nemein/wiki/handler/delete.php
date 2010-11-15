<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: delete.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage delete handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_delete extends midcom_baseclasses_components_handler
{
    /**
     * The wikipage we're deleting
     *
     * @var net_nemein_wiki_wikipage
     * @access private
     */
    var $_page = null;

    /**
     * The Datamanager of the article to display
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Internal helper, loads the datamanager for the current wikipage. Any error triggers a 500.
     *
     * @access private
     */
    function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        if (   ! $this->_datamanager
            || ! $this->_datamanager->autoset_storage($this->_page))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for article {$this->_page->id}.");
            // This will exit.
        }
    }

    function _load_page($wikiword)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '=', $wikiword);
        $result = $qb->execute();

        if (count($result) > 0)
        {
            $this->_page = $result[0];
            return true;
        }
        return false;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_delete($handler_id, $args, &$data, $delete_mode = true)
    {
        $this->_load_page($args[0]);
        if (!$this->_page)
        {
            return false;
        }

        $this->_page->require_do('midgard:delete');

        if (array_key_exists('net_nemein_wiki_deleteok', $_POST))
        {
            $wikiword = $this->_page->title;
            if ($this->_page->delete())
            {
                $_MIDCOM->uimessages->add($this->_request_data['l10n']->get('net.nemein.wiki'), sprintf($this->_request_data['l10n']->get('page %s deleted'), $wikiword), 'ok');

                // Update the index
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->delete($this->_page->guid);

                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX));
            }
            else
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to delete wikipage, reason ".midcom_connection::get_error_string());
                // This will exit.
            }
        }

        $this->_load_datamanager();

        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => "{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n_midcom']->get('cancel'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/cancel.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
        $this->_view_toolbar->bind_to($this->_page);

        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "{$this->_page->name}/",
            MIDCOM_NAV_NAME => $this->_page->title,
        );
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "delete/{$this->_page->name}/",
            MIDCOM_NAV_NAME => $this->_request_data['l10n_midcom']->get('delete'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $_MIDCOM->set_pagetitle($this->_page->title);
        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_delete($handler_id, &$data)
    {
        $this->_request_data['wikipage_view'] = $this->_datamanager->get_content_html();

        // Replace wikiwords
        if (array_key_exists('content', $this->_request_data['wikipage_view']))
        {
            $this->_request_data['wikipage_view']['content'] = preg_replace_callback($this->_config->get('wikilink_regexp'), array($this->_page, 'replace_wikiwords'), $this->_request_data['wikipage_view']['content']);
        }

        midcom_show_style('view-wikipage-delete');
    }
}
?>