<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: attachment.php 26707 2010-10-19 14:14:12Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Attachment record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the GetBy*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable as midcom_application::dbfactory.
 *
 * @package midcom.db
 * @see midcom_services_dbclassloader
 */
class midcom_db_attachment extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_attachment';

    /**
     * Internal tracking state variable, holds the file handle of any open
     * attachment.
     *
     * @var resource
     * @access private
     */
    var $_open_handle = null;
    
    /**
     * Internal tracking state variable, TRUE if the attachment has a handle opened in write mode
     */
    var $_open_write_mode = false;
    
    /**
     * This switch will tell the checkup routines whether the current creation is going to
     * duplicate the object or not. It is used to create a new blob entry when copying an object
     * containing this attachment.
     * 
     * @var boolean
     * @access public
     */
    var $_duplicate = false;

    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }
    
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    function get_parent_guid_uncached()
    {
        return $this->parentguid;
    }

    /**
     * Returns the Parent of the Attachment, which is identified by the table/id combination
     * in the attachment record. The table in question is used to identify the object to
     * use. If multiple objects are registered for a given table, the first matching class
     * returned by the dbfactory is used (which is usually rather arbitrary).
     *
     * @todo Not yet implemented.
     * @return MidgardObject Parent object.
     */
    function get_parent_guid_uncached_static($guid)
    {
        $mc = new midgard_collector('midgard_attachment', 'guid', $guid);
        $mc->set_key_property('parentguid');
        $mc->execute();
        $link_values = $mc->list_keys();
        if (!$link_values)
        {
            return null;
        }
        
        foreach ($link_values as $key => $value)
        {
            return $key;
        }
    }

    /**
     * Opens the attachment for file IO, the semantics match the original
     * mgd_open_attachment call. Returns a filehandle that can be used with the
     * usual PHP file functions if successful, the handle has to be closed with
     * the close() method when you no longer need it, don't let it fall over
     * the end of the script.
     *
     * <b>Important Note:</b> It is important to use the close() member function of
     * this class to close the file handle, not just fclose(). Otherwise, the upgrade
     * notification switches will fail.
     *
     * @param string $mode The mode which should be used to open the attachment, same as
     *     the mode parameter of the PHP fopen call. This defaults to write access (see
     *     mgd_open_attachmentl for details).
     * @return resource A file handle to the attachment if successful, false on failure.
     */
    function open()
    {
        if (! $this->id)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Cannot open a non-persistent attachment..', MIDCOM_LOG_WARN);
            debug_print_r('Object state:', $this);
            debug_pop();
            return false;
        }

        if ($this->_open_handle !== null)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the Attachment {$this->id} already had an open file handle, we close it implicitly.", MIDCOM_LOG_WARN);
            debug_pop();
            @fclose($this->_open_handle);
            $this->_open_handle = null;
        }

        switch (func_num_args())
        {
            case 0:
                $mode = 'default';
                $this->_open_write_mode = true;

                $blob = new midgard_blob($this->__object);
                if (!$blob)
                {
                    /* FIXME, throw error */
                }

                $handle = $blob->get_handler();
                break;

            case 1:
                $mode = func_get_arg(0);
                $this->_open_write_mode = ($mode{0} != 'r');

                /* WARNING, read mode not supported by midgard_blob! */
                $blob = new midgard_blob($this->__object);
                if (!$blob)
                {
                    /* FIXME, throw error */
                }

                $handle = @fopen($blob->get_path(), $mode);
                break;

            default:
                trigger_error('midcom_db_attachment takes either zero or one arguments.', E_USER_ERROR);
                // This should exit.
        }

        if (!$handle)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to open attachment with mode {$mode}, last Midgard error was:" . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            debug_pop();
        }

        $this->_open_handle = $handle;

        return $handle;
    }
    
    /**
     * This function reads the file and returns its contents
     * 
     * @access public
     * @return string
     */
    function read()
    {
        $attachment = new midgard_attachment($this->guid);
        $blob = new midgard_blob($attachment);
        
        $contents = $blob->read_content();
        
        return $contents;
    }

    /**
     * This function closes the open write handle obtained by the open() call again.
     * It is required to call this function instead of a simple fclose to ensure proper
     * upgrade notifications.
     */
    function close()
    {
        if ($this->_open_handle === null)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Tried to close non-open attachment {$this->id}", MIDCOM_LOG_WARN);
            debug_pop();
            return;
        }

        fclose ($this->_open_handle);
        $this->_open_handle = null;

        if ($this->_open_write_mode)
        {
            // We need to update the attachment now, this cannot be done in the Midgard Core
            // at this time.
            if (! $this->update())
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to update attachment {$this->id}", MIDCOM_LOG_WARN);
                debug_pop();
                return;
            }
    
            $object = $this->get_parent();
            if ($object !== null)
            {
                $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $object);
            }
            
            $this->file_to_cache();
        }
    }

    /**
     * Get the path to the document in the static cache
     * 
     * @return string
     */
    static function get_cache_path(midcom_db_attachment $attachment, $check_privileges = true)
    {

        if (!$GLOBALS['midcom_config']['attachment_cache_enabled'])
        {
            return null;
        }

        // Check if the attachment can be read anonymously
        if (   $check_privileges
            && !$attachment->can_do('midgard:read', 'EVERYONE'))
        {
            return null;
        }

        // Copy the file to the static directory
        if (!file_exists($GLOBALS['midcom_config']['attachment_cache_root']))
        {
            mkdir($GLOBALS['midcom_config']['attachment_cache_root']);
        }
        
        $subdir = substr($attachment->guid, 0, 1);
        if (!file_exists("{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}"))
        {
            mkdir("{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}");
        }
        
        $filename = "{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}/{$attachment->guid}_{$attachment->name}";
        
        return $filename;
    }

    function file_to_cache()
    {
        // Check if the attachment can be read anonymously
        if (!$GLOBALS['midcom_config']['attachment_cache_enabled'])
        {
            return;
        }
        
        if (!$this->can_do('midgard:read', 'EVERYONE'))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Attachment {$this->name} ({$this->guid}) is not publicly readable, not caching.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }

        $filename = midcom_db_attachment::get_cache_path($this);

        if (!$filename)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to generate cache path for attachment {$this->name} ({$this->guid}), not caching.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }
  
        if (   file_exists($filename)
            && is_link($filename))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Attachment {$this->name} ({$this->guid}) is already in cache as {$filename}, skipping.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }
        
        // Then symlink the file
        $blob = new midgard_blob($this->__object);
        
        if (@symlink($blob->get_path(), $filename))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Symlinked attachment {$this->name} ({$this->guid}) as {$filename}.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }
        
        // Symlink failed, actually copy the data
        $fh = $this->open('r');
        if (!$fh)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to cache attachment {$this->name} ({$this->guid}), opening failed.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }

        $data = '';
        while (!feof($fh))
        {
            $data .= fgets($fh);
        }
        fclose($fh);
        $this->_open_handle = null;
        
        file_put_contents($filename, $data);
        
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Symlinking attachment {$this->name} ({$this->guid}) as {$filename} failed, data copied instead.", MIDCOM_LOG_DEBUG);
        debug_pop();
    }

    /**
     * Simple wrapper for stat() on the blob object.
     *
     * @return mixed Either a stat array as for stat() or false on failure.
     */
    function stat()
    {
        if (!$this->id)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Cannot open a non-persistent attachment..', MIDCOM_LOG_WARN);
            debug_print_r('Object state:', $this);
            debug_pop();
            return false;
        }

        $blob = new midgard_blob($this->__object);
        if (!$blob)
        {
            return false;
        }
        
        $path = $blob->get_path();
        if (!file_exists($path))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("File {$path} that blob {$this->guid} points to cannot be found", MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        return stat($path);
    }

    /**
     * Internal helper, computes an MD5 string which is used as an attachment location.
     * It should be random enough, even if the algorithm used does not match the one
     * Midgard uses. If the location already exists, it will iterate until an unused
     * location is found.
     *
     * @return string An unused attachment location.
     */
    function _create_attachment_location()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $location_in_use = true;
        $location = '';

        while ($location_in_use)
        {
            $base = get_class($this);
            $base .= microtime();
            if (isset($this->id))
            {
                $base .= $this->id;
            }
            else if (isset($this->guid))
            {
                $base .= $this->guid;                
            }
            $base .= $_SERVER['SERVER_NAME'];
            $base .= $_SERVER['REMOTE_ADDR'];
            $base .= $_SERVER['REMOTE_PORT'];
            $name = strtolower(md5($base));
            $location = strtoupper(substr($name, 0, 1) . '/' . substr($name, 1, 1) . '/') . $name;

            // Check uniqueness
            $qb = midcom_db_attachment::new_query_builder();
            $qb->add_constraint('location', '=', $location);
            if (   isset($this->id)
                && !empty($this->id))
            {
                // Add this one if and only if we are persistent already.
                $qb->add_constraint('id', '<>', $this->id);
            }
            elseif (   isset($this->guid)
                && !empty($this->guid))
            {
                // Add this one if and only if we are persistent already.
                $qb->add_constraint('guid', '<>', $this->guid);
            }
            $result = $qb->count_unchecked();

            if ($result == 0)
            {
                $location_in_use = false;
            }
            else
            {
                debug_add("Location {$location} is in use, retrying");
                debug_pop();
            }
        }

        debug_add("Created this location: {$location}");
        debug_pop();
        return $location;
    }

    /**
     * Simple creation event handler which fills out the location field if it
     * is still empty with a location generated by _create_attachment_location().
     *
     * @return boolean True if creation may commence.
     */
    function _on_creating()
    {
        if (empty($this->mimetype))
        {
            $this->mimetype = 'application/octet-stream';
        }
        
        if (! parent::_on_creating())
        {
            return false;
        }
        
        if (!$this->_duplicate)
        {
            $this->location = $this->_create_attachment_location();
        }
        
        return true;
    }

    /**
     * Created callback, triggers watches on the parent(!) object.
     */
    function _on_created()
    {
        parent::_on_created();
        $object = $this->get_parent();
        if ($object !== null)
        {
            $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $object);
        }
    }
    
    function update_cache()
    {
        // Check if the attachment can be read anonymously
        if (   $GLOBALS['midcom_config']['attachment_cache_enabled']
            && !$this->can_do('midgard:read', 'EVERYONE'))
        {
            // Not public file, ensure it is removed
            $subdir = substr($this->guid, 0, 1);
            $filename = "{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}/{$this->guid}_{$this->name}";
            if (file_exists($filename))
            {
                @unlink($filename);
            }
        }
    }

    /**
     * Updated callback, triggers watches on the parent(!) object.
     */
    function _on_updated()
    {
        parent::_on_updated();
        $this->update_cache();
        $object = $this->get_parent();
        if ($object !== null)
        {
            $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $object);
        }
    }

    /**
     * Deleted callback, triggers watches on the parent(!) object.
     */
    function _on_deleted()
    {
        parent::_on_deleted();

        if ($GLOBALS['midcom_config']['attachment_cache_enabled'])
        {
            // Check if other attachments point to the same file
            //$mc = midcom_db_attachment::new_collector('name', $this->name);
            //$mc->execute();
            //if (count($mc->list_keys()) > 0)
            //{
            //}

            // Remove attachment cache
            $filename = midcom_db_attachment::get_cache_path($this, false);
            if (   !is_null($filename)
                && file_exists($filename))
            {
                @unlink($filename);
            }
        }

        $object = $this->get_parent();
        if ($object !== null)
        {
            $_MIDCOM->componentloader->trigger_watches(MIDCOM_OPERATION_DBA_UPDATE, $object);
        }
    }

    /**
     * Updates the contents of the attachments with the contents given.
     *
     * @param mixed $source File contents.
     * @return boolean Indicating success.
     */
    function copy_from_memory($source)
    {
        $dest = $this->open();
        if (! $dest)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not open attachment for writing, last Midgard error was: ' . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        fwrite($dest, $source);

        $this->close();
        return true;
    }

    /**
     * Updates the contents of the attachments with the contents of the resource identified
     * by the filehandle passed.
     *
     * @param resource $source The handle to read from.
     * @return boolean Indicating success.
     */
    function copy_from_handle($source)
    {
        $dest = $this->open();
        if (! $dest)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not open attachment for writing, last Midgard error was: ' . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        while (! feof($source))
        {
            fwrite($dest, fread($source, 100000));
        }

        $this->close();
        return true;
    }

    /**
     * Updates the contents of the attachments with the contents of the file specified.
     * This is a wrapper for copy_from_handle.
     *
     * @param string $filename The file to read.
     * @return boolean Indicating success.
     */
    function copy_from_file($filename)
    {
        $source = @fopen ($filename, 'r');
        if (! $source)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Could not open file for reading.' . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            if (isset($php_errorstr))
            {
                debug_add("Last PHP error was: {$php_errorstr}", MIDCOM_LOG_WARN);
            }
            debug_pop();
            return false;
        }
        $result = $this->copy_from_handle($source);
        fclose($source);
        return $result;
    }
}
?>
