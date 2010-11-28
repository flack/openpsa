<?php
/**
 * @package org.openpsa.imp
 * @author Nemein Oy, http://www.nemein.com/
 * @version $Id: navigation.php,v 1.1 2005/07/29 15:02:08 bergius Exp $
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.imp NAP interface class.
 *
 * NAP is mainly used for toolbar rendering in this component
 *
 * @package org.openpsa.imp
 */
class org_openpsa_imp_navigation extends midcom_baseclasses_components_navigation
{
    function get_leaves()
    {
        $leaves = array();
        return $leaves;
    }
}
?>