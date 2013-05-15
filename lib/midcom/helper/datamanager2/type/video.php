<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Video type.
 *
 * This type encapsulates a single uploaded image along with an optional number of
 * derived images like thumbnails. Both the main image and the derived thumbnails
 * will be ran through a defined filter chain. The originally uploaded file can be
 * kept optionally.
 *
 * The original image will be available under the "original" identifier unless
 * configured otherwise. The main image used for display is available as "main",
 * which will be ensured to be web-compatible. (This
 * distinction is important in case you upload TIFF or other non-web-compatible
 * images. All derived images will be available under the names defined in the schema
 * configuration.
 *
 * An optional "quick" thumbnail mode is available as well where you just specify the
 * maximum frame of a thumbnail to-be-generated. The auto-generated image will then be
 * available in the attachment identified as "thumbnail".
 *
 * The class uses the image filter system for the actual resizing and conversion work,
 * you need to specify all operations including resize operations in the filtering
 * chain declaration for the corresponding derived image.
 *
 * Regarding file type conversions: The original uploaded image will always be run through
 * an automatic type conversion as the very first step before any further processing is
 * done. From that point, no further type conversion is done unless the user specifies
 * another one in the filter chains for a derived type. A manual initial image conversion
 * is not yet supported by the type.
 *
 * All derived images will be computed from the initially converted, uploaded image,
 * which should minimize the losses of the subsequent conversions. This intermediate
 * image will not be kept an any place. The keep_original option will only save the
 * unmodified, uploaded file.
 *
 * The recreate_derived_images call will recreate all derived images from
 * the original image. If that image is not available, the generated main image is used
 * instead.
 *
 * Be aware that the type holds <i>no</i> safety code to guard against duplicate image
 * identifiers (e.g. defining a "main" image in the derived images list). The results
 * of such a configuration is undefined.
 *
 * <b>Available configuration options:</b>
 *
 * - boolean keep_original controls whether you want to keep the originally uploaded
 *   file available. This option is disabled by default.
 * - string filter_chain The filter chain used to render the main image. This chain
 *   empty (null) by default.
 * - Array derived_images A list of derived images to construct from the main image.
 *   This option consists of a list of identifier/filter chain declarations. They will
 *   be constructed in order, each using a fresh copy of the (initially type-converted)
 *   original image. This options may be null (the default) indicating no derived
 *   images. Note, that the system will detect any explicit image type conversions
 *   you're doing in a filter chain, setting the attachments' Mime-Type property
 *   automagically.
 * - Array auto_thumbnail This array holds the maximum size of the thumbnail to create
 *   with automatic defaults. The array holds a maximum width/height pair as first and
 *   second element of the array, nothing else. The image will be available as
 *   "thumbnail". This image will be constructed after constructing all explicitly
 *   defined derived images. This option may be null (the default) indicating no
 *   thumbnail.
 * - string output_mode Determines if $datamanager->convert_to_html() should return
 *   image as HTML tag ready to use (default) or array type containing all data about
 *   image and derived formats.
 *
 * <b>Implementation note:</b>
 *
 * Due to the fact that Imagemagick is used for most operations, this type is currently
 * only capable of operating based on actual files, not file handles.
 *
 * @todo Implement thumbnail interface.
 * @todo Operation on file handles.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_video extends midcom_helper_datamanager2_type_blobs
{
    /**
     * Set this to true to keep the original file available as "original".
     *
     * @var boolean
     */
    var $keep_original = false;

    /**
     * The filter chain to use to create the "main" image.
     *
     * @var string
     * @see midcom_helper_imagefilter
     */
    var $filter_chain = null;

    /**
     * The list of derived images to construct.
     *
     * @var array
     */
    var $derived_images = null;

    /**
     * The maximum width/height (in this order) of the thumbnail to be auto-created.
     *
     * The passed values will be given to the rescale function of the imagefilter.
     * This means that if you want to scale an image only by width, you still have
     * to set the height parameter to zero (auto_thumbnail => Array(100,0)).
     *
     * @var array
     */
    var $auto_thumbnail = null;

    /**
     * The video title entered by the user. Stored in each attachments
     * title field.
     *
     * @var string
     */
    var $title = '';
    var $title_video = '';

    /**
     * The original filename of the uploaded file.
     *
     * @var string
     */
    private $_filename = null;
    var $_filename_video = null;

    /**
     * The name of the original temporary uploaded file (which will already be converted
     * to a Web-Aware format).
     *
     * @var string
     */
    private $_original_tmpname = null;
    var $_original_tmpname_video = null;

    /**
     * The current working file.
     *
     * @var string
     */
    private $_current_tmpname = null;
    var $_current_tmpname_video = null;

    /**
     * The image-filter instance to use.
     *
     * @var midcom_helper_imagefilter
     */
    private $_filter = null;

    /**
     * The target mimetype used after automatic conversion for all
     * generated images.
     *
     * @var string
     */
    private $_target_mimetype = null;
    var $_target_mimetype_video = null;

    /**
     * The original mimetype of the uploaded file.
     *
     * @var string
     */
    private $_original_mimetype = null;
    var $_original_mimetype_video = null;

    /**
     * This list is used when updating an existing attachment. It keeps track
     * of which attachments have been updated already when replacing an existing
     * image. All attachments still listed here after a set_image call will
     * be deleted. This keeps attachment GUIDs stable during updates but also
     * adds resilience against against changed type configuration.
     *
     * @var Array
     */
    private $_pending_attachments = Array();
    var $_pending_attachments_video = Array();

    /**
     * This option taken from data schema controls the way data is output.
     * Either (default) preformatted <img> tag or plain array() type containing
     * object data
     *
     * @var string
     */
    var $output_mode = 'html';

    public function _on_initialize()
    {
        if (   (   !empty($this->auto_thumbnail)
                || !empty($this->filter_chain)
                || !empty($this->derived_images))
            && !midcom_helper_imagefilter::imagemagick_available())
        {
            throw new midcom_error('DM2 type image requires ImageMagick for manipulation operations, see debug log for details');
        }
        return parent::_on_initialize();
    }

    /**
     * recreates main image if original is available
     */
    function recreate_main_image()
    {
        if (!array_key_exists('original', $this->attachments))
        {
            // Allow main image only be recreated if we have original stored
            return false;
        }
        if (!$this->_prepare_recreate())
        {
            return false;
        }
        if (   !$this->_filter->set_file($this->_original_tmpname)
            || !$this->_auto_convert_to_web_type())
        {
            unlink ($this->_original_tmpname);
            return false;
        }
        $ret = $this->_save_main_image();
        if (!$ret)
        {
            unlink ($this->_original_tmpname);
            return false;
        }
        $this->recreate_derived_images(false);
        return $ret;
    }

    /**
     * Recreates derived images
     */
    function recreate_derived_images($force_prepare = true)
    {
        if (!$this->_prepare_recreate($force_prepare))
        {
            return false;
        }
        // Call the appropriate methods
        $this->_add_thumbnail_to_derived_images();
        $ret = $this->_save_derived_images();
        unlink ($this->_original_tmpname);
        // Clear up all attachments no longer in use
        if ($ret)
        {
            if (array_key_exists('main', $this->_pending_attachments))
            {
                unset($this->_pending_attachments['main']);
            }
            $this->_clean_pending_attachments();
        }
        return $ret;
    }

    /**
     * Preparation operations for recreate_xxx()
     */
    function _prepare_recreate($force = false)
    {
        if (   !empty($this->_original_tmpname)
            && !$force)
        {
            // We have prepared.
            return true;
        }
        // Prepare internal members
        $this->_filter = new midcom_helper_imagefilter();
        // PHP5-TODO: Must be copy-by-value
        $this->_pending_attachments = $this->attachments;
        // Remove original and main from pending attachments
        if (   $this->keep_original
            && array_key_exists('original', $this->_pending_attachments))
        {
            unset($this->_pending_attachments['original']);
        }
        if (array_key_exists('original', $this->attachments))
        {
            // Copy original as original
            $src = $this->attachments['original']->open('r');
        }
        else
        {
            // Copy main as original
            $src = $this->attachments['main']->open('r');
        }
        // Create tmp file and copy by handles
        $this->_original_tmpname = tempnam(midcom::get('config')->get('midcom_tempdir'), "midcom_helper_datamanager2_type_image");
        $dst = fopen($this->_original_tmpname, 'w+');
        if (   !$src
            || !$dst)
        {
            // TODO: Error reporting
            return false;
        }
        while (! feof($src))
        {
            $buffer = fread($src, 131072); /* 128 kB */
            fwrite($dst, $buffer, 131072);
        }
        fclose($src);
        fclose($dst);
        $this->title = $this->attachments['main']->title;
        $this->_filename = $this->attachments['main']->name;
        $this->_original_mimetype = midcom_helper_misc::get_mimetype($this->_original_tmpname);
        return true;
    }

    /**
     * Rotates applies a rotational filter to all images in the type
     *
     * @param string $direction direction to rotate to
     * @return boolean indicating success/failure
     */
    function rotate($direction)
    {
        $filter = $this->_rotate_get_filter($direction);
        if ($filter === false)
        {
            return false;
        }
        return $this->apply_filter_all($filter);
    }

    /**
     * Converts given direction to imagefilter chain
     */
    function _rotate_get_filter($direction)
    {
        switch (strtolower($direction))
        {
            case 'left':
                // PONDER: shouldn't this be -90 ??
                $filter = 'rotate(270)';
                break;
            case 'right':
                $filter = 'rotate(90)';
                break;
            /* TODO: implement these as well
            case 'mirror':
                $filter = '';
                break;
            case 'flip':
                $filter = '';
                break;
            */
            default:
                debug_add("Given rotate direction '{$direction}' is not supported", MIDCOM_LOG_ERROR);
                return false;
        }
        return $filter;
    }

    /**
     * Applies a given filter to all (except original) images in the type
     *
     * @param string $filter the midcom_helper_imagefilter filter chain to apply
     * @return boolean indicating success/failure
     */
    function apply_filter_all($filter)
    {
        foreach ($this->attachments as $identifier => $image)
        {
            if ($identifier === 'original')
            {
                continue;
            }
            if (!$this->apply_filter($identifier, $filter))
            {
                debug_add("Failed to apply filter '{$filter}' to image '{$identifier}', aborting", MIDCOM_LOG_ERROR);
                return false;
            }
        }
        return true;
    }

    /**
     * Applies a filter to image identifier
     *
     * @param string $identifier the image identifier to apply to
     * @param string $filter the midcom_helper_imagefilter filter chain to apply
     * @return boolean indicating success/failure
     */
    function apply_filter($identifier, $filter)
    {
        if (!midcom_helper_imagefilter::imagemagick_available())
        {
            return false;
        }

        if (!array_key_exists($identifier, $this->attachments))
        {
            debug_add("identifier '{$identifier}' not found", MIDCOM_LOG_ERROR);
            return false;
        }

        $image = $this->attachments[$identifier];
        $this->_filter = new midcom_helper_imagefilter($image);
        if (!$this->_filter->process_chain($filter))
        {
            debug_add("Failed to process filter chain '{$filter}', aborting", MIDCOM_LOG_ERROR);
            // Clean up
            $this->_filter = null;
            return false;
        }

        if (!$this->update_image_from_file($identifier))
        {
            debug_add("Failed to update image '{$identifier}' from file '" . $this->_filter->get_file() . "', aborting", MIDCOM_LOG_ERROR);
            // Clean up
            $this->_filter = null;
            return false;
        }

        // Don't leave the filter object laying
        $this->_filter = null;

        debug_add("Applied filter '{$filter}' to image '{$identifier}'", MIDCOM_LOG_INFO);
        return true;
    }

    /**
     * Overwrites image content from file, recalculates size etc
     *
     * @param string $identifier image identifier to update
     * @return boolean indicating success/failure
     */
    function update_image_from_file($identifier)
    {
        if (!$this->_filter->write($this->attachments[$identifier]))
        {
            return false;
        }

        // Failing these is bad, but it's too late now that we already have overwritten the actual image data...
        $this->_set_attachment_info_additional($identifier, $this->_filter->get_file());
        $this->_update_attachment_info($identifier);
        return true;
    }

    /**
     * Public access wrapper for _set_image
     *
     * @param string $filename The name of the image attachment to be created.
     * @param string $tmpname The file to load.
     * @param string $title The title of the image.
     * @param boolean $autodelete If this is true (the default), the temporary file will
     *     be deleted after postprocessing and attachment-creation.
     * @return boolean Indicating success.
     */
    function set_image($filename, $tmpname, $title, $autodelete = true)
    {
        if (empty($filename))
        {
            debug_add("filename must not be empty", MIDCOM_LOG_ERROR);
            return false;
        }

        // Ensure that the filename is URL safe and contains only one extension
        $filename = midcom_db_attachment::safe_filename($filename, true);

        return $this->_set_image($filename, $tmpname, $title, $autodelete);
    }

    function set_video($filename, $tmpname, $title, $autodelete = true)
    {
        if (empty($filename))
        {
            debug_add("filename must not be empty", MIDCOM_LOG_ERROR);
            return false;
        }
        // Ensure that the filename is URL safe and contains only one extension
        $filename = midcom_db_attachment::safe_filename($filename, true);

        return $this->_set_video($filename, $tmpname, $title, $autodelete);
    }

    /**
     * Adds the image to the type. Loads and processes the $tmpname file on disk.
     *
     * @param string $filename The name of the image attachment to be created.
     * @param string $tmpname The file to load.
     * @param string $title The title of the image.
     * @param boolean $autodelete If this is true (the default), the temporary file will
     *     be deleted after postprocessing and attachment-creation.
     * @return boolean Indicating success.
     */
    private function _set_image($filename, $tmpname, $title, $autodelete = true)
    {
        // Prepare Internal Members
        $this->title = $title;
        $this->_filename = $filename;
        $this->_original_tmpname = $tmpname;
        $this->_original_mimetype = midcom_helper_misc::get_mimetype($this->_original_tmpname);

        $this->_filter = new midcom_helper_imagefilter();

        // 1st step: original image storage and auto-conversion
        if (   ! $this->_save_original()
            || ! $this->_filter->set_file($this->_original_tmpname)
            || ! $this->_auto_convert_to_web_type())
        {
            debug_add("Failed to process the conversion batch 1 (save original & web conversion) for
        the uploaded file {$filename} in {$tmpname}, aborting type processing.",
                MIDCOM_LOG_ERROR);

            // Clean up
            $this->delete_all_attachments();
            return false;
        }

        // Prepare all other images
        if (   ! $this->_save_main_image()
            || ! $this->_add_thumbnail_to_derived_images()
            || ! $this->_save_derived_images()) // This fucker returns false
        {
            debug_add("Failed to process the conversion batch 2 (derived images) for the uploaded file {$filename} in {$tmpname}, aborting type processing.",
                MIDCOM_LOG_ERROR);

            // Clean up
            $this->delete_all_attachments();
            return false;
        }

        // Clear up all attachments no longer in use:
        $this->_clean_pending_attachments();

        if ($autodelete)
        {
            unlink ($this->_original_tmpname);
        }

        return true;
    }

    private function _set_video($filename, $tmpname, $title, $autodelete = true)
    {
        // Prepare Internal Members
        $this->title_video = $title;
        $this->_filename_video = $filename;
        $this->_original_tmpname_video = $tmpname;
        $this->_original_mimetype_video = midcom_helper_misc::get_mimetype($this->_original_tmpname_video);

        // 1st step: original image storage and auto-conversion
        if (   ! $this->_save_original_video())
        {
            debug_add("Failed to save original video {$filename} in {$tmpname}, aborting.", MIDCOM_LOG_ERROR);

            // Clean up
            $this->delete_all_attachments();

            return false;
        }

        // Prepare all other images
        if (!$this->_save_main_video())
        {
            debug_add("Failed to save uploaded video {$filename} in {$tmpname}, aborting.", MIDCOM_LOG_ERROR);

            // Clean up
            $this->delete_all_attachments();

            return false;
        }

        // Clear up all attachments no longer in use:
        $this->_clean_pending_attachments_video();

        if ($autodelete)
        {
            unlink ($this->_original_tmpname_video);
        }

        return true;
    }

    function _clean_pending_attachments()
    {
        foreach ($this->_pending_attachments as $identifier => $attachment)
        {
            $this->delete_attachment($identifier);
        }
    }

    function _clean_pending_attachments_video()
    {
        foreach ($this->_pending_attachments_video as $identifier => $attachment)
        {
            $this->delete_attachment($identifier);
        }
    }

    function delete_screenshot()
    {
        $this->delete_attachment('main');
    }

    /**
     * Small internal helper function. It adds a derived 'thumbnail' image to the list
     * used if and only if the auto_thumbnail option is set. Any existing thumbnail
     * declaration will be silently overwritten!
     *
     * @return boolean Indicating success.
     */
    function _add_thumbnail_to_derived_images()
    {
        if ($this->auto_thumbnail)
        {
            if (! $this->derived_images)
            {
                $this->derived_images = Array();
            }
            $this->derived_images['thumbnail'] = "resize({$this->auto_thumbnail[0]},{$this->auto_thumbnail[1]})";
        }

        return true;
    }

    /**
     * This loops over the defined derived images (if any) and constructs
     * each of them in turn.
     *
     * @return boolean Indicating success.
     */
    function _save_derived_images()
    {
        if ($this->derived_images)
        {
            foreach ($this->derived_images as $identifier => $filter_chain)
            {
                // PONDER: Shouldn't the derived images be derived from the 'main' image (now derived from original) ??
                $result = $this->_save_derived_image($identifier);
                @unlink($this->_current_tmpname);
                if (! $result)
                {
                    return false;
                }
            }
        }
        return true;
    }

    function _save_derived_videos()
    {
        if ($this->derived_videos)
        {
            foreach ($this->derived_videoss as $identifier => $filter_chain)
            {
                // PONDER: Shouldn't the derived images be derived from the 'main' image (now derived from original) ??
                $result = $this->_save_derived_image($identifier);
                @unlink($this->_current_tmpname);
                if (! $result)
                {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * This is the actual code which filters and saves a derived image.
     *
     * @param string $identifier The derived image to construct.
     * @return boolean Indicating success
     */
    function _save_derived_image($identifier)
    {
        $this->_current_tmpname = $this->_filter->create_tmp_copy($this->_original_tmpname);

        if (   !$this->_filter->set_file($this->_current_tmpname)
            || !$this->_filter->process_chain($this->derived_images[$identifier]))
        {
            return false;
        }

        if (isset($this->_identifier))
        {
            // we come from the image*s* type
            $blob_identifier = "{$this->_identifier}{$identifier}";
            $title = $this->_title;
        }
        else
        {
            $blob_identifier = $identifier;
            $title = $this->title;
        }

        if (array_key_exists($identifier, $this->_pending_attachments))
        {
            unset($this->_pending_attachments[$identifier]);
            return $this->update_attachment($blob_identifier,
                                            "{$identifier}_{$this->_filename}",
                                            $title,
                                            midcom_helper_misc::get_mimetype($this->_current_tmpname),
                                            $this->_current_tmpname,
                                            false);
        }
        if (isset($this->_attachment_map))
        {
            $this->_attachment_map[$blob_identifier] = Array($this->_identifier, $identifier);
        }
        return $this->add_attachment($blob_identifier,
                                     "{$identifier}_{$this->_filename}",
                                     $title,
                                     midcom_helper_misc::get_mimetype($this->_current_tmpname),
                                     $this->_current_tmpname,
                                     false);
    }

    /**
     * Saves the main image to the type, doing transformation work if configured to do so.
     *
     * @return boolean Indicating success.
     */
    function _save_main_image()
    {
        $this->_current_tmpname = $this->_filter->create_tmp_copy($this->_original_tmpname);

        if (!$this->_filter->set_file($this->_current_tmpname))
        {
            return false;
        }

        if (isset($this->_identifier))
        {
            // we come from the image*s* type
            $blob_identifier = "{$this->_identifier}main";
            $title = $this->_title;
        }
        else
        {
            $blob_identifier = 'main';
            $title = $this->title;
        }

        $result = true;

        // Filter if necessary.
        if (   $this->filter_chain
            && ! $this->_filter->process_chain($this->filter_chain))
        {
            $result = false;
        }

        if ($result)
        {
            if (array_key_exists('main', $this->_pending_attachments))
            {
                unset($this->_pending_attachments['main']);
                $result = $this->update_attachment($blob_identifier,
                                                   $this->_filename,
                                                   $title,
                                                   $this->_target_mimetype,
                                                   $this->_current_tmpname,
                                                   false);
        }
            else
            {
                $this->delete_attachment($blob_identifier);

                if (isset($this->_attachment_map))
                {
                    $this->_attachment_map[$blob_identifier] = Array($this->_identifier, 'main');
                }
                $result = $this->add_attachment($blob_identifier,
                                                $this->_filename,
                                                $title,
                                                $this->_target_mimetype,
                                                $this->_current_tmpname,
                                                false);
            }
        }
        @unlink($this->_current_tmpname);

        return $result;
    }

    function _save_main_video()
    {
        $this->_current_tmpname_video = $this->_filter->create_tmp_copy($this->_original_tmpname_video);

        if (isset($this->_identifier))
        {
            // we come from the video*s* type
            $blob_identifier = "{$this->_identifier}main_video";
            $title = $this->_title_video;
        }
        else
        {
            $blob_identifier = 'main_video';
            $title = $this->title_video;
        }

        $result = true;

        if ($result)
        {
            if (array_key_exists('main_video', $this->_pending_attachments_video))
            {
                unset($this->_pending_attachments_video['main_video']);
                $result = $this->update_attachment($blob_identifier,
                                                   $this->_filename_video,
                                                   $title,
                                                   $this->_target_mimetype_video,
                                                   $this->_current_tmpname_video,
                                                   false);
            }
            else
            {
                $this->delete_attachment($blob_identifier);

                if (isset($this->_attachment_map))
                {
                    $this->_attachment_map[$blob_identifier] = Array($this->_identifier, 'main_video');
                }

                $result = $this->add_attachment($blob_identifier,
                                                $this->_filename_video,
                                                $title,
                                                $this->_target_mimetype_video,
                                                $this->_current_tmpname_video,
                                                false);
            }
        }
        @unlink($this->_current_tmpname_video);

        return $result;
    }

    /**
     * If we are configured to do so, we save the original image.
     *
     * @return boolean Indicating success
     */
    function _save_original()
    {
        if (!$this->keep_original)
        {
            return true;
        }
        if (isset($this->_identifier))
        {
            // we come from the image*s* type
            $blob_identifier = "{$this->_identifier}original";
            $title = $this->_title;
        }
        else
        {
            $blob_identifier = 'original';
            $title = $this->title;
        }

        if (array_key_exists('original', $this->_pending_attachments))
        {
            unset($this->_pending_attachments['original']);
            return $this->update_attachment($blob_identifier,
                                            "original_{$this->_filename}",
                                            $title,
                                            $this->_original_mimetype,
                                            $this->_original_tmpname,
                                            false);
        }
        if (isset($this->_attachment_map))
        {
            $this->_attachment_map[$blob_identifier] = Array($this->_identifier, 'original');
        }
        return $this->add_attachment($blob_identifier,
                                     "original_{$this->_filename}",
                                     $title,
                                     $this->_original_mimetype,
                                     $this->_original_tmpname,
                                     false);
    }

    function _save_original_video()
    {
        if (!$this->keep_original)
        {
            return true;
        }
        if (isset($this->_identifier))
        {
            // we come from the videos*s* type
            $blob_identifier = "{$this->_identifier}original_video";
            $title = $this->_title_video;
        }
        else
        {
            $blob_identifier = 'original_video';
            $title = $this->title_video;
        }

        if (array_key_exists('original_video', $this->_pending_attachments_video))
        {
            unset($this->_pending_attachments_video['original_video']);
            return $this->update_attachment($blob_identifier,
                                            "original_{$this->_filename_video}",
                                            $title,
                                            $this->_original_mimetype_video,
                                            $this->_original_tmpname_video,
                                            false);
        }
        if (isset($this->_attachment_map))
        {
            $this->_attachment_map[$blob_identifier] = Array($this->_identifier, 'original');
        }
        return $this->add_attachment($blob_identifier,
                                     "original_{$this->_filename_video}",
                                     $title,
                                     $this->_original_mimetype_video,
                                     $this->_original_tmpname_video,
                                     false);
    }

    /**
     * Automatically convert the uploaded file to a web-compatible type. Uses
     * only the first image of multi-page uploads (like PDFs) and populates the
     * _target_mimetype member accordingly. The original_tmpname file is manipulated
     * directly.
     *
     * Uploaded GIF, PNG and JPEG files are left untouched.
     *
     * In case of any conversions being done, the new extension will be appended
     * to the uploaded file.
     *
     * @return boolean Indicating success
     */
    function _auto_convert_to_web_type()
    {
        debug_add("\$this->_original_mimetype: {$this->_original_mimetype}");
        switch ($this->_original_mimetype)
        {
            case 'image/png':
            case 'image/gif':
            case 'image/jpeg':
                $this->_target_mimetype = $this->_original_mimetype;
                $conversion = null;
                break;

            case 'application/postscript':
            case 'application/pdf':
                $this->_target_mimetype = 'image/png';
                $conversion = 'png';
                break;

            default:
                $this->_target_mimetype = 'image/jpeg';
                $conversion = 'jpg';
                break;
        }
        debug_add("\$conversion={$conversion}");

        if (empty($conversion))
        {
            return true;
        }

        if (!midcom_helper_imagefilter::imagemagick_available())
        {
            throw new midcom_error('DM2 type image requires ImageMagick for manipulation operations, see debug log for details');
        }
        // PONDER: Make sure there is only one extension on the file ??
        $this->_filename .= ".{$conversion}";
        return $this->_filter->convert($conversion);
    }

    /**
     * Calls the base type to read all attachments, then extracts the title of the
     * main attachment, if applicable.
     */
    function convert_from_storage($source)
    {
        parent::convert_from_storage($source);

        if (array_key_exists('main', $this->attachments))
        {
            $this->title = $this->attachments['main']->title;
        }
    }

    /**
     * Updates the attachment titles.
     */
    function convert_to_storage()
    {
        foreach ($this->attachments as $identifier => $copy)
        {
            $this->update_attachment_title($identifier, $this->title);
        }

        return parent::convert_to_storage();
    }

    /**
     * The HTML-Version of the image type can take two forms, depending on
     * type configuration:
     *
     * 1. If an 'thumbnail' image is present, it is shown and encapsulated in an
     *    anchor tag leading to the 'main' image.
     * 2. If no 'thumbnail' image is present, the 'main' image is shown
     *    directly, without any anchor.
     *
     * In case that there is no image uploaded, an empty string is returned.
     */
    function convert_to_html()
    {
        $result = '';

        if (!$this->attachments_info)
        {
            return $result;
        }
        switch ($this->output_mode)
        {
            case 'html':
                $main = $this->attachments_info['main'];
                $title = ($main['description']) ? $main['description'] : $main['filename'];
                $link_url = $main['url'];
                $image_properties = 'src="' .  $main['url'] . '" ' . $main['size_line'];
                $render_link = false;

                if (array_key_exists('thumbnail', $this->attachments_info))
                {
                    $thumb = $this->attachments_info['thumbnail'];
                    $image_properties = 'src="' .  $thumb['url'] . '" ' . $thumb['size_line'];
                    $render_link = true;
                }
                if (array_key_exists('main_video', $this->attachments_info))
                {
                    $video = $this->attachments_info['main_video'];
                    $link_url = $video['url'];
                    $render_link = true;
                }
                if ($render_link)
                {
                    $result .= "<a href='{$link_url}'><img {$image_properties} alt='{$title}' title='{$title}' /></a>";
                }
                else
                {
                    $result .= "<img {$image_properties} alt='{$title}' title='{$title}' />";
                }
                break;
            // FIXME: wouldn't it be better to access $dm2->types->imagefield->attachments_info ??
            case 'array':
                $result = array();
                $tmp = $images['main'];
                if (array_key_exists('thumbnail', $this->attachments_info))
                {
                    $tmp['thumbnail'] = $images['thumbnail'];
                }
                if (array_key_exists('main_video', $this->attachments_info))
                {
                    $tmp['main_video'] = $images['main_video'];
                }
                $result = $tmp;
                break;
        }

        return $result;
    }
}
?>