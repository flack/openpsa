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

    public function _handler_view(array &$data)
    {
        $prefix = '/' . $this->_component . '/elFinder-2.1.59/';
        org_openpsa_widgets_contact::add_head_elements();

        $head = midcom::get()->head;
        $head->enable_jquery_ui(['mouse', 'controlgroup', 'draggable', 'droppable', 'selectable', 'resizable', 'slider', 'button']);
        $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . 'js/elfinder.min.js');

        $lang = $this->_i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "js/i18n/elfinder.{$lang}.js")) {
            $lang = $this->_i18n->get_fallback_language();
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

        return $this->show('show-directory');
    }

    /**
     * Add toolbar items
     */
    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->_request_data['directory']->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button($this->router->generate('document-create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new document'),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
            ]);
            $buttons[] = $workflow->get_button($this->router->generate('directory-create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new directory'),
                MIDCOM_TOOLBAR_GLYPHICON => 'folder-o',
            ]);
        }
        if ($this->_request_data['directory']->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button($this->router->generate('directory-edit'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit directory'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }
        if ($this->_request_data['directory']->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->_request_data['directory'], 'recursive' => true]);
            $buttons[] = $workflow->get_button("__ais/folder/delete/", [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete directory'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            ]);
        }
        $this->_view_toolbar->add_items($buttons);
        $this->bind_view_to_object($this->_request_data['directory']);
    }

    public function _handler_connector()
    {
        $options = [
            'roots' => [
                [
                    'driver' => 'Openpsa',
                    'path' => $this->_topic->guid
                ]
            ]
        ];

        $connector = new elFinderConnector(new elFinder($options));
        $connector->run();
    }

    public function _handler_goto(string $hash)
    {
        $parts = explode('_', $hash);
        $guid = base64_decode($parts[1]);
        $url = midcom::get()->permalinks->resolve_permalink($guid);
        if (!$url) {
            throw new midcom_error_notfound('Could not resolve URL for ' . $guid);
        }
        return new midcom_response_relocate($url);
    }
}
