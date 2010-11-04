<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: view.php 26684 2010-10-08 10:55:41Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_handler_document_view extends midcom_baseclasses_components_handler
{

    /**
     * The document we're working with (if any).
     *
     * @var org_openpsa_documents_documen_dba
     * @access private
     */
    private $_document = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    private $_schemadb = null;

    var $_datamanager = null;

    function __construct()
    {
        parent::__construct();
    }

    function _on_initialize()
    {
        $_MIDCOM->auth->require_valid_user();
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
    }

    private function _load_document($guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);

        // if the document doesn't belong to the current topic, we don't
        // show it, because otherwise folder-based permissions would be useless
        if (   !is_object($document)
            || $document->topic != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The document '{$guid}' could not be found in this folder.");
        }
        
        // Load the document to datamanager
        if (!$this->_datamanager->autoset_storage($document))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Failed to initialize the datamanager, see debug level log for more information.', MIDCOM_LOG_ERROR);
            debug_print_r('Object to be used was:', $document);
            debug_pop();
            return false;
        }

        return $document;
    }

    /**
     * Displays older versions of the document
     * 
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_versions($handler_id, $args, &$data)
    {
        $this->_document = $this->_load_document($args[0]);
        
        // Get list of older versions
        $qb = org_openpsa_documents_document_dba::new_query_builder();
        $qb->add_constraint('nextVersion', '=', $this->_document->id);
        $qb->add_constraint('topic', '=', $data['directory']->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_DOCUMENT);
        $qb->add_order('metadata.created', 'DESC');

        $data['documents'] = $qb->execute();

        return true;
    }
    
    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_versions($handler_id, &$data)
    {
        if (sizeof($data['documents']) == 0)
        {
            return;
        }
        
        midcom_show_style('show-document-grid');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_view($handler_id, $args, &$data)
    {
        // Get the requested document object
        $this->_document = $this->_load_document($args[0]);

        //If the user hasn't looked at the document since its last update, save the current time as last visit
        $person = $_MIDCOM->auth->user->get_storage();
        if ((int) $person->get_parameter('org.openpsa.documents_visited', $this->_document->guid) < (int) $this->_document->metadata->revised)
        {
            $person->set_parameter('org.openpsa.documents_visited', $this->_document->guid, time());
        }

        // Add toolbar items
        if ( $_MIDCOM->auth->can_do('midgard:update', $this->_document))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "document/edit/{$this->_document->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_HELPTEXT => '',
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }
        if ( $_MIDCOM->auth->can_do('midgard:delete', $this->_document))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "document/delete/{$this->_document->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_HELPTEXT => '',
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }

        // Get number of older versions
        $this->_request_data['document_versions'] = 0;
        $qb = org_openpsa_documents_document_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_request_data['directory']->id);
        $qb->add_constraint('nextVersion', '=', $this->_document->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_DOCUMENT);
        $this->_request_data['document_versions'] = $qb->count();

        $GLOBALS['midcom_component_data']['org.openpsa.documents']['active_leaf'] = $this->_document->id;

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css",
            )
        );

        org_openpsa_core_ui::enable_ui_tab();

        $_MIDCOM->componentloader->load('org.openpsa.contactwidget');

        $this->_request_data['document_dm'] =& $this->_datamanager;
        $this->_request_data['document'] =& $this->_document;

        $_MIDCOM->set_pagetitle($this->_document->title);

        $_MIDCOM->bind_view_to_object($this->_document, $this->_datamanager->schema->name);

        $this->_update_breadcrumb_line();

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_view($handler_id, &$data)
    {
        midcom_show_style("show-document");
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param mixed $action The current action
     */
    private function _update_breadcrumb_line($action = false)
    {
        $tmp = Array();

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "document/{$this->_document->guid}/",
            MIDCOM_NAV_NAME => $this->_document->title,
        );

        if ($action)
        {
            $tmp[] = Array
            (
                MIDCOM_NAV_URL => "",
                MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get($action . ' %s'), $this->_l10n->get('document')),
            );
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

}
?>