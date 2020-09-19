<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom;
use midcom_response_styled;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Workflow base class
 *
 * @package midcom.workflow
 */
abstract class dialog
{
    const INACTIVE = 'inactive';

    /**
     * @var string
     */
    protected $state = self::INACTIVE;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configure($resolver);

        foreach ($resolver->resolve($options) as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     *
     * @param OptionsResolver $resolver
     */
    public function configure(OptionsResolver $resolver)
    {
    }

    public static function add_head_elements()
    {
        $head = midcom::get()->head;
        $head->enable_jquery_ui(['mouse', 'draggable', 'resizable', 'button', 'dialog']);
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/workflow.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.workflow/workflow.css');
    }

    protected function add_dialog_js()
    {
        midcom::get()->head->enable_jquery_ui(['button']);
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.js');
    }

    protected function response(\midcom_core_context $context) : midcom_response_styled
    {
        $this->add_dialog_js();
        midcom::get()->style->append_styledir(__DIR__ . '/style');
        return new midcom_response_styled($context, 'POPUP');
    }

    public function get_state() : string
    {
        return $this->state;
    }

    /**
     * @return array button config in midcom_helper_toolbar format
     */
    public function get_button(string $url, array $options = []) : array
    {
        static::add_head_elements();
        $button_config = $this->get_button_config();
        if (!empty($options[MIDCOM_TOOLBAR_ICON])) {
            unset($button_config[MIDCOM_TOOLBAR_GLYPHICON]);
        }
        $button_config[MIDCOM_TOOLBAR_URL] = $url;
        //The constants are numeric, so array_merge won't work...
        foreach ($options as $key => $value) {
            if (   is_array($value)
                && !empty($button_config[$key])) {
                $value = array_merge($button_config[$key], $value);
            }
            $button_config[$key] = $value;
        }
        return $button_config;
    }

    public function render_attributes() : string
    {
        $button_config = $this->get_button_config();

        $output = ' title="' . htmlspecialchars($button_config[MIDCOM_TOOLBAR_LABEL]) . '"';

        foreach ($button_config[MIDCOM_TOOLBAR_OPTIONS] as $key => $val) {
            $output .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }

        return $output;
    }

    public function js_response(string $script) : Response
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.js');
        midcom::get()->head->add_jscript($script);
        midcom::get()->dispatcher->addListener(KernelEvents::RESPONSE, [midcom::get()->head, 'inject_head_elements']);
        $content = '<!DOCTYPE html><html><head>' . \midcom_helper_head::HEAD_PLACEHOLDER . '</head><body></body></html>';
        return new Response($content);
    }

    abstract public function get_button_config() : array;

    abstract public function run(Request $request) : Response;
}
