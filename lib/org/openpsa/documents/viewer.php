<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: viewer.php 26515 2010-07-07 08:41:51Z gudd $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents site interface class.
 *
 * Document management and WebDAV file share
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_viewer extends midcom_baseclasses_components_request
{
    /**
     * Constructor.
     */
    public function _on_initialize()
    {
        // Match /document/create/choosefolder
        $this->_request_switch['document-create-choosefolder'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_create', 'create'),
            'fixed_args' => array('document', 'create', 'choosefolder'),
        );

        // Match /document/create
        $this->_request_switch['document-create'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_create', 'create'),
            'fixed_args' => array('document', 'create'),
        );

        // Match /document/delete/<document GUID>
        $this->_request_switch['document-delete'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_admin', 'delete'),
            'fixed_args' => array('document', 'delete'),
            'variable_args' => 1,
        );
        // Match /document/edit/<document GUID>
        $this->_request_switch['document-edit'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_admin', 'edit'),
            'fixed_args' => array('document', 'edit'),
            'variable_args' => 1,
        );
        // Match /document/versions/<document GUID>
        $this->_request_switch['document-versions'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_view', 'versions'),
            'fixed_args' => array('document', 'versions'),
            'variable_args' => 1,
        );
        // Match /directory/navigation

        $this->_request_switch['navigation-show'] = array
        (
            'handler' => array('org_openpsa_documents_handler_directory_navigation', 'navigation'),
            'fixed_args' => array('directory', 'navigation'),
        );
        // Match /document/<document GUID>
        $this->_request_switch['document-view'] = array
        (
            'handler' => array('org_openpsa_documents_handler_document_view', 'view'),
            'fixed_args' => 'document',
            'variable_args' => 1,
        );
        // Match /directory/<mode>/<directory GUID>
        $this->_request_switch['directory-single-view'] = array
        (
            'handler' => array('org_openpsa_documents_handler_directory_view', 'view'),
            'fixed_args' => 'directory',
            'variable_args' => 2,
        );

        // Match /edit
        $this->_request_switch['directory-edit'] = array
        (
            'handler' => array('org_openpsa_documents_handler_directory_edit', 'edit'),
            'fixed_args' => 'edit',
        );

        // Match /create
        $this->_request_switch['directory-create'] = array
        (
            'handler' => array('org_openpsa_documents_handler_directory_create', 'create'),
            'fixed_args' => 'create',
        );

        // Match /search
        $this->_request_switch['search'] = array
        (
            'handler' => array('org_openpsa_documents_handler_search', 'search'),
            'fixed_args' => 'search',
        );

        // Match /
        $this->_request_switch['directory-view'] = array
        (
            'handler' => array('org_openpsa_documents_handler_directory_view', 'view'),
        );
    }

    public function _on_handle($handler, $args)
    {
        // Pass topic to handlers
        $this->_request_data['directory'] = new org_openpsa_documents_directory($this->_topic->id);
        $this->_request_data['enable_versioning'] = $this->_config->get('enable_versioning');

        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        org_openpsa_core_ui::enable_jqgrid();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.cookie.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.documents/dynatree_0.4/jquery.dynatree.min.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL."/org.openpsa.documents/dynatree_0.4/skin/ui.dynatree.css");

        return parent::_on_handle($handler, $args);
    }
}
?>