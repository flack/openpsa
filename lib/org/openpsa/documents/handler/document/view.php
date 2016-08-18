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
class org_openpsa_documents_handler_document_view extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    /**
     * The document we're working with (if any).
     *
     * @var org_openpsa_documents_documen_dba
     */
    private $_document = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    private $_datamanager = null;

    /**
     * The grid provider for document versions
     *
     * @var org_openpsa_widgets_grid_provider
     */
    private $_provider;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        $qb = org_openpsa_documents_document_dba::new_query_builder();

        if ($this->_document->nextVersion == 0)
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->id);
        }
        else
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->nextVersion);
            $qb->add_constraint('metadata.created', '<', gmstrftime('%Y-%m-%d %T', $this->_document->metadata->created));
        }
        $qb->add_constraint('topic', '=', $this->_request_data['directory']->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_documents_document_dba::OBTYPE_DOCUMENT);

        if (!is_null($field))
        {
            if ($field == 'created')
            {
                $field = 'metadata.created';
            }
            $qb->add_order($field, $direction);
        }

        return $qb;
    }

    public function get_row(midcom_core_dbaobject $document)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();

        $entry['id'] = $document->id;
        $entry['index_title'] = $document->title;

        $entry['index_filesize'] = 0;
        $entry['filesize'] = '';
        $entry['mimetype'] = '';

        $icon = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
        $alt = '';
        $att = $document->load_attachment();

        if ($att)
        {
            $icon = midcom_helper_misc::get_mime_icon($att->mimetype);
            $alt = $att->name;
            $stats = $att->stat();
            $entry['index_filesize'] = $stats[7];
            $entry['filesize'] = midcom_helper_misc::filesize_to_string($stats[7]);
            $entry['mimetype'] = org_openpsa_documents_document_dba::get_file_type($att->mimetype);
        }

        $title = '<a class="tab_escape" href="' .$prefix . 'document/' . $document->guid .'/"><img src="' . $icon . '"';
        $title .= 'alt="' . $alt . '" style="border: 0px; height: 16px; vertical-align: middle" /> ' . $document->title . '</a>';

        $entry['title'] = $title;

        $entry['created'] = strftime('%Y-%m-%d %X', $document->metadata->created);

        $entry['index_author'] = '';
        $entry['author'] = '';

        if ($document->author)
        {
            $author = org_openpsa_contacts_person_dba::get_cached($document->author);
            $entry['index_author'] = $author->rname;
            $author_card = org_openpsa_widgets_contact::get($author->guid);
            $entry['author'] = $author_card->show_inline();
        }

        return $entry;
    }

    private function _load_document($guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);

        // if the document doesn't belong to the current topic, we don't
        // show it, because otherwise folder-based permissions would be useless
        if ($document->topic != $this->_topic->id)
        {
            throw new midcom_error_notfound("The document '{$guid}' could not be found in this folder.");
        }

        // Load the document to datamanager
        if (!$this->_datamanager->autoset_storage($document))
        {
            debug_print_r('Object to be used was:', $document);
            throw new midcom_error('Failed to initialize the datamanager, see debug level log for more information.');
        }

        return $document;
    }

    /**
     * Displays older versions of the document
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_versions($handler_id, array $args, array &$data)
    {
        $this->_document = $this->_load_document($args[0]);
        $this->_provider = new org_openpsa_widgets_grid_provider($this, 'local');
        $this->_provider->add_order('created', 'DESC');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_versions($handler_id, array &$data)
    {
        if ($this->_provider->count_rows() == 0)
        {
            return;
        }
        $data['grid'] = $this->_provider->get_grid('documents_grid');

        midcom_show_style('show-document-grid');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        // Get the requested document object
        $this->_document = $this->_load_document($args[0]);

        //If the user hasn't looked at the document since its last update, save the current time as last visit
        $person = midcom::get()->auth->user->get_storage();
        if ((int) $person->get_parameter('org.openpsa.documents_visited', $this->_document->guid) < (int) $this->_document->metadata->revised)
        {
            $person->set_parameter('org.openpsa.documents_visited', $this->_document->guid, time());
        }

        // Get number of older versions
        $this->_request_data['document_versions'] = 0;
        $qb = org_openpsa_documents_document_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_request_data['directory']->id);
        if ($this->_document->nextVersion == 0)
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->id);
        }
        else
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->nextVersion);
            $qb->add_constraint('metadata.created', '<', gmstrftime('%Y-%m-%d %T', $this->_document->metadata->created));
        }
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_documents_document_dba::OBTYPE_DOCUMENT);
        $this->_request_data['document_versions'] = $qb->count();

        org_openpsa_widgets_ui::enable_ui_tab();
        org_openpsa_widgets_contact::add_head_elements();

        $this->_request_data['document_dm'] = $this->_datamanager;
        $this->_request_data['document'] = $this->_document;

        midcom::get()->head->set_pagetitle($this->_document->title);

        if ($this->_document->nextVersion == 0)
        {
            $this->_populate_toolbar();
        }

        $this->_add_version_navigation();

        $this->bind_view_to_object($this->_document, $this->_datamanager->schema->name);
    }

    private function _populate_toolbar()
    {
        if ($this->_document->can_do('midgard:update'))
        {
            $workflow = $this->get_workflow('datamanager2');
            $this->_view_toolbar->add_item($workflow->get_button("document/edit/{$this->_document->guid}/", array
            (
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )));
        }
        if ($this->_document->can_do('midgard:delete'))
        {
            $workflow = $this->get_workflow('delete', array('object' => $this->_document));
            $this->_view_toolbar->add_item($workflow->get_button("document/delete/{$this->_document->guid}/"));
        }
    }

    private function _add_version_navigation()
    {
        $previous_version = false;
        $next_version = false;

        $qb = org_openpsa_documents_document_dba::new_query_builder();
        if ($this->_document->nextVersion)
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->nextVersion);
            $qb->add_constraint('metadata.created', '<', gmstrftime('%Y-%m-%d %T', $this->_document->metadata->created));
        }
        else
        {
            $qb->add_constraint('nextVersion', '=', $this->_document->id);
        }
        $version = $qb->count() + 1;

        if ($version > 1)
        {
            $qb->add_order('metadata.created', 'DESC');
            $qb->set_limit(1);
            $results = $qb->execute();
            $previous_version = $results[0];
        }

        if ($this->_document->nextVersion != 0)
        {
            $qb = org_openpsa_documents_document_dba::new_query_builder();
            $qb->begin_group('OR');
            $qb->begin_group('AND');
            $qb->add_constraint('nextVersion', '=', $this->_document->nextVersion);
            $qb->add_constraint('metadata.revised', '>', gmstrftime('%Y-%m-%d %T', $this->_document->metadata->created));
            $qb->end_group();
            $qb->add_constraint('id', '=', $this->_document->nextVersion);
            $qb->end_group();
            $qb->add_order('nextVersion', 'DESC');
            $qb->add_order('metadata.created', 'ASC');
            $qb->set_limit(1);
            $results = $qb->execute();
            $next_version = $results[0];

            $current_version = org_openpsa_documents_document_dba::get_cached($this->_document->nextVersion);
            $version_date = $this->_l10n->get_formatter()->datetime($this->_document->metadata->revised);
            $this->add_breadcrumb('document/' . $current_version->guid . '/', $current_version->title);
            $this->add_breadcrumb('', sprintf($this->_l10n->get('version %s (%s)'), $version, $version_date));
        }
        else
        {
            $this->add_breadcrumb('document/' . $this->_document->guid . '/', $this->_document->title);
        }

        if ($next_version)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "document/{$next_version->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('next version'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/up.png',
                )
             );
        }
        if ($previous_version)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "document/{$previous_version->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('previous version'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/down.png',
                )
            );
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        midcom_show_style('show-document');
    }
}
