<?php
/**
 * @package org.openpsa.documents
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents elfinder controller.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_finder extends midcom_baseclasses_components_handler
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
        $prefix = '/' . $this->_component . '/elFinder-2.1.23/';
        org_openpsa_widgets_contact::add_head_elements();

        $head = midcom::get()->head;
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/widgets/draggable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/widgets/droppable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/widgets/selectable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL. '/widgets/tabs.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . 'js/elfinder.min.js');

        $lang = midcom::get()->i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "js/i18n/elfinder.{$lang}.js")) {
            $lang = midcom::get()->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "js/i18n/elfinder.{$lang}.js")) {
                $lang = 'en';
            }
        }
        $data['lang'] = $lang;
        $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . "js/i18n/elfinder.{$lang}.js");

        $head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/elfinder.custom.js');

        $head->add_stylesheet(MIDCOM_STATIC_URL . $prefix . 'css/elfinder.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . $prefix . 'css/theme.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/layout.css');

        $this->_populate_toolbar();
    }

    /**
     * Add toolbar items
     */
    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager2');
        $buttons = array();
        if ($this->_request_data['directory']->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button("document/create/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new document'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
            ));
            $buttons[] = $workflow->get_button("create/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new directory'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
            ));
        }
        if ($this->_request_data['directory']->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("edit/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit directory'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ));
        }
        if ($this->_request_data['directory']->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', array('object' => $this->_request_data['directory'], 'recursive' => true));
            $buttons[] = $workflow->get_button("__ais/folder/delete/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete directory'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            ));
        }
        $this->_view_toolbar->add_items($buttons);
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

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_connector($handler_id, array $args, array &$data)
    {
        $options = array(
            'roots' => array(
                array(
                    'driver' => 'Openpsa',
                    'path' => $this->_topic->guid
                )
            )
        );

        $connector = new elFinderConnector(new elFinder($options));
        $connector->run();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_goto($handler_id, array $args, array &$data)
    {
        $parts = explode('_', $args[0]);
        $guid = base64_decode($parts[1]);
        $url = midcom::get()->permalinks->resolve_permalink($guid);
        if (!$url) {
            throw new midcom_error_notfound('Could not resolve URL for ' . $guid);
        }
        return new midcom_response_relocate($url);
    }
}
