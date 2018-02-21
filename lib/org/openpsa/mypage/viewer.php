<?php
/**
 * @package org.openpsa.mypage
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.mypage site interface class.
 *
 * Personal summary page into OpenPSA
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
        if ($handler_id == 'workingon_set') {
            midcom::get()->auth->require_valid_user('basic');
        } else {
            midcom::get()->auth->require_valid_user();
            org_openpsa_widgets_contact::add_head_elements();
        }
    }
}
