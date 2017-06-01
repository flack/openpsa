<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static NAP interface class
 *
 * See the individual member documentations about special NAP options in use.
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_navigation extends midcom_baseclasses_components_navigation
{
    /**
     * Returns all leaves for the current content topic.
     *
     * It will hide the index leaf from the NAP information unless we are in Autoindex
     * mode. The leaves' titles are used as a description within NAP, and the toolbar will
     * contain edit and delete links.
     */
    public function get_leaves()
    {
        $leaves = array();
        if ($this->_config->get('hide_navigation')) {
            return $leaves;
        }

        $qb = net_nehmer_static_viewer::get_topic_qb($this->_config, $this->_topic->id);

        $qb->add_constraint('up', '=', 0);
        $qb->add_constraint('metadata.navnoentry', '=', 0);
        $qb->add_constraint('name', '<>', '');

        // Unless in Auto-Index mode or the index article is hidden, we skip the index article.
        if (   !$this->_config->get('autoindex')
            && !$this->_config->get('indexinnav')) {
            $qb->add_constraint('name', '<>', 'index');
        }

        // Sort items with the same primary sort key by title.
        $qb->add_order('title');

        $articles = $qb->execute();

        foreach ($articles as $article) {
            $article_url = ($article->name == 'index') ? '' : "{$article->name}/";
            $leaves[$article->id] = array(
                MIDCOM_NAV_URL => $article_url,
                MIDCOM_NAV_NAME => $article->title ?: $article->name,
                MIDCOM_NAV_GUID => $article->guid,
                MIDCOM_NAV_OBJECT => $article,
            );
        }

        return $leaves;
    }
}
