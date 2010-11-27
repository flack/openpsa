<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: navigation.php 20857 2009-03-05 19:45:48Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Marketplace NAP interface class
 *
 * @package net.nehmer.buddylist
 */

class net_nehmer_buddylist_navigation extends midcom_baseclasses_components_navigation
{
    function get_leaves()
    {
        $leaves = Array();

        if ($_MIDCOM->auth->user)
        {
            $leaves[NET_NEHMER_BUDDYLIST_LEAFID_PENDING] = array
            (
                MIDCOM_NAV_URL => "pending/list.html",
                MIDCOM_NAV_NAME => $this->_l10n->get('buddy requests'),
            );
        }

        return $leaves;
    }
}
?>