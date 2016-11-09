<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Shell interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_shell extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_shell'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_shell($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $controller = $this->get_controller('nullstorage');

        switch ($controller->process_form())
        {
            case 'save':
                $data['code'] = $controller->formmanager->get_value('code');
                break;

            case 'edit':
                if (   $controller->formmanager->form->isSubmitted()
                    && !empty($controller->datamanager->validation_errors))
                {
                    foreach ($controller->datamanager->validation_errors as $field => $error)
                    {
                        $element =& $controller->formmanager->form->getElement($field);
                        $message = sprintf($this->_l10n->get('validation error in field %s: %s'), $element->getLabel(), $error);
                        midcom::get()->uimessages->add
                            (
                                $this->_l10n->get('midgard.admin.asgard'),
                                $message,
                                'error'
                            );
                    }
                }
        }

        $data['controller'] = $controller;
        $data['view_title'] = $this->_l10n->get('shell');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/shell.js');

        $data['asgard_toolbar'] = $this->_prepare_toolbar();

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb("__mfa/asgard/shell/", $data['view_title']);
        return new midgard_admin_asgard_response($this, '_show_shell');
    }

    private function _prepare_toolbar()
    {
        $toolbar = new midgard_admin_asgard_toolbar();
        $buttons = array
        (
            array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('save in browser'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 's',
                MIDCOM_TOOLBAR_OPTIONS => array('id' => 'save-script')
            ),
            array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('restore from browser'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'r',
                MIDCOM_TOOLBAR_OPTIONS => array('id' => 'restore-script')
            ),
            array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('clear all'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'r',
                MIDCOM_TOOLBAR_OPTIONS => array('id' => 'clear-script')
            )
        );
        $toolbar->add_items($buttons);
        return $toolbar;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_shell($handler_id, array &$data)
    {
        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midgard_admin_asgard_shell');
        }
        else
        {
            midcom::get()->cache->content->enable_live_mode();
            while (@ob_end_flush());
            ob_implicit_flush(true);
            midcom_show_style('midgard_admin_asgard_shell_runner');
            ob_implicit_flush(false);
        }
    }
}
