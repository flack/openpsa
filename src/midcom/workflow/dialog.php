<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

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

    public static function add_head_elements()
    {
        $head = midcom::get()->head;
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/mouse.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/draggable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/position.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/button.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/dialog.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/workflow.js');
        $head->add_jquery_ui_theme(array('dialog'));
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
     * @return arrabutton config in midcom_helper_toolbar format
     */
    public function get_button($url, array $options = array())
    {
        static::add_head_elements();

        $button_config = $this->get_button_config();
        $button_config[MIDCOM_TOOLBAR_URL] = $url;
        //The constants are numeric, so array_merge won't work...
        foreach ($options as $key => $value)
        {
            if (   is_array($value)
                && !empty($button_config[$key]))
            {
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
        $output = '';
        foreach ($button_config[MIDCOM_TOOLBAR_OPTIONS] as $key => $val)
        {
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