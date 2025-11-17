<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\blob;

/**
 * MidCOM level replacement for the Midgard Attachment record with framework support.
 *
 * @property string $name Filename of the attachment
 * @property string $title Title of the attachment
 * @property string $location Location of the attachment in the blob directory structure
 * @property string $mimetype MIME type of the attachment
 * @property string $parentguid GUID of the object the attachment is attached to
 * @package midcom.db
 */
class midcom_db_attachment extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'midgard_attachment';

    public bool $_use_rcs = false;

    /**
     * Internal tracking state variable, holds the file handle of any open
     * attachment.
     *
     * @var resource
     */
    private $_open_handle;

    /**
     * Internal tracking state variable, true if the attachment has a handle opened in write mode
     */
    private bool $_open_write_mode = false;

    /**
     * Opens the attachment for file IO.
     *
     * Returns a filehandle that can be used with the usual PHP file functions if successful,
     * the handle has to be closed with the close() method when you no longer need it, don't
     * let it fall over the end of the script.
     *
     * <b>Important Note:</b> It is important to use the close() member function of
     * this class to close the file handle, not just fclose(). Otherwise, the upgrade
     * notification switches will fail.
     *
     * @param string $mode The mode which should be used to open the attachment, same as
     *     the mode parameter of the PHP fopen call. This defaults to write access.
     * @return resource|false A file handle to the attachment if successful, false on failure.
     */
    public function open(string $mode = 'w')
    {
        if (!$this->id) {
            debug_add('Cannot open a non-persistent attachment.', MIDCOM_LOG_WARN);
            debug_print_r('Object state:', $this);
            return false;
        }

        if ($this->_open_handle !== null) {
            debug_add("Warning, the attachment {$this->id} already had an open file handle, we close it implicitly.", MIDCOM_LOG_WARN);
            $this->close();
        }

        $blob = new blob($this->__object);
        $handle = $blob->get_handler($mode);

        if (!$handle) {
            debug_add("Failed to open attachment with mode {$mode}, last PHP error was: ", MIDCOM_LOG_WARN);
            midcom::get()->debug->log_php_error(MIDCOM_LOG_WARN);
            return false;
        }

        $this->_open_write_mode = ($mode[0] != 'r');
        $this->_open_handle = $handle;

        return $handle;
    }

    /**
     * Read the file and return its contents
     */
    public function read() : ?string
    {
        $blob = new blob($this->__object);
        return $blob->read_content();
    }

    /**
     * Close the open write handle obtained by the open() call again.
     * It is required to call this function instead of a simple fclose to ensure proper
     * upgrade notifications.
     */
    public function close()
    {
        if ($this->_open_handle === null) {
            debug_add("Tried to close non-open attachment {$this->id}", MIDCOM_LOG_WARN);
            return;
        }

        fclose($this->_open_handle);
        $this->_open_handle = null;

        if ($this->_open_write_mode) {
            // We need to update the attachment now, this cannot be done in the Midgard Core
            // at this time.
            if (!$this->update()) {
                debug_add("Failed to update attachment {$this->id}", MIDCOM_LOG_WARN);
                return;
            }

            $this->file_to_cache();
            $this->_open_write_mode = false;
        }
    }

    /**
     * Rewrite a filename to URL safe form
     *
     * @todo add possibility to use the file utility to determine extension if missing.
     */
    public static function safe_filename(string $filename) : string
    {
        // we could use basename() or pathinfo() here, except that it swallows multibyte chars at the
        // beginning of the string if we run in e.g. C locale..
        $parts = explode('/', trim($filename));
        $filename = end($parts);

        if (preg_match('/^(.*)(\..*?)$/', $filename, $ext_matches)) {
            [, $name, $ext] = $ext_matches;
        } else {
            $name = $filename;
            $ext = '';
        }
        return midcom_helper_misc::urlize($name) . $ext;
    }

    /**
     * Get the path to the document in the static cache
     */
    private function get_cache_path() : ?string
    {
        // Copy the file to the static directory
        $cacheroot = midcom::get()->config->get('attachment_cache_root');
        $subdir = $this->guid[0];
        if (!file_exists("{$cacheroot}/{$subdir}/{$this->guid}")) {
            if (!mkdir("{$cacheroot}/{$subdir}/{$this->guid}", 0777, true)) {
                return null;
            }
        }

        return "{$cacheroot}/{$subdir}/{$this->guid}/{$this->name}";
    }

    public static function get_url(midgard_attachment|midcom_db_attachment|string $attachment, ?string $name = null) : string
    {
        if (is_string($attachment)) {
            $guid = $attachment;
            if (null === $name) {
                $mc = self::new_collector('guid', $guid);
                $names = $mc->get_values('name');
                $name = array_pop($names);
            }
        } else {
            $guid = $attachment->guid;
            $name = $attachment->name;
        }

        if (!$guid) {
            return '';
        }

        if (midcom::get()->config->get('attachment_cache_enabled')) {
            $subdir = $guid[0];

            if (file_exists(midcom::get()->config->get('attachment_cache_root') . '/' . $subdir . '/' . $guid . '/' . $name)) {
                return midcom::get()->config->get('attachment_cache_url') . '/' . $subdir . '/' . $guid . '/' . urlencode($name);
            }
        }

        // Use regular MidCOM attachment server
        return midcom_connection::get_url('self') . 'midcom-serveattachmentguid-' . $guid . '/' . urlencode($name);
    }

    public function file_to_cache()
    {
        if (!midcom::get()->config->get('attachment_cache_enabled')) {
            return;
        }

        if (!$this->can_do('midgard:read', 'EVERYONE')) {
            debug_add("Attachment {$this->name} ({$this->guid}) is not publicly readable, not caching.");
            $this->remove_from_cache();
            return;
        }

        $filename = $this->get_cache_path();

        if (!$filename) {
            debug_add("Failed to create cache dir, skipping.");
            return;
        }

        if (file_exists($filename) && is_link($filename)) {
            debug_add("Attachment {$this->name} ({$this->guid}) is already in cache as {$filename}, skipping.");
            return;
        }

        // Then symlink the file
        if (@symlink($this->get_path(), $filename)) {
            debug_add("Symlinked attachment {$this->name} ({$this->guid}) as {$filename}.");
            return;
        }

        // Symlink failed, actually copy the data
        if (!copy($this->get_path(), $filename)) {
            debug_add("Failed to cache attachment {$this->name} ({$this->guid}), copying failed.");
        } else {
            debug_add("Symlinking attachment {$this->name} ({$this->guid}) as {$filename} failed, data copied instead.");
        }
    }

    private function remove_from_cache()
    {
        $filename = $this->get_cache_path();
        if ($filename && file_exists($filename)) {
            @unlink($filename);
        }
    }

    /**
     * Simple wrapper for stat() on the blob object.
     *
     * @return mixed Either a stat array as for stat() or false on failure.
     */
    public function stat()
    {
        if (!$this->id) {
            debug_add('Cannot open a non-persistent attachment.', MIDCOM_LOG_WARN);
            debug_print_r('Object state:', $this);
            return false;
        }

        $path = $this->get_path();
        if (!file_exists($path)) {
            debug_add("File {$path} that blob {$this->guid} points to cannot be found", MIDCOM_LOG_WARN);
            return false;
        }

        return stat($path);
    }

    public function get_path() : string
    {
        if (!$this->id) {
            return '';
        }
        return (new blob($this->__object))->get_path();
    }

    /**
     * Internal helper, computes an MD5 string which is used as an attachment location.
     * If the location already exists, it will iterate until an unused location is found.
     */
    private function _create_attachment_location() : string
    {
        $max_tries = 500;

        for ($i = 0; $i < $max_tries; $i++) {
            $name = strtolower(md5(uniqid(more_entropy: true)));
            $location = strtoupper($name[0] . '/' . $name[1]) . '/' . $name;

            // Check uniqueness
            $qb = self::new_query_builder();
            $qb->add_constraint('location', '=', $location);
            $result = $qb->count_unchecked();

            if ($result == 0) {
                debug_add("Created this location: {$location}");
                return $location;
            }
            debug_add("Location {$location} is in use, retrying");
        }
        throw new midcom_error('could not create attachment location');
    }

    /**
     * Simple creation event handler which fills out the location field if it
     * is still empty with a location generated by _create_attachment_location().
     */
    public function _on_creating() : bool
    {
        if (empty($this->mimetype)) {
            $this->mimetype = 'application/octet-stream';
        }

        $this->location = $this->_create_attachment_location();

        return true;
    }

    public function update_cache()
    {
        // Check if the attachment can be read anonymously
        if (   midcom::get()->config->get('attachment_cache_enabled')
            && !$this->can_do('midgard:read', 'EVERYONE')) {
            // Not public file, ensure it is removed
            $this->remove_from_cache();
        }
    }

    /**
     * Updated callback, triggers watches on the parent(!) object.
     */
    public function _on_updated()
    {
        $this->update_cache();
    }

    /**
     * Deleted callback, triggers watches on the parent(!) object.
     */
    public function _on_deleted()
    {
        if (midcom::get()->config->get('attachment_cache_enabled')) {
            // Remove attachment cache
            $this->remove_from_cache();
        }
    }

    /**
     * Updates the contents of the attachments with the contents given.
     *
     * @param string $data File contents.
     */
    public function copy_from_memory(string $data) : bool
    {
        if ($dest = $this->open()) {
            fwrite($dest, $data);
            $this->close();
            return true;
        }
        return false;
    }

    /**
     * Updates the contents of the attachments with the contents of the resource identified
     * by the filehandle passed.
     *
     * @param resource $source The handle to read from.
     */
    public function copy_from_handle($source) : bool
    {
        if ($dest = $this->open()) {
            stream_copy_to_stream($source, $dest);
            $this->close();
            return true;
        }
        return false;
    }

    /**
     * Updates the contents of the attachments with the contents of the file specified.
     * This is a wrapper for copy_from_handle.
     *
     * @param string $filename The file to read.
     */
    public function copy_from_file(string $filename) : bool
    {
        $source = @fopen($filename, 'r');
        if (!$source) {
            debug_add('Could not open file for reading.' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            midcom::get()->debug->log_php_error(MIDCOM_LOG_WARN);
            return false;
        }
        $result = $this->copy_from_handle($source);
        fclose($source);
        return $result;
    }
}
