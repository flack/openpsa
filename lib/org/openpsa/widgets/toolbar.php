<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for manipulating toolbars
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_toolbar
{
    /**
     * The toolbar to work on
     *
     * @var midcom_helper_toolbar
     */
    private $toolbar;

    public function __construct(midcom_helper_toolbar $toolbar)
    {
        $this->toolbar = $toolbar;
    }

    public static function add_head_elements()
    {
        $head = midcom::get()->head;
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.position.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.button.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.dialog.min.js');
        $head->add_jquery_ui_theme(array('dialog'));
    }

    public function add_delete_button($url, $title)
    {
        self::add_head_elements();
        $l10n_midcom = midcom::get('i18n')->get_l10n('midcom');
        $l10n_oocore = midcom::get('i18n')->get_l10n('org.openpsa.core');
        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        $form_id = '_qf__' . $controller->formmanager->form->getAttribute('id');

        $this->toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $url,
                MIDCOM_TOOLBAR_LABEL => $l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                MIDCOM_TOOLBAR_OPTIONS => array
                (
                    'data-dialog' => 'delete',
                    'data-form-id' => $form_id,
                    'data-dialog-heading' => $l10n_oocore->get('confirm delete'),
                    'data-dialog-text' => sprintf($l10n_midcom->get('delete %s'), $title),
                    'data-dialog-cancel-label' => $l10n_midcom->get('cancel')
                )
            )
        );
    }
}
?>