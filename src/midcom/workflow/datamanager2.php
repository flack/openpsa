<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom_core_context;
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
    protected $controller;

    /**
     *
     * @var callable
     */
    protected $save_callback;

    /**
     * {@inheritdoc}
     */
    public function configure(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'controller' => null,
                'save_callback' => null
            ))
            ->setAllowedTypes('controller', array('null', 'midcom_helper_datamanager2_controller'));
    }

    public function get_button_config()
    {
        return array(
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_l10n('midcom')->get('edit'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            MIDCOM_TOOLBAR_OPTIONS => array(
                'data-dialog' => 'dialog',
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

        if ($this->state == 'save') {
            $url = '';
            if (is_callable($this->save_callback)) {
                $url = call_user_func($this->save_callback, $this->controller);
                if ($url !== null) {
                    $url = $this->prepare_url($url);
                }
            }
            midcom::get()->head->add_jscript('refresh_opener(' . $url . ');');
            $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, array(midcom::get(), 'finish'));
        } else {
            $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, array($this->controller, 'display_form'));
        }
        return new \midcom_response_styled($context, 'POPUP');
    }

    public function add_post_button($url, $label, array $args)
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.workflow/dialog.js');
        midcom::get()->head->add_jscript('add_post_button(' . $this->prepare_url($url) . ', "' . $label . '", ' . json_encode($args) . ');');
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
            && ! preg_match('|^https?://|', $url)) {
            $url = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $url;
        }
        return '"' . $url . '"';
    }
}
