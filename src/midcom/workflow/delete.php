<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use midcom_helper_toolbar;
use midcom;

/**
 * Helper class for manipulating toolbars
 *
 * @package midcom.workflow
 */
class delete extends base
{
    private $form_identifier = 'confirm-delete';

    public $method = 'delete';

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

    public function add_button(midcom_helper_toolbar $toolbar, $url)
    {
        self::add_head_elements();

        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $url,
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
            )
        );
    }

    public function is_active()
    {
        return !empty($_POST[$this->form_identifier]);
    }

    public function run()
    {
        if (!$this->is_active())
        {
            return false;
        }
        $this->object->require_do('midgard:delete');
        $stat = $this->object->{$this->method}();
        $uim = midcom::get()->uimessages;
        $title = $this->get_object_title();
        if ($stat)
        {
            $uim->add($this->l10n_midcom->get('midcom'), sprintf($this->l10n_midcom->get("%s deleted"), $title));
        }
        else
        {
            $uim->add($this->l10n_midcom->get('midcom'), sprintf($this->l10n_midcom->get("failed to delete %s: %s"), $title, midcom_connection::get_error_string()), 'error');
        }
        return $stat;
    }
}
