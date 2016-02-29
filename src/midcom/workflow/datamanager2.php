<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use midcom_core_context;
use midcom_helper_toolbar;
use midcom_helper_datamanager2_controller;
use midcom;

/**
 * @package midcom.workflow
 */
class datamanager2 extends dialog
{
    /**
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $controller;

    /**
     *
     * @var callable
     */
    private $save_callback;

    public function __construct(midcom_helper_datamanager2_controller $controller = null, $save_callback = null)
    {
        $this->controller = $controller;
        $this->save_callback = $save_callback;
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
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_l10n('midcom')->get('edit'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            MIDCOM_TOOLBAR_OPTIONS => array
            (
                'data-dialog' => 'datamanager',
                'data-dialog-cancel-label' => midcom::get()->i18n->get_l10n('midcom')->get('cancel')
            )
        );
    }

    public function run()
    {
        $context = midcom_core_context::get();
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
                    $url = call_user_func($this->save_callback, $this->controller);
                    if ($url !== null)
                    {
                        $url = $this->prepare_url($url);
                    }
                }
                midcom::get()->head->add_jscript('refresh_opener(' . $url . ');');
            }
            else
            {
                midcom::get()->head->add_jscript('close();');
            }
            $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, array(midcom::get(), 'finish'));
        }
        return new \midcom_response_styled($context, 'POPUP');
    }

    public function add_dialog_button(dialog $dialog, $url)
    {
        $config = $dialog->get_button_config();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.js');
        midcom::get()->head->add_jscript('add_dialog_button(' . $this->prepare_url($url) . ', "' . $config[MIDCOM_TOOLBAR_LABEL] . '", ' . json_encode($config[MIDCOM_TOOLBAR_OPTIONS]) . ');');
    }

    private function prepare_url($url)
    {
        if (   substr($url, 0, 1) != '/'
            && ! preg_match('|^https?://|', $url))
        {
            $url = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $url;
        }
        return '"' . $url . '"';
    }
}
