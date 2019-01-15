<?php
/**
 * @package net.nemein.wiki
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package net.nemein.wiki
 */
trait net_nemein_wiki_handler
{
    public function load_page($wikiword)
    {
        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('name', '=', $wikiword);
        $result = $qb->execute();

        if (!empty($result)) {
            return $result[0];
        }
        throw new midcom_error_notfound('The page "' . $wikiword . '" could not be found.');
    }

    public function initialize_index_article(midcom_db_topic $topic)
    {
        $page = new net_nemein_wiki_wikipage();
        $page->topic = $topic->id;
        $page->name = 'index';
        $page->title = $topic->extra;
        $page->content = $this->_l10n->get('wiki default page content');
        $page->author = midcom_connection::get_user();
        if (!$page->create()) {
            throw new midcom_error('Failed to create index article: ' . midcom_connection::get_error_string());
        }
        return $page;
    }
}
