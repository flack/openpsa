<?php
/**
 * @package org.openpsa.imp
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA MidCOM-Horde/Imp "SSO"
 *
 * @package org.openpsa.imp
 */
class org_openpsa_imp_interface extends midcom_baseclasses_components_interface
{

    function __construct()
    {
        parent::__construct();

        $this->_component = 'org.openpsa.imp';
    }
}
?>