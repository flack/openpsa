<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Attachment editing interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_object_attachments extends midcom_baseclasses_components_handler
{
    /**
     * Current loaded object
     *
     * @var midcom_core_dbaobject
     */
    private $_object = null;

    /**
     * Files in the current object
     *
     * @var array
     */
    private $_files = array();

    /**
     * Current file being edited
     *
     * @var midcom_db_attachment
     */
    private $_file = null;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/legacy.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/attachments/layout.css');
    }

    private function _process_file_upload($uploaded_file)
    {
        if (is_null($this->_file)) {
            $local_filename = midcom_db_attachment::safe_filename($uploaded_file['name']);
            $local_file = $this->_get_file($local_filename, true);
        } else {
            $local_file = $this->_file;
        }

        if ($local_file->mimetype != $uploaded_file['type']) {
            $local_file->mimetype = $uploaded_file['type'];
            $local_file->update();
        }

        if (!$local_file->copy_from_file($uploaded_file['tmp_name'])) {
            return false;
        }
        return $local_file->name;
    }

    private function _process_form()
    {
        if (!isset($_POST['midgard_admin_asgard_save'])) {
            return false;
        }

        // Check if we have an uploaded file
        if (   isset($_FILES['midgard_admin_asgard_file'])
            && is_uploaded_file($_FILES['midgard_admin_asgard_file']['tmp_name'])) {
            return $this->_process_file_upload($_FILES['midgard_admin_asgard_file']);
        }

        if (is_null($this->_file)) {
            if (empty($_POST['midgard_admin_asgard_filename'])) {
                return false;
            }

            // We're creating a new file
            $local_filename = midcom_db_attachment::safe_filename($_POST['midgard_admin_asgard_filename']);
            $local_file = $this->_get_file($local_filename, true);
        } else {
            $local_file = $this->_file;
        }

        $needs_update = false;

        if (   !empty($_POST['midgard_admin_asgard_filename'])
            && $local_file->name != $_POST['midgard_admin_asgard_filename']) {
            $local_file->name = $_POST['midgard_admin_asgard_filename'];
            $needs_update = true;
        }

        if (   !empty($_POST['midgard_admin_asgard_mimetype'])
            && $local_file->mimetype != $_POST['midgard_admin_asgard_mimetype']) {
            $local_file->mimetype = $_POST['midgard_admin_asgard_mimetype'];
            $needs_update = true;
        }

        if (   $needs_update
            && !$local_file->update()) {
            return false;
        }

        // We should always store at least an empty string so it can be edited later
        $contents = '';
        if (!empty($_POST['midgard_admin_asgard_contents'])) {
            $contents = $_POST['midgard_admin_asgard_contents'];
        }

        if (!$local_file->copy_from_memory($contents)) {
            return false;
        }
        return $local_file->name;
    }

    /**
     *
     * @param string $filename
     * @param boolean $autocreate
     * @return midcom_db_attachment
     */
    private function _get_file($filename, $autocreate = false)
    {
        $qb = midcom_db_attachment::new_query_builder();
        $qb->add_constraint('parentguid', '=', $this->_object->guid);
        $qb->add_constraint('name', '=', $filename);

        $files = $qb->execute();
        if (empty($files)) {
            if (!$autocreate) {
                throw new midcom_error_notfound("Attachment '{$filename}' of object {$this->_object->guid} was not found.");
            }
            $file = new midcom_db_attachment();
            $file->name = $filename;
            $file->parentguid = $this->_object->guid;

            if (!$file->create()) {
                throw new midcom_error('Failed to create attachment, reason: ' . midcom_connection::get_error_string());
            }
            return $file;
        }
        return $files[0];
    }

    private function _list_files()
    {
        $qb = midcom_db_attachment::new_query_builder();
        $qb->add_constraint('parentguid', '=', $this->_object->guid);
        $qb->add_order('mimetype');
        $qb->add_order('metadata.score', 'DESC');
        $qb->add_order('name');
        $this->_files = $qb->execute();
    }

    /**
     * Add the necessary files for attachment operations, if attachments exist
     */
    private function _add_jscripts()
    {
        if (sizeof($this->_files) > 0) {
            // Add Colorbox
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/colorbox/jquery.colorbox-min.js');
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/jQuery/colorbox/colorbox.css', 'screen');
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/object_browser.js');

            //add table widget
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
            $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css');
            midcom\workflow\delete::add_head_elements();
        }
    }

    private function prepare_object($guid)
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($guid);
        $this->_object->require_do('midgard:update');
        $this->_object->require_do('midgard:attachments');
    }

    /**
     * Handler for creating new attachments
     *
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->prepare_object($args[0]);

        if ($filename = $this->_process_form()) {
            return new midcom_response_relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/{$filename}/");
        }

        $this->_list_files();
        $this->_add_jscripts();

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_create');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_create($handler_id, array &$data)
    {
        $data['files'] = $this->_files;
        $data['object'] = $this->_object;
        midcom_show_style('midgard_admin_asgard_object_attachments_header');

        $data['attachment_text_types'] = $this->_config->get('attachment_text_types');
        midcom_show_style('midgard_admin_asgard_object_attachments_new');
        midcom_show_style('midgard_admin_asgard_object_attachments_footer');
    }

    /**
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->prepare_object($args[0]);

        $data['filename'] = $args[1];
        $this->_file = $this->_get_file($data['filename']);
        $this->_file->require_do('midgard:update');
        $this->bind_view_to_object($this->_file);

        $filename = $this->_process_form();
        if (   $filename
            && $filename != $data['filename']) {
            return new midcom_response_relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/{$filename}/");
        }

        $this->_list_files();
        $this->_add_jscripts();

        $data['attachment_text_types'] = $this->_config->get('attachment_text_types');
        if (array_key_exists($this->_file->mimetype, $data['attachment_text_types'])) {
            // Figure out correct syntax from MIME type
            switch (preg_replace('/.+?\//', '', $this->_file->mimetype)) {
                case 'css':
                    $data['file_syntax'] = 'css';
                    break;

                case 'html':
                    $data['file_syntax'] = 'html';
                    break;

                case 'x-javascript':
                case 'javascript':
                    $data['file_syntax'] = 'javascript';
                    break;

                default:
                    $data['file_syntax'] = 'text';
            }
        }

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);
        return new midgard_admin_asgard_response($this, '_show_edit');
    }

    /**
     * Show the editing view for the requested style
     *
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['files'] = $this->_files;
        $data['file'] = $this->_file;
        $data['object'] = $this->_object;
        midcom_show_style('midgard_admin_asgard_object_attachments_header');
        midcom_show_style('midgard_admin_asgard_object_attachments_file');
        midcom_show_style('midgard_admin_asgard_object_attachments_footer');
    }

    /**
     * Handler for confirming file deleting for the requested file
     *
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->prepare_object($args[0]);

        $filename = $args[1];
        $file = $this->_get_file($filename);

        $workflow = $this->get_workflow('delete', array
        (
            'object' => $file,
            'label' => $filename,
            'success_url' => "__mfa/asgard/object/attachments/{$this->_object->guid}/"
        ));
        return $workflow->run();
    }
}
