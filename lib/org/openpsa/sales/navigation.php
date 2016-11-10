<?php
/**
 * @package org.openpsa.sales
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.sales NAP interface class.
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_navigation extends midcom_baseclasses_components_navigation
{
    public function get_leaves()
    {
        $leaves = array();
        $modes = array('active', 'won', 'delivered', 'invoiced', 'lost', 'canceled');
        foreach ($modes as $mode) {
            $leaves["{$this->_topic->id}:{$mode}"] = array
            (
                MIDCOM_NAV_URL => "list/{$mode}/",
                MIDCOM_NAV_NAME => $this->_l10n->get('salesprojects ' . $mode),
            );
        }
        return $leaves;
    }
}
