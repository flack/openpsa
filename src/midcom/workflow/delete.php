<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

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

    private $form_identifier = 'confirm-delete';

    /**
     * The method to call for deletion (delete or delete_tree)
     *
     * @var string
     */
    public $method = 'delete';

    /**
     * The URL to redirect to after successful deletion
     *
     * Defaults to topic start page
     *
     * @var string
     */
    public $success_url = '';

    /**
     *
     * @var \midcom_core_dbaobject
     */
    private $object;

    /**
     * @var string
     */
    private $object_title;

    /**
     *
     * @var \midcom_services_i18n_l10n
     */
    private $l10n_midcom;

    /**
     *
     * @param \midcom_core_dbaobject $object
     */
    public function __construct(midcom_core_dbaobject $object)
    {
        $this->object = $object;
        $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
        $this->label = midcom_helper_reflector::get_object_title($this->object);
        if (!empty($_POST[$this->form_identifier]))
        {
            $this->state = static::CONFIRMED;
        }
    }

    public function get_object_title()
    {
        if ($this->object_title === null)
        {
            $this->object_title = midcom_helper_reflector::get_object_title($this->object);
        }
        return $this->object_title;
    }

    public function set_object_title($title)
    {
        $this->object_title = $title;
    }

    /**
     *
     * @return array
     */
    public function get_button_config()
    {
        return array
        (
            MIDCOM_TOOLBAR_LABEL => $this->l10n_midcom->get('delete'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
            MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            MIDCOM_TOOLBAR_OPTIONS => array
            (
                'data-dialog' => 'delete',
                'data-form-id' => $this->form_identifier,
                'data-dialog-heading' => $this->l10n_midcom->get('confirm delete'),
                'data-dialog-text' => sprintf($this->l10n_midcom->get('delete %s'), $this->get_object_title()),
                'data-dialog-cancel-label' => $this->l10n_midcom->get('cancel')
            )
        );
    }

    public function run()
    {
        $failure_url = (!empty($_POST['referrer'])) ? $_POST['referrer'] : $this->success_url;
        if ($this->get_state() === static::CONFIRMED)
        {
            $this->object->require_do('midgard:delete');
            $uim = midcom::get()->uimessages;
            $title = $this->get_object_title();
            if ($this->object->{$this->method}())
            {
                $this->state = static::SUCCESS;
                $uim->add($this->l10n_midcom->get('midcom'), sprintf($this->l10n_midcom->get("%s deleted"), $title));
                midcom::get()->indexer->delete($this->object->guid);
                return new midcom_response_relocate($this->success_url);
            }
            $this->state = static::FAILURE;
            $uim->add($this->l10n_midcom->get('midcom'), sprintf($this->l10n_midcom->get("failed to delete %s: %s"), $title, midcom_connection::get_error_string()), 'error');
        }
        return new midcom_response_relocate($failure_url);
    }
}
