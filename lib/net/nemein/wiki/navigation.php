<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: navigation.php 24773 2010-01-18 08:15:45Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wiki NAP interface class.
 * 
 * @package net.nemein.wiki
 */
class net_nemein_wiki_navigation  extends midcom_baseclasses_components_navigation
{
    /**
     * Get the leaves set to be displayed in navigation
     * 
     * @access public
     * @return array
     */
    function get_leaves()
    {
        // Get the required information with midgard_collector
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_constraint('view', '=', 1);
        
        // Set the order of navigation
        $qb->add_order('metadata.score', 'DESC');
        $qb->add_order('title');
        $qb->add_order('name');
        
        // Return an empty result set
        if ($qb->count() === 0)
        {
            return array();
        }
        
        
        $leaves = array();
        
        // Get the leaves
        foreach ($qb->execute() as $article)
        {
            $leaves[$article->id] = array
            (
                MIDCOM_NAV_URL => $article->name,
                MIDCOM_NAV_NAME => ($article->title) ? $article->title : $article->name,
                MIDCOM_NAV_GUID => $article->guid,
                MIDCOM_NAV_OBJECT => $article,
            );
        }
        
        // Finally return the leaves
        return $leaves;
    }
}
?>