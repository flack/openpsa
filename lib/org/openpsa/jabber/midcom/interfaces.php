<?php
/**
 * OpenPSA Jabber Instant Messaging Component
 *
 * @package org.openpsa.jabber
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.jabber
 */
class org_openpsa_jabber_interface extends midcom_baseclasses_components_interface
{
    function __construct()
    {
        parent::__construct();

        $this->_component = 'org.openpsa.jabber';
        $this->_autoload_files = array();
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );

    }
}
?>