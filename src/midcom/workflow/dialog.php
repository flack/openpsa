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

    /**
     * @return string
     */
    public function get_state()
    {
        return $this->state;
    }

    /**
     *
     * @param string $url
     * @param array $options
     * @return array button config in midcom_helper_toolbar format
     */
    public function get_button($url, array $options = [])
    {
        static::add_head_elements();

        $button_config = $this->get_button_config();
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

    /**
     *
     * @return string
     */
    public function render_attributes()
    {
        $button_config = $this->get_button_config();

        $output = ' title="' . htmlspecialchars($button_config[MIDCOM_TOOLBAR_LABEL]) . '"';

        foreach ($button_config[MIDCOM_TOOLBAR_OPTIONS] as $key => $val) {
            $output .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }

        return $output;
    }

    /**
     * @return array
     */
    abstract public function get_button_config();

    /**
     * @return \midcom_response
     */
    abstract public function run();
}
