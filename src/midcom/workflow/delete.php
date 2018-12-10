<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom_helper_reflector;
use midcom_core_dbaobject;
use midcom_response_relocate;
use midcom_connection;
use midcom;

/**
 * Helper class for manipulating toolbars
 *
 * @package midcom.workflow
 */
class delete extends dialog
{
    const CONFIRMED = 'confirmed';

    const SUCCESS = 'success';

    const FAILURE = 'failure';

    /**
     * The method to call for deletion (delete or delete_tree)
     *
     * @var boolean
     */
    protected $recursive;

    /**
     * The URL to redirect to after successful deletion
     *
     * Defaults to topic start page
     *
     * @var string
     */
    protected $success_url;

    /**
     *
     * @var \midcom_core_dbaobject
     */
    protected $object;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $dialog_text;

    private $form_identifier = 'confirm-delete';

    /**
     *
     * @var \midcom_services_i18n_l10n
     */
    private $l10n_midcom;

    /**
     * {@inheritdoc}
     */
    public function configure(OptionsResolver $resolver)
    {
        $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
        if (!empty($_POST[$this->form_identifier])) {
            $this->state = static::CONFIRMED;
        }

        $resolver
            ->setDefaults([
                'recursive' => false,
                'success_url' => '',
                'label' => null,
                'object' => null,
                'dialog_text' => null
            ])
            ->setRequired('object')
            ->setAllowedTypes('object', midcom_core_dbaobject::class)
            ->setNormalizer('label', function ($options, $value) {
                if ($value === null) {
                    return midcom_helper_reflector::get_object_title($options['object']);
                }
                return $value;
            });
    }

    /**
     *
     * @return array
     */
    public function get_button_config()
    {
        $dialog_text = $this->dialog_text ?: '<p>' . sprintf($this->l10n_midcom->get('delete %s'), $this->label) . '</p>';
        if ($this->recursive) {
            $dialog_text .= '<p class="warning">' . $this->l10n_midcom->get('all descendants will be deleted') . ':</p>';
            $dialog_text .= '<div id="delete-child-list"></div>';
        }

        return [
            MIDCOM_TOOLBAR_LABEL => $this->l10n_midcom->get('delete'),
            MIDCOM_TOOLBAR_GLYPHICON => 'trash',
            MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            MIDCOM_TOOLBAR_OPTIONS => [
                'data-dialog' => 'delete',
                'data-form-id' => $this->form_identifier,
                'data-dialog-heading' => $this->l10n_midcom->get('confirm delete'),
                'data-dialog-text' => $dialog_text,
                'data-dialog-cancel-label' => $this->l10n_midcom->get('cancel'),
                'data-recursive' => $this->recursive ? 'true' : 'false',
                'data-guid' => $this->object->guid,
            ]
        ];
    }

    public function run()
    {
        $this->object->require_do('midgard:delete');
        $url = (!empty($_POST['referrer'])) ? $_POST['referrer'] : $this->success_url;
        if ($this->get_state() === static::CONFIRMED) {
            $method = $this->recursive ? 'delete_tree' : 'delete';
            $message = ['title' => $this->l10n_midcom->get('midcom'), 'type' => 'info'];
            if ($this->object->{$method}()) {
                $this->state = static::SUCCESS;
                $url = $this->success_url;
                $message['body'] = sprintf($this->l10n_midcom->get("%s deleted"), $this->label);
            } else {
                $this->state = static::FAILURE;
                $message['body'] = sprintf($this->l10n_midcom->get("failed to delete %s: %s"), $this->label, midcom_connection::get_error_string());
                $message['type'] = 'error';
            }
            midcom::get()->uimessages->add($message['title'], $message['body'], $message['type']);
        }
        return new midcom_response_relocate($url);
    }
}
