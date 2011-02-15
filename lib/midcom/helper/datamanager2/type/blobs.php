<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Blob management type.
 *
 * This type allows you to control an arbitrary number of attachments on a given object.
 * It can only operate if the storage implementation provides it with a Midgard Object.
 * The storage location provided by the schema is unused at this time, as attachment
 * operations cannot be undone. Instead, the direct parameter calls are used to manage
 * the list of attachments in a parameter associated to the domain of the type. The
 * storage IO calls will not do much, except synchronizing data where necessary.
 *
 * The type can manage an arbitrary number of attachments. Each attachment is identified
 * by a handle (not its name!). It provides management functions for existing attachments,
 * which allow you to add, delete and update them in all variants. These functions
 * are executed immediately on the storage object, no undo is possible.
 *
 * This type serves as a base class for other, more advanced blob types, like the image type.
 *
 * <b>Available configuration options:</b>
 *
 * - <b>string attachment_server_url:</b> This is the base URL used for attachment serving.
 *   It defaults to the global MidCOM attachment handler at the sites root. When constructing
 *   Attachment Info blocks, this URL is completed using "{$baseurl}{$guid}/{$filename}".
 * - boolean sortable Should the attachments list be sortable. True if the sorting should
 *   be turned on, false if they should be sorted alphabetically.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_blobs extends midcom_helper_datamanager2_type
{
    /**
     * All attachments covered by this field.
     * The array contains midcom_db_attachment objects indexed by their
     * identifier within the field.
     *
     * See the $attachments_info member for a more general approach easily usable
     * within styles.
     *
     * @var Array
     */
    public $attachments = Array();

    /**
     * This is the base URL used for attachment serving.
     *
     * It defaults to the global MidCOM attachment handler at the sites root.
     * When constructing Attachment Info blocks, this URL is completed using
     * "{$baseurl}{$guid}/{$filename}".
     *
     * @var string
     */
    public $attachment_server_url = null;

    /**
     * This member is populated and synchronized with all known changes to the
     * attachments listing.
     *
     * It contains a batch of metadata that makes presenting them easy. The
     * information is kept in an array per attachment, again indexed
     * by their identifiers. The following keys are defined:
     *
     * - filename: The name of the file (useful to produce nice links).
     * - mimetype: The MIME Type.
     * - url: A complete URL valid for the current site, which delivers the attachment.
     * - filesize and formattedsize: The size of the file, as integer, and as formatted number
     *   with thousand-separator.
     * - lastmod and isoformattedlastmod: The UNIX- and ISO-formatted timestamp of the
     *   last modification to the attachment file.
     * - id, guid: The ID and GUID of the attachment.
     * - description: The title of the attachment, usually used as a caption.
     * - size_x, size_y and size_line: Only applicable for images, holds the x and y
     *   sizes of the image, along with a line suitable for inclusion in the <img />
     *   tags.
     * - object: This is a reference to the attachment object (in $attachments).
     * - identifier: The identifier of the attachment (for reverse-lookup purposes).
     *
     * The information in this listing should be considered read-only. If you want to
     * change information like the Title of an attachment, you need to do this using
     * the attachment object directly.
     *
     * @var Array
     */
    public $attachments_info = Array();

    /**
     * Maximum amount of blobs allowed to be stored in the same field
     *
     * @var integer
     */
    public $max_count = 0;

    /**
     * Should the widget offer sorting feature
     *
     * @var boolean
     */
    public $sortable = false;

    /**
     * Sorted attachments list
     *
     * @var array
     */
    public $_sorted_list = array();

    /**
     * Set the base URL accordingly
     *
     * this requires midcom_config access and is thus not possible using class member initializers.
     */
    public function _on_configuring($config)
    {
        parent::_on_configuring($config);

        $this->attachment_server_url =
            "{$GLOBALS['midcom_config']['midcom_site_url']}midcom-serveattachmentguid-";
    }

    /**
     * This function loads all known attachments from the storage object.
     *
     * It will leave the field empty in case the storage object is null.
     */
    function convert_from_storage ($source)
    {
        $this->attachments = Array();
        $this->attachments_info = Array();

        if ($this->storage->object === null)
        {
            // We don't have a storage object, skip the rest of the operations.
            return;
        }

        $raw_list = $this->storage->object->get_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->name}");
        if (!$raw_list)
        {
            // No attachments found.
            return;
        }

        $items = explode(',', $raw_list);

        foreach ($items as $item)
        {
            $info = explode(':', $item);
            if (   !is_array($info)
                || !array_key_exists(0, $info)
                || !array_key_exists(1, $info))
            {
                // Broken item
                debug_add("item '{$item}' is broken!", MIDCOM_LOG_ERROR);
                continue;
            }
            $identifier = $info[0];
            $guid = $info[1];
            $this->_load_attachment($identifier, $guid);
        }

        $this->_sort_attachments();
    }

    /**
     * This function sorts the attachment lists by filename.
     *
     * It has to be called after each attachment operation. It uses a
     * user-defined ordering function for each of the two arrays to be sorted:
     * sort_attachments_cmp() and _sort_attachments_info_callback().
     *
     * @access protected
     */
    function _sort_attachments()
    {
        // Sortable attachments should be already sorted in the correct order

        uasort($this->attachments,
            Array('midcom_helper_datamanager2_type_blobs', 'sort_attachments_cmp'));
        uasort($this->attachments_info,
            Array('midcom_helper_datamanager2_type_blobs', '_sort_attachments_info_callback'));
    }


    /**
     * Compares two attachments by score and name, to be used with the sorting routines
     *
     * See the usort() documentation for further details.
     *
     * @param midcom_db_attachment $a The first attachment.
     * @param midcom_db_attachment $b The second attachment.
     * @return int A value according to the rules from strcmp().
     */
    static public function sort_attachments_cmp($a, $b)
    {
        if ($a->metadata->score > $b->metadata->score)
        {
            return -1;
        }
        if ($a->metadata->score < $b->metadata->score)
        {
            return 1;
        }
        return strnatcasecmp($a->name, $b->name);
    }

    /**
     * User-defined array sorting callback, used for sorting $attachments_info.
     *
     * See the usort() documentation for further details.
     *
     * @access protected
     * @param Array $a The first attachment info array.
     * @param Array $b The second attachment info array.
     * @return int A value according to the rules from strcmp().
     */
    static function _sort_attachments_info_callback($a, $b)
    {
        return midcom_helper_datamanager2_type_blobs::sort_attachments_cmp($a['object'], $b['object']);
    }

    /**
     * This function will load a given attachment from the disk, and then calls
     * a function which updates the $attachments_info listing.
     *
     * @param string $identifier The identifier of the attachment to load.
     * @param string $guid The guid of the attachment to load.
     */
    function _load_attachment($identifier, $guid)
    {
        try
        {
            $attachment = new midcom_db_attachment($guid);
        }
        catch (midcom_error $e)
        {
            debug_add("Failed to load the attachment {$guid} from disk, aborting.", MIDCOM_LOG_INFO);
            $e->log();
            return;
        }

        $this->attachments[$identifier] = $attachment;
        $this->_update_attachment_info($identifier);
    }

    /**
     * Synchronizes the attachments info array with the attachment referenced by the
     * identifier.
     *
     * @param mixed $identifier The identifier of the attachment to update
     * @access protected
     */
    function _update_attachment_info($identifier)
    {
        // Shortcuts
        $att = $this->attachments[$identifier];
        $stats = $att->stat();

        $info = Array();
        $info['filename'] = $att->name;
        $info['description'] = $att->title;
        $info['mimetype'] = $att->mimetype;
        $name = urlencode($att->name);

        if ($GLOBALS['midcom_config']['attachment_cache_enabled'])
        {
            $subdir = substr($att->guid, 0, 1);
            if (file_exists("{$GLOBALS['midcom_config']['attachment_cache_root']}/{$subdir}/{$att->guid}_{$att->name}"))
            {
                // Attachment coming from the cache URL
                $info['url'] = "{$GLOBALS['midcom_config']['attachment_cache_url']}/{$subdir}/{$att->guid}_{$att->name}";
            }
        }

        if (!isset($info['url']))
        {
            // Uncached attachment served straight out of MidCOM
            if (   $this->storage->object
                && is_a($this->storage->object, 'midcom_db_topic'))
            {
                // Topic attachment, try to generate "clean" URL
                $nap = new midcom_helper_nav();
                $parent = $nap->resolve_guid($att->parentguid);
                if (   is_array($parent)
                    && $parent[MIDCOM_NAV_TYPE] == 'node')
                {
                    $info['url'] = midcom_connection::get_url('self') . $parent[MIDCOM_NAV_RELATIVEURL] . $name;
                }
            }
        }

        if (!isset($info['url']))
        {
            // Use regular MidCOM attachment server
            $info['url'] = "{$this->attachment_server_url}{$att->guid}/{$name}";
        }

        $info['id'] = $att->id;
        $info['guid'] = $att->guid;

        $info['filesize'] = $stats[7];
        $info['formattedsize'] = midcom_helper_misc::filesize_to_string($stats[7]);
        $info['lastmod'] = $stats[9];
        $info['isoformattedlastmod'] = strftime('%Y-%m-%d %T', $stats[9]);

        $this->_update_attachment_info_additional($info, $att);

        $info['object'] = $this->attachments[$identifier];
        $info['identifier'] = $identifier;

        $this->attachments_info[$identifier] = $info;
    }

    /**
     * Read additional information from the attachment and add to the information array
     *
     * With images, it evaluates reads image size information from parameters.
     *
     * @param &$info Information array
     * @param midcom_db_attachment $att Attachment to update information from
     */
    function _update_attachment_info_additional(&$info, $att)
    {
        $info['size_x'] = $att->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_x');
        $info['size_y'] = $att->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_y');
        $info['size_line'] = $att->get_parameter('midcom.helper.datamanager2.type.blobs', 'size_line');
    }

    function convert_to_storage()
    {
        // Synchronize the parameters again with the current attachment listing, just to
        // be on the safe side.
        $this->_save_attachment_listing();

        return '';
    }

    /**
     * This function synchronizes the attachment listing parameter of this field with the
     * current attachment state.
     */
    function _save_attachment_listing()
    {
        // Data that will be stored
        $data = array();
        // Count of attachments, used to calculate metadata->score
        $count = count($this->attachments);

        foreach ($this->attachments as $identifier => $attachment)
        {
            $attachment = $this->attachments[$identifier];
            if (!mgd_is_guid($attachment->guid))
            {
                unset($attachment);
                continue;
            }

            // Store score if is sortable and we have value
            if (   $this->sortable
                && isset($this->_sorted_list[$identifier]))
            {
                $score = $count - $this->_sorted_list[$identifier] + 1;
                // Store the attachment score
                $attachment->metadata->score = $score;
            }

            $data[] = "{$identifier}:{$attachment->guid}";
            unset($attachment);
        }

        // We need to be selective when saving, excluding one case: empty list
        // with empty storage object. In that case we store nothing. If we have
        // an object, we set the parameter unconditionally, to get all deletions.
        if ($this->storage->object)
        {
            $this->storage->object->set_parameter('midcom.helper.datamanager2.type.blobs', "guids_{$this->name}", implode(',', $data));
        }
        else if ($data)
        {
            debug_add('We were told to store attachment GUIDs, but no storage object was present. This should not happen, ignoring silently.',
                MIDCOM_LOG_WARN);
            debug_print_r('This data should have been stored:', $data);
        }
    }

    /**
     * Adds a new attachment based on a file on-disk.
     *
     * This is a wrapper for add_attachment_by_handle() which works with an existing
     * file based on its name, not handle. The file is deleted after successful processing,
     * unless you set the fourth parameter to false.
     *
     * This file version will automatically evaluate the file with getimagesize so that
     * convenience methods of them are available.
     *
     * @param string $identifier The identifier of the new attachment.
     * @param string $filename The filename to use after processing.
     * @param string $title The title of the attachment to use.
     * @param string $mimetype The MIME Type of the file.
     * @param string $tmpname The name of the source file.
     * @param boolean $autodelete Set this to true (the default) to automatically delete the
     *     file after successful processing.
     * @return boolean Indicating success.
     */
    function add_attachment($identifier, $filename, $title, $mimetype, $tmpname, $autodelete = true)
    {
        if (! file_exists($tmpname))
        {
            debug_add("Cannot add attachment, the file {$tmpname} was not found.", MIDCOM_LOG_INFO);
            return false;
        }

        if (!$this->file_sanity_checks($tmpname, $filename))
        {
            // the method will log errors and raise uimessages as needed
            return false;
        }

        // Ensure that the filename is URL safe (but allow multiple extensions)
        // PONDER: make use of this configurable in type-config ??
        $filename = midcom_helper_datamanager2_type_blobs::safe_filename($filename, false);

        $handle = @fopen($tmpname, 'r');
        if (! $handle)
        {
            debug_add("Cannot add attachment, could not open {$tmpname} for reading.", MIDCOM_LOG_INFO);
            if (isset($php_errormsg))
            {
                debug_add("Last PHP error was: {$php_errormsg}", MIDCOM_LOG_INFO);
            }
            return false;
        }
        if (! $this->add_attachment_by_handle($identifier, $filename, $title, $mimetype, $handle, true, $tmpname))
        {
            fclose($handle);
            debug_add('Failed to create attachment, see above for details.');
            return false;
        }

        if ($autodelete)
        {
            if (! @unlink($tmpname))
            {
                debug_add('Failed to automatically delete the source file, ignoring silently.', MIDCOM_LOG_WARN);
                if (isset($php_errormsg))
                {
                    debug_add("Last PHP error was: {$php_errormsg}", MIDCOM_LOG_WARN);
                }
            }
        }

        return true;
    }

    /**
     * This is a simple helper which looks at the original file and adds additional information to the
     * attachment.
     *
     * With images, it evaluates the imagesize information of a given file and adds that information as
     * parameters to the attachment identified by its identifier.
     *
     * @param string $identifier Attachment identifier
     * @param string $filename Original file name
     */
    function _set_attachment_info_additional($identifier, $filename)
    {
        $data = @getimagesize($filename);
        if ($data)
        {
            $this->attachments[$identifier]->parameter('midcom.helper.datamanager2.type.blobs', 'size_x', $data[0]);
            $this->attachments[$identifier]->parameter('midcom.helper.datamanager2.type.blobs', 'size_y', $data[1]);
            $this->attachments[$identifier]->parameter('midcom.helper.datamanager2.type.blobs', 'size_line', $data[3]);
            if (! $this->attachments[$identifier]->mimetype)
            {
                switch ($data[2])
                {
                    case 1:
                        $this->attachments[$identifier]->mimetype = 'image/gif';
                        $this->attachments[$identifier]->update();
                        break;

                    case 2:
                        $this->attachments[$identifier]->mimetype = 'image/jpeg';
                        $this->attachments[$identifier]->update();
                        break;

                    case 3:
                        $this->attachments[$identifier]->mimetype = 'image/png';
                        $this->attachments[$identifier]->update();
                        break;

                    case 6:
                        $this->attachments[$identifier]->mimetype = 'image/bmp';
                        $this->attachments[$identifier]->update();
                        break;

                    case 7:
                    case 8:
                        $this->attachments[$identifier]->mimetype = 'image/tiff';
                        $this->attachments[$identifier]->update();
                        break;
                }
            }
        }
    }


    /**
     * Adds a new attachment based on a file on-disk.
     *
     * This call will create a new attachment object based on the given file, add it to the
     * attachment list and synchronize all attachment operations. It works on an open file
     * handle, which is closed after successful processing, unless you set the forth parameter
     * to false.
     *
     * @param string $identifier The identifier of the new attachment.
     * @param string $filename The filename to use after processing.
     * @param string $title The title of the attachment to use.
     * @param string $mimetype The MIME Type of the file.
     * @param resource $source A file handle prepared to read of the the source file.
     * @param boolean $autoclose Set this to true if the file handle should automatically be closed
     *     after successful processing.
     * @param string $tmpfile In case you have a filename to the source handle, you should specify
     *     it here. It will be used to load getimagesize information directly (rather then doing a
     *     temporary copy). The default null indicates that the source file location is unknown.
     * @return boolean Indicating success.
     */
    function add_attachment_by_handle($identifier, $filename, $title, $mimetype, $source, $autoclose = true, $tmpfile = null)
    {
        if ( array_key_exists($identifier, $this->attachments))
        {
            debug_add("Failed to add the attachment record: The identifier '{$identifier}' is already in use.", MIDCOM_LOG_INFO);
            return false;
        }

        // Ensure that the filename is URL safe (but allow multiple extensions)
        // PONDER: make use of this configurable in type-config ??
        $filename = midcom_helper_datamanager2_type_blobs::safe_filename($filename, false);

        // Obtain a temporary object if necessary. This is the only place where this needs to be
        // done (all other I/O ops are logically behind the add operation).
        if (! $this->storage->object)
        {
            $this->storage->create_temporary_object();
        }

        $filename = $this->_generate_unique_name($filename);

        // Try to create a new attachment.
        $attachment = $this->storage->object->create_attachment($filename, $title, $mimetype);
        if (! $attachment)
        {
            debug_add('Failed to create attachment record, see above for details.', MIDCOM_LOG_INFO);
            return false;
        }

        $this->attachments[$identifier] = $attachment;
        $this->_save_attachment_listing();

        $attachment->copy_from_handle($source);

        if ($autoclose)
        {
            fclose($source);
        }

        $this->_store_att_map_parameters($identifier, $attachment);

        if ($tmpfile !== null)
        {
            $this->_set_attachment_info_additional($identifier, $tmpfile);
        }
        else
        {
            // TODO: needs create temporary copy function.
            _midcom_stop_request('TODO');
        }

        $this->_update_attachment_info($identifier);
        $this->_sort_attachments();
        $this->_index_attachment($attachment);

        return true;
    }

    /**
     * Make sure we have unique filename
     *
     * @todo Isn't there a reflector method that already does this (for filenames)?
     */
    private function _generate_unique_name($filename)
    {
        $attachment = new midcom_db_attachment();
        $attachment->name = $filename;
        $attachment->parentguid = $this->storage->object->guid;

        $_MIDCOM->componentloader->load('midcom.helper.reflector');
        $resolver = new midcom_helper_reflector_nameresolver($attachment);
        if ($resolver->name_is_unique())
        {
            return $filename;
        }
        debug_add("Name '{$attachment->name}' is not unique, trying to generate", MIDCOM_LOG_INFO);

        $ext_regex = '/^(.*)(\..*?)$/';
        if (preg_match($ext_regex, $filename, $ext_matches))
        {
            $name_woext = $ext_matches[1];
            $ext = $ext_matches[2];
            unset($ext_matches);
        }
        else
        {
            $name_woext = $filename;
            $ext = '';
        }
        unset($ext_regex);
        if (preg_match('/(.*?)-([0-9]{3,})$/', $name_woext, $name_matches))
        {
            // Name already has i and base parts, split them.
            $i = (int)$name_matches[2];
            $base_name = (string)$name_matches[1];
            unset($name_matches);
        }
        else
        {
            // Defaults
            $i = 1;
            $base_name = $name_woext;
        }
        $d = 100;
        do
        {
            if ($i > 1)
            {
                // Start suffixes from -002
                $attachment->name = $base_name . sprintf('-%03d', $i) . $ext;
            }

            debug_add("Trying with name '{$attachment->name}'");

            // Handle the decrementer
            --$d;
            if ($d < 1)
            {
                // Decrementer undeflowed
                debug_add("Maximum number of name tries exceeded, current name was: " . $attachment->name, MIDCOM_LOG_ERROR);
                return false;
            }
            // and the incrementer
            ++$i;
        }
        while (!$resolver->name_is_unique());
        unset($i, $d);
        return $attachment->name;
    }

    /**
     * Updates the title field of the specified attachment.
     *
     * This will automatically update the attachment info as well.
     *
     * @param string $identifier The identifier of the new attachment.
     * @param string $title The new title of the attachment, set this to null to
     *     keep the original title unchanged.
     * @return boolean Indicating success.
     */
    function update_attachment_title($identifier, $title)
    {
        if (! array_key_exists($identifier, $this->attachments))
        {
            debug_add("Failed to update the attachment title: The identifier {$identifier} is unknown", MIDCOM_LOG_INFO);
            return false;
        }

        $this->attachments[$identifier]->title = $title;
        if (! $this->attachments[$identifier]->update())
        {
            debug_add('Failed to update the attachment title: Last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }

        $this->_update_attachment_info($identifier);

        return true;
    }

    /**
     * Update an existing attachment with a new file (this keeps GUIDs stable).
     *
     * @param string $identifier The identifier of the attachment to update.
     * @param string $tmpname The name of the source file.
     * @param string $filename The filename to use after processing.
     * @param string $title The new title of the attachment, set this to null to
     *     keep the original title unchanged.
     * @param string $mimetype The new MIME Type of the file, set this to null to
     *     keep the original title unchanged. If you are unsure of the mime type,
     *     set this to '' not null, this will enforce a redetection.
     * @param boolean $autodelete Set this to true (the default) to automatically delete the
     *     file after successful processing.
     * @return boolean Indicating success.
     */
    function update_attachment($identifier, $filename, $title, $mimetype, $tmpname, $autodelete = true)
    {
        if (! file_exists($tmpname))
        {
            debug_add("Cannot add attachment, the file {$tmpname} was not found.", MIDCOM_LOG_INFO);
            return false;
        }

        if (!$this->file_sanity_checks($tmpname, $filename))
        {
            // the method will log errors and raise uimessages as needed
            return false;
        }

        $handle = @fopen($tmpname, 'r');
        if (! $handle)
        {
            debug_add("Cannot add attachment, could not open {$tmpname} for reading.", MIDCOM_LOG_INFO);
            if (isset($php_errormsg))
            {
                debug_add("Last PHP error was: {$php_errormsg}", MIDCOM_LOG_INFO);
            }
            return false;
        }

        if (! $this->update_attachment_by_handle($identifier, $filename, $title, $mimetype, $handle, true, $tmpname))
        {
            fclose($handle);
            debug_add('Failed to create attachment, see above for details.');
            return false;
        }

        if ($autodelete)
        {
            if (! @unlink($tmpname))
            {
                debug_add('Failed to automatically delete the source file, ignoring silently.', MIDCOM_LOG_WARN);
                if (isset($php_errormsg))
                {
                    debug_add("Last PHP error was: {$php_errormsg}", MIDCOM_LOG_WARN);
                }
            }
        }

        return true;
    }

    /**
     * Stores the field mapping info to the attachment object itself as well
     *
     * For now these are only used for backup purposes but in the future we'll move to use them as main source
     *
     * @param string $identifier identifier of the attachment
     * @param midgard_attachment &$attachment reference to the attachment object to operare on.
     */
    function _store_att_map_parameters($identifier, &$attachment)
    {
        $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'fieldname', $this->name);
        $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'identifier', $identifier);
    }

    /**
     * Update an existing attachment with a new file (this keeps GUIDs stable).
     *
     * @param string $identifier The identifier of the new attachment.
     * @param resource $handle A file handle prepared to read of the the source file.
     * @param string $title The new title of the attachment, set this to null to
     *     keep the original title unchanged.
     * @param string $mimetype The new MIME Type of the file, set this to null to
     *     keep the original title unchanged. If you are unsure of the mime type,
     *     set this to '' not null, this will enforce a redetection.
     * @param boolean $autoclose Set this to true if the file handle should automatically be closed
     *     after successful processing.
     * @param string $tmpfile In case you have a filename to the source handle, you should specify
     *     it here. It will be used to load getimagesize information directly (rather then doing a
     *     temporary copy). The default null indicates that the source file location is unknown.
     * @return boolean Indicating success.
     */
    function update_attachment_by_handle($identifier, $filename, $title, $mimetype, $source, $autoclose = true, $tmpfile = null)
    {
        if (! array_key_exists($identifier, $this->attachments))
        {
            debug_add("Failed to update the attachment record: The identifier {$identifier} is unknown", MIDCOM_LOG_INFO);
            return false;
        }

        $attachment = $this->attachments[$identifier];
        if ($title !== null)
        {
            $attachment->title = $title;
        }
        if ($mimetype !== null)
        {
            $attachment->mimetype = $mimetype;
        }

        $attachment->copy_from_handle($source);
        if ($autoclose)
        {
            fclose($source);
        }

        $attachment->title = $title;
        $attachment->name = $filename;
        if (! $attachment->update())
        {
            debug_add('Failed to update the attachment record.', MIDCOM_LOG_INFO);
            return false;
        }

        $this->_store_att_map_parameters($identifier, $attachment);

        if ($tmpfile !== null)
        {
            $this->_set_attachment_info_additional($identifier, $tmpfile);
        }
        else
        {
            // TODO: needs create temporary copy function.
            _midcom_stop_request('TODO');
        }

        $this->_update_attachment_info($identifier);
        $this->_sort_attachments();
        $this->_index_attachment($attachment);

        return true;
    }

    /**
     * Deletes an existing attachment.
     *
     * @param string $identifier The identifier of the attachment that should be deleted.
     * @return boolean Indicating success.
     */
    function delete_attachment($identifier)
    {
        if (!array_key_exists($identifier, $this->attachments))
        {
            debug_add('Failed to delete the attachment record: The identifier is unknown.', MIDCOM_LOG_INFO);
            return false;
        }

        $attachment = $this->attachments[$identifier];

        // Check that the attachment isn't connected elsewhere via DM2
        $qb = new midgard_query_builder('midgard_parameter');
        $qb->add_constraint('domain', '=', 'midcom.helper.datamanager2.type.blobs');
        $qb->add_constraint('name', '=', "guids_{$this->name}");
        $qb->add_constraint('value', 'LIKE', "%:{$attachment->guid}%");

        if ($qb->count() > 1)
        {
            debug_add("Attachment {$attachment->guid} is used also elsewhere by DM2, remove only the reference", MIDCOM_LOG_INFO);
        }
        else if (!$attachment->delete())
        {
            debug_add('Failed to delete the attachment record: DBA delete call returned false.', MIDCOM_LOG_INFO);
            return false;
        }

        unset($this->attachments[$identifier]);
        unset($this->attachments_info[$identifier]);
        $this->_sort_attachments();
        $this->_save_attachment_listing();

        return true;
    }

    /**
     * This call will unconditionally delete all attachments currently contained by the type.
     *
     * @return boolean Indicating success.
     */
    function delete_all_attachments()
    {
        foreach ($this->attachments as $identifier => $attachment)
        {
            if (!$this->delete_attachment($identifier))
            {
                return false;
            }
        }
        return true;
    }

    function convert_from_csv ($source)
    {
        // TODO: Not yet supported
        return '';
    }

    function convert_to_raw()
    {
        return $this->convert_to_csv();
    }

    function convert_to_csv()
    {
        $results = array();
        if ($this->attachments_info)
        {
            foreach ($this->attachments_info as $info)
            {
                $results[] = $info['url'];
            }
        }

        if (empty($results))
        {
            return '';
        }
        else
        {
            return implode(',', $results);
        }
    }

    function convert_to_html()
    {
        $result = '';
        if ($this->attachments_info)
        {
            $result .= "<ul>\n";
            foreach($this->attachments_info as $identifier => $info)
            {
                if (   $info['description']
                    && $info['description'] != $info['filename'])
                {
                    $title = "{$info['filename']} - {$info['description']}";
                }
                else
                {
                    $title = $info['filename'];
                }
                $result .= "<li><a href='{$info['url']}'>{$title}</a></li>\n";
            }
            $result .= "</ul>\n";
        }
        return $result;
    }

    /**
     * Internal helper function, determines the mime-type of the specified file.
     *
     * The call uses the "file" utility which must be present for this type to work.
     *
     * @param string $filename The file to scan
     * @return string The autodetected mime-type
     */
    function _get_mimetype($filename)
    {
        return exec("{$GLOBALS['midcom_config']['utility_file']} -ib {$filename} 2>/dev/null");
    }


    /**
     * Rewrite a filename to URL safe form
     *
     * @param string $filename file name to rewrite
     * @param boolean $force_single_extension force file to single extension (defaults to true)
     * @return string rewritten filename
     * @todo add possibility to use the file utility to determine extension if missing.
     */
    static function safe_filename($filename, $force_single_extension = true)
    {
        $filename = trim($filename);
        if ($force_single_extension)
        {
            $regex = '/^(.*)(\..*?)$/';
        }
        else
        {
            $regex = '/^(.*?)(\..*)$/';
        }
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
        return midcom_helper_misc::generate_urlname_from_string($name) . $ext;
    }

    /**
     * Creates a working copy to filesystem from given attachment object
     *
     * @param object $att the attachment object to copy
     * @return string tmp file name (or false on failure)
     */
    function create_tmp_copy($att)
    {
        $src = $att->open('r');
        if (!$src)
        {
            debug_add("Could not open attachment #{$att->id} for reading", MIDCOM_LOG_ERROR);
            return false;
        }
        $tmpname = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'midcom_helper_datamanager2_type_blobs_');
        $dst = fopen($tmpname, 'w+');
        if (!$dst)
        {
            debug_add("Could not open file '{$tmpname}' for writing", MIDCOM_LOG_ERROR);
            unlink($tmpname);
            return false;
        }
        while (! feof($src))
        {
            $buffer = fread($src, 131072); /* 128 kB */
            fwrite($dst, $buffer, 131072);
        }
        $att->close();
        fclose($dst);
        return $tmpname;
    }


    /**
     * Makes sanity checks on the uploaded file, used by add_attachment and update_attachment
     *
     * @see add_attachment
     * @see update_attachment
     * @param string $filepath path to file to check
     * @param string $filename actual name of the file to use with attachment
     * @return boolean indicating sanity
     */
    function file_sanity_checks($filepath, $filename = '')
    {
        static $checked_files = array();
        static $checks = array
        (
            'sizenotzero',
            'avscan',
        );
        // Do not check same file twice
        if (isset($checked_files[$filepath]))
        {
            return $checked_files[$filepath];
        }
        foreach ($checks as $check)
        {
            $methodname = "file_sanity_checks_{$check}";
            if (!$this->$methodname($filepath))
            {
                // the methods will log their own errors
                $checked_files[$filepath] = false;
                return false;
            }
        }
        $checked_files[$filepath] = true;
        return true;
    }

    /**
     * Make sure given file is larger than zero bytes
     *
     * @see file_sanity_checks
     * @return boolean indicating sanity
     */
    function file_sanity_checks_sizenotzero($filepath)
    {
        $size = filesize($filepath);
        if ($size == 0)
        {
            debug_add("filesize('{$filepath}') returned {$size} which evaluated to zero", MIDCOM_LOG_ERROR);

            $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), $this->_l10n->get('uploaded file has zero size'), 'error');

            return false;
        }
        return true;
    }

    /**
     * Scans the file for virii
     *
     * @see file_sanity_checks
     * @return boolean indicating sanity
     */
    function file_sanity_checks_avscan($filepath)
    {
        $scan_template = $this->_config->get('type_blobs_avscan_command');
        if (empty($scan_template))
        {
            // silently ignore if scan command not configured
            return true;
        }
        $scan_command = escapeshellcmd(sprintf($scan_template, $filepath));
        $scan_output = array();
        exec($scan_command, $scan_output, $exit_code);
        if ($exit_code !== 0)
        {
            // Scan command returned error (likely infected file);
            debug_add("{$scan_command} returned {$exit_code}, likely file is infected", MIDCOM_LOG_ERROR);
            debug_print_r('scanner_output', $scan_output, MIDCOM_LOG_ERROR);
            $_MIDCOM->uimessages->add($this->_l10n_midcom->get('midcom.helper.datamanager2'), $this->_l10n->get('virus found in uploaded file'), 'error');
            return false;
        }
        return true;
    }

    /**
     * helper function to index the attachment - also checks if it should be indexed
     */
    private function _index_attachment($attachment)
    {
        if (   $this->storage->object
            && $GLOBALS['midcom_config']['indexer_backend'])
        {
            $index_attachment = true;
            //check if there is an index_method set
            if (array_key_exists('index_method', $this->_datamanager->schema->fields[$this->name]))
            {
                // do not index the attachment for index_method attachment & noindex
                // for index_method=attachment the content of the attachment is stored in content of the object
                if (    $this->_datamanager->schema->fields[$this->name]['index_method'] == 'attachment'
                    ||  $this->_datamanager->schema->fields[$this->name]['index_method'] == 'noindex' )
                {
                    $index_attachment = false;
                }
            }
            if($index_attachment)
            {
                $document = new midcom_services_indexer_document_attachment($attachment, $this->storage->object);
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->index($document);
            }
        }
    }
}
?>