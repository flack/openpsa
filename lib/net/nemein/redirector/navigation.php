<?php
/**
 * @package net.nemein.redirector
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * net.nemein.redirector NAP interface class.
 *
 * @package net.nemein.redirector
 */
class net_nemein_redirector_navigation extends midcom_baseclasses_components_navigation
{
    public function get_leaves()
    {
        $leaves = [];
        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);
        $qb->add_order('metadata.score', 'DESC');
        $qb->add_order('title');

        // Get the results
        $results = $qb->execute();

        foreach ($results as $tinyurl) {
            $leaves[$tinyurl->id] = [
                MIDCOM_NAV_URL => "{$tinyurl->name}/",
                MIDCOM_NAV_NAME => $tinyurl->title,
                MIDCOM_NAV_GUID => $tinyurl->guid,
                MIDCOM_NAV_OBJECT => $tinyurl,
            ];
        }

        return $leaves;
    }
}
