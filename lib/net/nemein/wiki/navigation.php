<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
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
     * @return array
     */
    public function get_leaves()
    {
        $leaves = array();

        // Get the required information with midgard_collector
        $mc = net_nemein_wiki_wikipage::new_collector('topic', $this->_topic->id);
        $mc->add_value_property('id');
        $mc->add_value_property('name');
        $mc->add_value_property('title');
        // Set the order of navigation
        $mc->add_order('metadata.score', 'DESC');
        $mc->add_order('title');
        $mc->add_order('name');
        $guids = $mc->list_keys();

        // Return an empty result set
        if (count($guids) === 0)
        {
            return $leaves;
        }

        // Get the leaves
        foreach ($guids as $guid => $empty)
        {
            $values = $mc->get($guid);
            $leaves[$values['id']] = array
            (
                MIDCOM_NAV_URL => $values['name'],
                MIDCOM_NAV_NAME => ($values['title']) ? $values['title'] : $values['name'],
                MIDCOM_NAV_GUID => $guid,
                MIDCOM_NAV_OBJECT => new midcom_core_dbaproxy($guid, 'net_nemein_wiki_wikipage'),
            );
        }

        // Finally return the leaves
        return $leaves;
    }
}
?>