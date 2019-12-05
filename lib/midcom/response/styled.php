<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper for style(engine)d responses
 *
 * @package midcom
 */
class midcom_response_styled extends Response
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
        parent::__construct();
        $this->set_context($context);
        $this->root_element = $root_element;
        $this->content = $this->render();
    }

    private function render() : string
    {
        midcom::get()->style->enter_context($this->context);
        ob_start();
        if (midcom::get()->skip_page_style) {
            $this->context->show();
        } else {
            if ($this->context->id == 0) {
                // Let metadata service add its meta tags
                midcom::get()->metadata->populate_meta_head();
            }
            midcom_show_style($this->root_element);
        }

        midcom::get()->style->leave_context();
        return ob_get_clean();
    }

    private function set_context(midcom_core_context $context)
    {
        if (   $context->get_key(MIDCOM_CONTEXT_PERMALINKGUID) === null
            || $context->get_key(MIDCOM_CONTEXT_PAGETITLE) == '') {
            // Retrieve Metadata
            $nav = new midcom_helper_nav();
            if ($nav->get_current_leaf() === false) {
                $meta = $nav->get_node($nav->get_current_node());
            } else {
                $meta = $nav->get_leaf($nav->get_current_leaf());
            }

            if ($meta) {
                if ($context->get_key(MIDCOM_CONTEXT_PERMALINKGUID) === null) {
                    $context->set_key(MIDCOM_CONTEXT_PERMALINKGUID, $meta[MIDCOM_NAV_GUID]);
                }
                if ($context->get_key(MIDCOM_CONTEXT_PAGETITLE) == '') {
                    $context->set_key(MIDCOM_CONTEXT_PAGETITLE, $meta[MIDCOM_NAV_NAME]);
                }
            }
        }
        $this->context = $context;
    }
}
