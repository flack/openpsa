<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Sales action handler ***creates pdf-file***
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_sales_action extends midcom_baseclasses_components_handler
{
    private $salesproject;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        if (empty($_POST['id'])) {
            throw new midcom_error('Incomplete POST data');
        }
        $this->salesproject = new org_openpsa_sales_salesproject_dba((int) $_POST['id']);
        $this->salesproject->require_do('midgard:update');
    }

    private function reply($success, $message)
    {
        $message = array(
            'title' => $this->_l10n->get($this->_component),
            'type' => $success ? 'info' : 'error',
            'message' => $message
        );

        midcom::get()->uimessages->add($message['title'], $message['message'], $message['type']);
        return new midcom_response_relocate('salesproject/' . $this->salesproject->guid . '/');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create_pdf($handler_id, array $args, array &$data)
    {
        $pdf_helper = new org_openpsa_sales_sales_pdf($this->salesproject);
        try {
            $pdf_helper->render_and_attach();
            return $this->reply(true, $this->_l10n->get('pdf created'));
        } catch (midcom_error $e) {
            return $this->reply(false, $this->_l10n->get('pdf creation failed') . ': ' . $e->getMessage());
        }
    }
}
