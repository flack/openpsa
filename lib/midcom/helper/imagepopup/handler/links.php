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
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_open($handler_id, array $args, array &$data)
    {
        $url = '__ais/imagepopup/';
        $filetype = $args[1];
        if ($filetype === 'file')
        {
            $url .= 'links/';
        }
        else if (empty($args[2]))
        {
            $url .= 'folder/';
        }
        $url .= implode('/', $args) . '/';

        return new midcom_response_relocate($url);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_links($handler_id, array $args, array &$data)
    {
        midcom::get()->cache->content->no_cache();
        midcom::get()->auth->require_valid_user();
        midcom::get()->skip_page_style = true;

        $data['nav'] = new fi_protie_navigation;
        $data['nav']->follow_all = true;
        $data['list_type'] = 'links';
        $data['schema_name'] = $args[0];
        $data['filetype'] = $args[1];

        $head = midcom::get()->head;
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/jQuery/fancytree-2.18.0/skin-win7/ui.fancytree.min.css");

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/styling.css");
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/effect-blind.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/fancytree-2.18.0/jquery.fancytree-all.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.imagepopup/functions.js");

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.helper.imagepopup');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_links($handler_id, array &$data)
    {
        $data['navlinks'] = midcom_helper_imagepopup_viewer::get_navigation($data);
        midcom_show_style('midcom_helper_imagepopup_init');
        midcom_show_style('midcom_helper_imagepopup_links');
        midcom_show_style('midcom_helper_imagepopup_finish');
    }
}
