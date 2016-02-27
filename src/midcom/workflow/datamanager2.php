<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use midcom_helper_toolbar;
use midcom_helper_datamanager2_controller;
use midcom;

/**
 * @package midcom.workflow
 */
class datamanager2 extends dialog
{
    private $mode;

    /**
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $controller;

    /**
     *
     * @var callable
     */
    public $save_callback;

    public function __construct($mode, midcom_helper_datamanager2_controller $controller = null)
    {
        $this->mode = $mode;
        $this->controller = $controller;
    }

    public static function add_head_elements()
    {
        parent::add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/resizable.min.js');
    }

    public function get_button_config()
    {
        return array
        (
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_l10n('midcom')->get($this->mode),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new.png',
            MIDCOM_TOOLBAR_OPTIONS => array('data-dialog' => 'datamanager')
        );
    }

    public function run()
    {
        $context = \midcom_core_context::get();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.js');
        midcom::get()->style->append_styledir(__DIR__ . '/style');
        $this->state = $this->controller->process_form();

        if ($this->state == 'edit')
        {
            $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, array($this->controller, 'display_form'));
        }
        else
        {
            if ($this->state == 'save')
            {
                $url = '';
                if (is_callable($this->save_callback))
                {
                    $url = (string) call_user_func($this->save_callback, $this->controller);
                }
                if (   substr($url, 0, 1) != '/'
                    && ! preg_match('|^https?://|', $url))
                {
                    $url = $context->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $url;
                }
                midcom::get()->head->add_jscript('refresh_opener("' . $url . '");');
            }
            else
            {
                midcom::get()->head->add_jscript('close();');
            }
            $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, array(midcom::get(), 'finish'));
        }
        return new \midcom_response_styled($context, 'popup');
    }
}
