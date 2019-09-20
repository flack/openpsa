<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Style helper
 *
 * @package midgard.admin.asgard
 */
trait midgard_admin_asgard_handler
{
    public function get_response($element = null) : midcom_response_styled
    {
        if (isset($_GET['ajax'])) {
            midcom::get()->skip_page_style = true;
        }
        $this->populate_breadcrumb_line();
        if ($element) {
            return $this->show($element, 'ASGARD_ROOT');
        }
        return new midcom_response_styled(midcom_core_context::get(), 'ASGARD_ROOT');
    }
}
