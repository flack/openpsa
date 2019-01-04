<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts site interface class.
 *
 * Contact management, address book and user manager
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * The handle callback populates the toolbars.
     */
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();

        midcom::get()->auth->require_valid_user();
        org_openpsa_widgets_contact::add_head_elements();
    }
}
