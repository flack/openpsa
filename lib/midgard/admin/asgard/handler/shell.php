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
        midcom::get('auth')->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $controller = $this->get_controller('nullstorage');

        switch ($controller->process_form())
        {
            case 'save':
                $data['code'] = $controller->formmanager->get_value('code');
                break;

            case 'cancel':
                return new midcom_response_relocate("__mfa/asgard/");

            case 'edit':

                if (   isset($_REQUEST['midcom_helper_datamanager2_save'])
                    && !empty($controller->datamanager->validation_errors))
                {
                    foreach ($controller->datamanager->validation_errors as $field => $error)
                    {
                        $element =& $controller->formmanager->form->getElement($field);
                        $message = sprintf($this->_l10n->get('validation error in field %s: %s'), $element->getLabel(), $error);
                        midcom::get('uimessages')->add
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
        midcom::get('head')->set_pagetitle($data['view_title']);

        // Set the breadcrumb data
        $this->add_breadcrumb('__mfa/asgard/', $this->_l10n->get('midgard.admin.asgard'));
        $this->add_breadcrumb("__mfa/asgard/shell/", $data['view_title']);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_shell($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_header');
        midcom_show_style('midgard_admin_asgard_middle');

        midcom_show_style('midgard_admin_asgard_shell');

        midcom_show_style('midgard_admin_asgard_footer');
    }
}
?>
