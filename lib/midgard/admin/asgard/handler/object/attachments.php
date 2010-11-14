<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: attachments.php 23975 2009-11-09 05:44:22Z rambo $
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
     * @var MidCOM DBA object
     * @access private
     */
    var $_object = null;

    /**
     * Files in the current object
     *
     * @var array
     * @access private
     */
    var $_files = array();

    /**
     * Current file being edited
     *
     * @var midcom_db_attachment
     * @access private
     */
    var $_file = null;

    /**
     * Simple constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
        parent::__construct();

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.styleeditor/style-editor.css',
            )
        );

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midgard.admin.asgard/attachments/layout.css',
            )
        );
    }

    function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;
    }

    /**
     * Rewrite a filename to URL safe form
     *
     * @param string $filename file name to rewrite
     * @return string rewritten filename
     *
     * FIXME: This code is duplicated in many places (see DM blobs type for example), make single helper and use that
     */
    function safe_filename($filename)
    {
        $filename = basename(trim($filename));

        $regex = '/^(.*)(\..*?)$/';

        if (preg_match($regex, $filename, $ext_matches))
        {
            $name = $ext_matches[1];
            $ext = $ext_matches[2];
        }
        else
        {
            $name = $filename;
            $ext = '';
        }
        return midcom_generate_urlname_from_string($name) . $ext;
    }

    /*function _load_config()
    {
        $_MIDCOM->load_library('midgard.admin.asgard');
        $this->_config = $GLOBALS['midcom_component_data']['midgard.admin.asgard']['config'];
    }*/

    function _process_file_upload($uploaded_file)
    {
        if (is_null($this->_file))
        {
            $local_filename = $this->safe_filename($uploaded_file['name']);
            $local_file = $this->_get_file($local_filename);
            if (!$local_file)
            {
                // New file, create
                $local_file = new midcom_db_attachment();
                $local_file->name = $local_filename;
                $local_file->parentguid = $this->_object->guid;
                $local_file->mimetype = $uploaded_file['type'];

                if (!$local_file->create())
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create attachment, reason: ' . midcom_application::get_error_string());
                    // This will exit.
                }
            }
        }
        else
        {
            $local_file = $this->_file;
        }


        if ($local_file->mimetype != $uploaded_file['type'])
        {
            $local_file->mimetype = $uploaded_file['type'];
            $local_file->update();
        }

        if (!$local_file->copy_from_file($uploaded_file['tmp_name']))
        {
            return false;
        }
        return $local_file->name;
    }

    function _process_form()
    {
        if (!isset($_POST['midgard_admin_asgard_save']))
        {
            return false;
        }

        // Check if we have an uploaded file
        if (   isset($_FILES['midgard_admin_asgard_file'])
            && is_uploaded_file($_FILES['midgard_admin_asgard_file']['tmp_name']))
        {
            return $this->_process_file_upload($_FILES['midgard_admin_asgard_file']);
        }

        if (is_null($this->_file))
        {
            if (   !isset($_POST['midgard_admin_asgard_filename'])
                || empty($_POST['midgard_admin_asgard_filename']))
            {
                return false;
            }

            // We're creating a new file
            $local_filename = $this->safe_filename($_POST['midgard_admin_asgard_filename']);
            $local_file = $this->_get_file($local_filename);
            if (!$local_file)
            {
                // New file, create
                $local_file = new midcom_db_attachment();
                $local_file->name = $local_filename;
                $local_file->parentguid = $this->_object->guid;

                if (!$local_file->create())
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to create attachment, reason: ' . midcom_application::get_error_string());
                    // This will exit.
                }
            }
        }
        else
        {
            $local_file = $this->_file;
        }

        $success = true;

        if (   isset($_POST['midgard_admin_asgard_filename'])
            && !empty($_POST['midgard_admin_asgard_filename'])
            && $local_file->name != $_POST['midgard_admin_asgard_filename'])
        {
            $local_file->name = $_POST['midgard_admin_asgard_filename'];

            if (!$local_file->update())
            {
                $success = false;
            }
        }

        if (   isset($_POST['midgard_admin_asgard_mimetype'])
            && !empty($_POST['midgard_admin_asgard_mimetype'])
            && $local_file->mimetype != $_POST['midgard_admin_asgard_mimetype'])
        {
            $local_file->mimetype = $_POST['midgard_admin_asgard_mimetype'];

            if (!$local_file->update())
            {
                $success = false;
            }
        }

        // We should always store at least an empty string so it can be edited later
        $contents = '';
        if (   isset($_POST['midgard_admin_asgard_contents'])
            && !empty($_POST['midgard_admin_asgard_contents']))
        {
            $contents = $_POST['midgard_admin_asgard_contents'];
        }

        if (!$local_file->copy_from_memory($contents))
        {
            $success = false;
        }

        if (!$success)
        {
            return false;
        }
        return $local_file->name;
    }

    function _get_file($filename)
    {
        $qb = midcom_db_attachment::new_query_builder();
        $qb->add_constraint('parentguid', '=', $this->_object->guid);
        $qb->add_constraint('name', '=', $filename);

        $files = $qb->execute();
        if (empty($files))
        {
            return false;
        }
        return $files[0];
    }

    function _list_files()
    {
        $qb = midcom_db_attachment::new_query_builder();
        $qb->add_constraint('parentguid', '=', $this->_object->guid);
        $qb->add_order('mimetype');
        $qb->add_order('metadata.score', 'DESC');
        $qb->add_order('name');
        $this->_files = $qb->execute();
    }

    /**
     * Helper function that adds the necessary files for attachment operations,
     * if attachments exist
     */
    function _add_jscripts()
    {
        if (sizeof($this->_files) > 0)
        {

            // Add Thickbox
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/object_browser.js');
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/thickbox/jquery-thickbox-3.1.pack.js');
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => MIDCOM_STATIC_URL . '/jQuery/thickbox/thickbox.css',
                    'media' => 'screen',
                )
            );
            $_MIDCOM->add_jscript('var tb_pathToImage = "' . MIDCOM_STATIC_URL . '/jQuery/thickbox/loadingAnimation.gif"');

            //add table widget
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => MIDCOM_STATIC_URL . '/midgard.admin.asgard/tablewidget.css',
                )
            );
        }
    }

    /**
     * Handler method for creating new attachments
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    function _handler_create($handler_id, $args, &$data)
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }
        $this->_object->require_do('midgard:update');
        $this->_object->require_do('midgard:attachments');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $filename = $this->_process_form();
        if (!$filename)
        {
            // Show error
        }
        else
        {
            $_MIDCOM->relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/{$filename}/");
        }

        $this->_list_files();
        $this->_add_jscripts();

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        return true;
    }

    /**
     * Show the editing view for the requested style
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_create($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['files'] =& $this->_files;
        $data['object'] =& $this->_object;
        midcom_show_style('midgard_admin_asgard_object_attachments_header');

        $data['attachment_text_types'] = $this->_config->get('attachment_text_types');
        midcom_show_style('midgard_admin_asgard_object_attachments_new');
        midcom_show_style('midgard_admin_asgard_object_attachments_footer');

        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @access public
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }
        $this->_object->require_do('midgard:update');
        $this->_object->require_do('midgard:attachments');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $data['filename'] = $args[1];
        $this->_file = $this->_get_file($data['filename']);
        if (!$this->_file)
        {
            return false;
        }
        $this->_file->require_do('midgard:update');
        $_MIDCOM->bind_view_to_object($this->_file);

        $filename = $this->_process_form();
        if (!$filename)
        {
            // Show error
        }
        else
        {
            if ($filename != $data['filename'])
            {
                $_MIDCOM->relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/{$filename}/");
            }
        }

        $this->_list_files();
        $this->_add_jscripts();

        $data['attachment_text_types'] = $this->_config->get('attachment_text_types');
        if (array_key_exists($this->_file->mimetype, $data['attachment_text_types']))
        {
            // Figure out correct syntax from MIME type
            switch(preg_replace('/.+?\//', '', $this->_file->mimetype))
            {
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

        return true;
    }

    /**
     * Show the editing view for the requested style
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_edit($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $host_prefix = $_MIDCOM->get_host_prefix();
        $delete_url = $host_prefix . '__mfa/asgard/object/attachments/delete/' . $this->_object->guid . '/' . $this->_file->name;

        $data['delete_url'] =& $delete_url;
        $data['files'] =& $this->_files;
        $data['file'] =& $this->_file;
        $data['object'] =& $this->_object;
        midcom_show_style('midgard_admin_asgard_object_attachments_header');
        midcom_show_style('midgard_admin_asgard_object_attachments_file');
        midcom_show_style('midgard_admin_asgard_object_attachments_footer');

        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Handler method for confirming file deleting for the requested file
     *
     * @access public
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$args[0]}' was not found.");
            // This will exit.
        }
        $this->_object->require_do('midgard:update');
        $this->_object->require_do('midgard:attachments');
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        $data['filename'] = $args[1];
        $this->_file = $this->_get_file($data['filename']);
        if (!$this->_file)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Attachment '{$data['filename']}' of object {$this->_object->guid} was not found.");
            // This will exit.
        }

        // Require delete privilege
        $this->_file->require_do('midgard:delete');

        if (isset($_POST['f_cancel']))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), $_MIDCOM->i18n->get_string('delete cancelled', 'midgard.admin.asgard'));
            $_MIDCOM->relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/{$data['filename']}/");
            // This will exit
        }

        if (isset($_POST['f_confirm']))
        {
            if ($this->_file->delete())
            {
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'), sprintf($_MIDCOM->i18n->get_string('file %s deleted', 'midgard.admin.asgard'), $data['filename']));
                $_MIDCOM->relocate("__mfa/asgard/object/attachments/{$this->_object->guid}/");
                // This will exit
            }


        }

        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $data);

        return true;
    }

    /**
     * Show the delete request
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_delete($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['file'] =& $this->_file;
        $data['attachment_text_types'] = $this->_config->get('attachment_text_types');
        midcom_show_style('midgard_admin_asgard_object_attachments_delete');

        midgard_admin_asgard_plugin::asgard_footer();
    }

}
?>