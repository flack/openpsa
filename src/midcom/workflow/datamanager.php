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
use midcom;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use midcom\datamanager\storage\container\dbacontainer;

/**
 * @package midcom.workflow
 */
class datamanager extends dialog
{
    /**
     * @var controller
     */
    protected $controller;

    /**
     * @var callable
     */
    protected $save_callback;

    /**
     * Disable relocate after execute
     *
     * Returns the uimessage as JSON instead
     */
    protected bool $relocate;

    protected ?string $style = null;

    /**
     * {@inheritdoc}
     */
    public function configure(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'controller' => null,
                'save_callback' => null,
                'relocate' => true,
                'style' => null
            ])
            ->setAllowedTypes('controller', ['null', controller::class]);
    }

    public function get_button_config() : array
    {
        return [
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit', 'midcom'),
            MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
            MIDCOM_TOOLBAR_OPTIONS => [
                'data-dialog' => 'dialog',
                'data-dialog-cancel-label' => midcom::get()->i18n->get_string('cancel', 'midcom')
            ]
        ];
    }

    public function run(Request $request) : Response
    {
        $this->state = $this->controller->handle($request);

        if ($this->state == controller::SAVE) {
            $script = $this->handle_save();
            return $this->js_response($script);
        }
        $context = midcom_core_context::get();
        if ($style = $this->style) {
            $callback = function() use ($style) {
                midcom::get()->style->show($style);
            };
        } else {
            $storage = $this->controller->get_datamanager()->get_storage();
            if ($storage instanceof dbacontainer) {
                midcom::get()->head->add_jscript('var DM_OBJECT_GUID = "' . $storage->get_value()->guid . '";');
            }
            $callback = [$this->controller, 'display_form'];
        }
        $context->set_key(MIDCOM_CONTEXT_SHOWCALLBACK, $callback);
        return $this->response($context);
    }

    protected function handle_save() : string
    {
        $dm = $this->controller->get_datamanager();
        $object = $dm->get_storage()->get_value();
        if ($object instanceof \midcom_core_dbaobject) {
            // we rebuild the form so that newly created child objects are listed with their proper DB identifiers
            $dm->set_storage($object);
            $data = $dm->get_content_html();
            $data['guid'] = $object->guid;
        } else {
            $data = $dm->get_content_html();
        }
        if ($this->relocate) {
            $url = null;
            if (is_callable($this->save_callback)) {
                $url = call_user_func($this->save_callback, $this->controller);
            }

            return 'refresh_opener(' . $this->prepare_url($url) . ', ' . json_encode($data) . ');';
        }
        return 'close(' . json_encode($data) . ');';
    }

    public function add_post_button(string $url, string $label, array $args)
    {
        $this->add_dialog_js();
        midcom::get()->head->add_jscript('add_post_button(' . $this->prepare_url($url) . ', "' . $label . '", ' . json_encode($args) . ');');
    }

    public function add_dialog_button(dialog $dialog, string $url)
    {
        $config = $dialog->get_button_config();
        $this->add_dialog_js();
        midcom::get()->head->add_jscript('add_dialog_button(' . $this->prepare_url($url) . ', "' . $config[MIDCOM_TOOLBAR_LABEL] . '", ' . json_encode($config[MIDCOM_TOOLBAR_OPTIONS]) . ');');
    }

    private function prepare_url(?string $url) : string
    {
        if ($url === null) {
            return 'undefined';
        }

        if (   !str_starts_with($url, '/')
            && ! preg_match('|^https?://|', $url)) {
            $url = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $url;
        }
        return '"' . $url . '"';
    }
}
