<?php
/**
 * @package net.nemein.tag 
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Tag handling library interface class
 * 
 * @package net.nemein.tag
 */
class net_nemein_tag_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        parent::__construct();
        $this->_component = 'net.nemein.tag';
    }

    /**
     * Ensure tag links pointing to an object are deleted when the object is
     */
    function _on_watched_dba_delete($object)
    {
        $qb = net_nemein_tag_link_dba::new_query_builder();
        $qb->add_constraint('fromGuid', '=', $object->guid);
        if ($qb->count_unchecked() == 0)
        {
            return;
        }
        
        $tag_links = $qb->execute();
        foreach ($tag_links as $tag_link)
        {
            $tag_link->delete();
        }
    }
}
?>