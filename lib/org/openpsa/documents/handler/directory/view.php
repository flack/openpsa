<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_directory_view extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $prefix = MIDCOM_STATIC_URL . '/' . $this->_component . '/';
        $this->add_stylesheet($prefix . 'layout.css');
        org_openpsa_widgets_contact::add_head_elements();

        $head = midcom::get()->head;
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/ui/draggable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/ui/droppable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/ui/selectable.min.js');
        $head->add_jsfile($prefix . 'elFinder-2.1.6/js/elfinder.min.js');

        $lang = midcom::get()->i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . '/' . $this->_component . "/elFinder-2.1.6/js/i18n/elfinder.{$lang}.js"))
        {
            $lang = midcom::get()->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . '/' . $this->_component . "/elFinder-2.1.6/js/i18n/elfinder.{$lang}.js"))
            {
                $lang = 'en';
            }
        }
        $data['lang'] = $lang;
        $head->add_jsfile($prefix . "elFinder-2.1.6/js/i18n/elfinder.{$lang}.js");

        $head->add_jsfile($prefix . 'elfinder.custom.js');

        $head->add_stylesheet($prefix . 'elFinder-2.1.6/css/elfinder.min.css');
        $head->add_stylesheet($prefix . 'elFinder-2.1.6/css/theme.css');

        $this->_populate_toolbar();
    }

    /**
     * Helper that adds toolbar items
     */
    private function _populate_toolbar()
    {
        if ($this->_request_data['directory']->can_do('midgard:create'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'document/create/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new document'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'create/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new directory'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                )
            );
        }
        if ($this->_request_data['directory']->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'edit/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit directory'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/move/{$this->_request_data['directory']->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('move', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
                )
            );
        }
        if ($this->_request_data['directory']->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => '__ais/folder/delete',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete directory'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }

        $this->bind_view_to_object($this->_request_data['directory']);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('show-directory');
    }
}
