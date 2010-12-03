<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.org NAP interface class.
 *
 * NAP is mainly used for toolbar rendering in this component
 *
 * ...
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_navigation extends midcom_baseclasses_components_navigation
{
    function _is_initialized()
    {
        $config = false;
        if (org_openpsa_contacts_interface::find_root_group($config))
        {
            return true;
        }
        return false;
    }
}
?>