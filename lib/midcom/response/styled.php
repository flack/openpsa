<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wrapper for style(engine)d responses
 *
 * @package midcom
 */
class midcom_response_styled extends midcom_response
{
    /**
     *
     * @var string
     */
    private $root_element;

    /**
     *
     * @var midcom_core_context
     */
    private $context;

    public function __construct(midcom_core_context $context, $root_element = 'ROOT')
    {
        $this->context = $context;
        $this->root_element = $root_element;
    }

    public function send()
    {
        // Retrieve Metadata
        $nav = new midcom_helper_nav();
        if ($nav->get_current_leaf() === false) {
            $meta = $nav->get_node($nav->get_current_node());
        } else {
            $meta = $nav->get_leaf($nav->get_current_leaf());
        }

        if ($this->context->get_key(MIDCOM_CONTEXT_PERMALINKGUID) === null) {
            $this->context->set_key(MIDCOM_CONTEXT_PERMALINKGUID, $meta[MIDCOM_NAV_GUID]);
        }

        if ($this->context->get_key(MIDCOM_CONTEXT_PAGETITLE) == '') {
            $this->context->set_key(MIDCOM_CONTEXT_PAGETITLE, $meta[MIDCOM_NAV_NAME]);
        }

        if (   $this->context->id == 0
            && !midcom::get()->skip_page_style) {
            // Let metadata service add its meta tags
            midcom::get()->metadata->populate_meta_head();
        }

        midcom::get()->style->enter_context($this->context->id);

        ob_start();
        if (midcom::get()->skip_page_style) {
            $this->context->show();
        } else {
            midcom_show_style($this->root_element);
        }
        ob_end_flush();

        midcom::get()->style->leave_context();
    }
}
