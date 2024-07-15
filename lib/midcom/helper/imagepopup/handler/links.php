<?php
/**
 * @package midcom.helper.imagepopup
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_handler_links extends midcom_baseclasses_components_handler
{
    public function _handler_open(array $args)
    {
        $url = '__ais/imagepopup/';
        $filetype = $args[0];
        if ($filetype === 'file') {
            $url .= 'links/';
        } elseif (empty($args[1])) {
            $url .= 'folder/';
        }
        $url .= implode('/', $args) . '/';

        return new midcom_response_relocate($url);
    }

    public function _handler_links(string $filetype, array &$data)
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->require_valid_user();
        midcom::get()->skip_page_style = true;

        $data['nav'] = new fi_protie_navigation;
        $data['nav']->follow_all = true;
        $data['list_type'] = 'links';
        $data['filetype'] = $filetype;

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/styling.css");
        org_openpsa_widgets_tree::add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/functions.js");

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.helper.imagepopup');
    }

    public function _show_links(string $handler_id, array &$data)
    {
        $data['navlinks'] = midcom_helper_imagepopup_viewer::get_navigation($data);
        midcom_show_style('midcom_helper_imagepopup_init');
        midcom_show_style('midcom_helper_imagepopup_links');
        midcom_show_style('midcom_helper_imagepopup_finish');
    }
}
