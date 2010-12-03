<?php
/**
 * OpenPSA contact widget for displaying a contact person as hCard
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.openpsa.contactwidget
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.contactwidget
 */
class org_openpsa_contactwidget_interface extends midcom_baseclasses_components_interface
{
    /**
     * Adds the default hCard rendering CSS rule to HTML inclusion list
     */
    public function _on_initialize()
    {
        // Make the hCards pretty
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.contactwidget/hcard.css");
        return true;
    }
}
?>