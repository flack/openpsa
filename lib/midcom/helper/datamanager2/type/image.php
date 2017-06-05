<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;

/**
 * Datamanager 2 Image type.
 *
 * This type encapsulates a single uploaded image along with an optional number of
 * derived images like thumbnails. Both the main image and the derived thumbnails
 * will be run through a defined filter chain. The originally uploaded file can be
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
class midcom_helper_datamanager2_type_image extends midcom_helper_datamanager2_type_blobs
{
    /**
     * Set this to true to keep the original file available as "original".
     *
     * @var boolean
     */
    public $keep_original = false;

    /**
     * The max filesize (in kb)
     *
     * @var int
     */
    public $max_filesize = null;

    /**
     * The filter chain to use to create the "main" image.
     *
     * @var string
     * @see midcom_helper_imagefilter
     */
    public $filter_chain = null;

    /**
     * The list of derived images to construct.
     *
     * @var array
     */
    public $derived_images = [];

    /**
     * The maximum width/height (in this order) of the thumbnail to be auto-created.
     *
     * The passed values will be given to the rescale function of the imagefilter.
     * This means that if you want to scale an image only by width, you still have
     * to set the height parameter to zero (auto_thumbnail => Array(100,0)).
     *
     * @var array
     */
    public $auto_thumbnail = [];

    /**
     * The image title entered by the user. Stored in each attachment's title field.
     *
     * @var string
     */
    public $title = '';

    /**
     * The original filename of the uploaded file.
     *
     * @var string
     */
    protected $_filename = null;

    /**
     * The current image identifier to use when operating on images.
     *
     * @var string
     */
    protected $_identifier = null;

    /**
     * The name of the original temporary uploaded file (which will already be converted
     * to a Web-Aware format).
     *
     * @var string
     */
    protected $_original_tmpname = null;

    /**
     * The image-filter instance to use.
     *
     * @var midcom_helper_imagefilter
     */
    protected $_filter = null;

    /**
     * This list is used when updating an existing attachment. It keeps track
     * of which attachments have been updated already when replacing an existing
     * image. All attachments still listed here after a set_image call will
     * be deleted. This keeps attachment GUIDs stable during updates but also
     * adds resilience against changed type configuration.
     *
     * @var Array
     */
    protected $_pending_attachments = null;

    /**
     * This option taken from data schema controls the way data is output.
     * Either (default) preformatted <img> tag or plain array() type containing
     * object data
     *
     * @var string
     */
    var $output_mode = 'html';

    /**
     * Whether this instance is handling single image or multiple images
     *
     * @var string
     */
    protected $_instance_mode = 'single';

    /**
     * The QF upload form element, used for processing.
     *
     * @var HTML_QuickForm_file
     */
    public $uploaded_file;

    /**
     * indicate whether the validation method has run before as it might be run multiple times
     *
     * @var boolean
     */
    private $_validation_done = false;

    function imagemagick_available($raise_uimessage = false)
    {
        if (!$this->_config->get('verify_imagemagick')) {
            return true;
        }

        $ret = midcom_helper_imagefilter::imagemagick_available();
        if (!$ret && $raise_uimessage) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), 'ImageMagick is required but seems not to be available, image fields may be disabled', 'error');
        }

        return $ret;
    }

    /**
     * Recreate all images
     */
    public function recreate()
    {
        if (!array_key_exists('original', $this->attachments)) {
            // Allow main image only be recreated if we have original stored
            debug_add("Image {$this->name} has no 'original' image, recreating derived images only.", MIDCOM_LOG_INFO);
            return $this->recreate_derived_images();
        }

        return $this->recreate_main_image();
    }

    /**
     * recreates main image
     */
    function recreate_main_image()
    {
        if (   !$this->_prepare_recreate()
            || !$this->_auto_convert_to_web_type()
            || !$this->_save_image('main', $this->filter_chain)) {
            return false;
        }
        return $this->recreate_derived_images(false);
    }

    /**
     * Recreates derived images
     */
    function recreate_derived_images($force_prepare = true)
    {
        if (!$this->_prepare_recreate($force_prepare)) {
            return false;
        }
        // Call the appropriate methods
        $this->_add_thumbnail_to_derived_images();
        $ret = $this->_save_derived_images();
        unlink($this->_original_tmpname);
        // Clear up all attachments no longer in use
        if ($ret) {
            unset($this->_pending_attachments['main']);
            $this->_clean_pending_attachments();
        }
        return $ret;
    }

    /**
     * Preparation operations for recreate_xxx()
     */
    protected function _prepare_recreate($force = false)
    {
        if (   (   !empty($this->auto_thumbnail)
                || !empty($this->filter_chain)
                || !empty($this->derived_images))
            && !$this->imagemagick_available()) {
            throw new midcom_error( 'DM2 type image requires ImageMagick for manipulation operations, see debug log for details');
        }

        if (   !empty($this->_original_tmpname)
            && !$force) {
            // We have prepared.
            return true;
        }
        if (array_key_exists('original', $this->attachments)) {
            // Copy original as original
            $att = $this->attachments['original'];
        } elseif (array_key_exists('main', $this->attachments)) {
            // Copy main as original
            $att = $this->attachments['main'];
        } else {
            return false;
        }
        $this->_filter = new midcom_helper_imagefilter($att);
        $this->_pending_attachments = $this->attachments;
        if ($this->keep_original) {
            unset($this->_pending_attachments['original']);
        }

        $this->title = $this->attachments['main']->title;
        $this->_filename = $this->attachments['main']->name;
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
        switch (strtolower($direction)) {
            case 'left':
                // PONDER: shouldn't this be -90 ??
                $filter = 'rotate(270)';
                break;
            case 'right':
                $filter = 'rotate(90)';
                break;
            default:
                debug_add("Given rotate direction '{$direction}' is not supported", MIDCOM_LOG_ERROR);
                return false;
        }
        return $this->apply_filter_all($filter);
    }

    /**
     * Applies a given filter to all (except original) images in the type
     *
     * @param string $filter the midcom_helper_imagefilter filter chain to apply
     * @return boolean indicating success/failure
     */
    function apply_filter_all($filter)
    {
        foreach (array_keys($this->attachments) as $identifier) {
            if ($identifier === 'original') {
                continue;
            }
            if (!$this->apply_filter($identifier, $filter)) {
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
     * @param string $filterchain the midcom_helper_imagefilter filter chain to apply
     * @return boolean indicating success/failure
     */
    function apply_filter($identifier, $filterchain)
    {
        if (!$this->imagemagick_available()) {
            return false;
        }

        if (!array_key_exists($identifier, $this->attachments)) {
            debug_add("identifier '{$identifier}' not found", MIDCOM_LOG_ERROR);
            return false;
        }

        $filter = new midcom_helper_imagefilter($this->attachments[$identifier]);
        try {
            $filter->process_chain($filterchain);
        } catch (midcom_error $e) {
            midcom::get()->uimessages->add('midcom.helper.imagefilter', $e->getMessage(), 'error');
            $e->log();
            return false;
        }

        if (!$filter->write($this->attachments[$identifier])) {
            debug_add("Failed to update image '{$identifier}', aborting", MIDCOM_LOG_ERROR);
            return false;
        }

        // Failing these is bad, but it's too late now that we already have overwritten the actual image data...
        $this->_set_attachment_info_additional($this->attachments[$identifier], $filter->get_file());
        $this->_update_attachment_info($identifier);

        debug_add("Applied filter '{$filterchain}' to image '{$identifier}'", MIDCOM_LOG_INFO);
        return true;
    }

    /**
     * Public access wrapper for _set_image
     *
     * @param string $filename The name of the image attachment to be created.
     * @param string $tmpname The file to load.
     * @param string $title The title of the image.
     * @return boolean Indicating success.
     */
    function set_image($filename, $tmpname, $title)
    {
        return $this->_set_image($filename, $tmpname, $title);
    }

    /**
     * Adds the image to the type. Loads and processes the $tmpname file on disk.
     *
     * @param string $filename The name of the image attachment to be created.
     * @param string $tmpname The file to load.
     * @param string $title The title of the image.
     * @param array $force_pending_attachments use this to override pending_attachments (when run from images type)
     * @return boolean Indicating success.
     */
    protected function _set_image($filename, $tmpname, $title, $force_pending_attachments = false)
    {
        if (   (   !empty($this->auto_thumbnail)
                || !empty($this->filter_chain)
                || !empty($this->derived_images))
            && !$this->imagemagick_available()) {
            throw new midcom_error( 'DM2 type image requires ImageMagick for manipulation operations, see debug log for details');
        }

        if (empty($filename)) {
            debug_add("filename must not be empty", MIDCOM_LOG_ERROR);
            return false;
        }

        // Ensure that the filename is URL safe and contains only one extension
        $filename = midcom_db_attachment::safe_filename($filename, true);

        if ($force_pending_attachments === false) {
            $this->_pending_attachments = $this->attachments;
        } else {
            $this->_pending_attachments = $force_pending_attachments;
        }

        // Prepare Internal Members
        $this->title = $title;
        $this->_filename = $filename;
        $this->_filter = new midcom_helper_imagefilter;
        $this->_original_tmpname = $tmpname;

        // 1st step: original image storage and auto-conversion
        if (   !$this->_save_original()
            || !$this->_filter->set_file($tmpname)
            || !$this->_auto_convert_to_web_type()) {
            // TODO: Raise uimessage

            debug_add("Failed to process the conversion batch 1 (save original & web conversion) for the uploaded file {$filename} in {$tmpname}, aborting type processing.",
                MIDCOM_LOG_ERROR);

            // Clean up (but only if we're not called by the images child class [or anyone else using the force_pending_attachments -argument)
            if ($force_pending_attachments === false) {
                $this->delete_all_attachments();
            }

            return false;
        }
        $this->_add_thumbnail_to_derived_images();
        // Prepare all other images
        if (   !$this->_save_image('main', $this->filter_chain)
            || !$this->_save_derived_images()) {
            // TODO: Raise uimessage

            debug_add("Failed to process the conversion batch 2 (derived images) for the uploaded file {$filename} in {$tmpname}, aborting type processing.",
                MIDCOM_LOG_ERROR);

            // Clean up (but only if we're not called by the images child class [or anyone else using the force_pending_attachments -argument)
            if ($force_pending_attachments === false) {
                $this->delete_all_attachments();
            }

            return false;
        }

        // Clear up all attachments no longer in use:
        $this->_clean_pending_attachments();

        return true;
    }

    protected function _clean_pending_attachments()
    {
        foreach (array_keys($this->_pending_attachments) as $identifier) {
            $this->delete_attachment($identifier);
        }
    }

    /**
     * Small internal helper function. It adds a derived 'thumbnail' image to the list
     * used if and only if the auto_thumbnail option is set. Any existing thumbnail
     * declaration will be silently overwritten!
     */
    protected function _add_thumbnail_to_derived_images()
    {
        if ($this->auto_thumbnail) {
            $this->derived_images['thumbnail'] = "resize({$this->auto_thumbnail[0]},{$this->auto_thumbnail[1]})";
        }
    }

    /**
     * This loops over the defined derived images (if any) and constructs
     * each of them in turn.
     *
     * @return boolean Indicating success.
     */
    protected function _save_derived_images()
    {
        foreach ($this->derived_images as $identifier => $filter_chain) {
            if (!$this->_save_image($identifier, $filter_chain)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Saves the main image to the type, doing transformation work if configured to do so.
     *
     * @param string $identifier The image identifier
     * @param string $filter_chain The filter chain
     * @param boolean $is_original Are we saving the original image or a derived one
     * @return boolean Indicating success.
     */
    protected function _save_image($identifier, $filter_chain, $is_original = false)
    {
        if ($is_original) {
            $tmpname = $this->_original_tmpname;
        } else {
            $tmpname = $this->_filter->create_tmp_copy($this->_original_tmpname);
            if (!$this->_filter->set_file($tmpname)) {
                return false;
            }
            // Filter if necessary.
            if ($filter_chain) {
                try {
                    $this->_filter->process_chain($filter_chain);
                } catch (midcom_error $e) {
                    midcom::get()->uimessages->add('midcom.helper.imagefilter', $e->getMessage(), 'error');
                    $e->log();
                    return false;
                }
            }
        }

        $filename = $this->_filename;
        $blob_identifier = $identifier;
        if ($identifier !== 'main') {
            $filename = $identifier . '_' . $filename;
        }
        $title = $this->title;
        if (isset($this->_identifier)) {
            // we come from the image*s* type
            $blob_identifier = $this->_identifier . $identifier;
            $title = $this->_title;
        }

        $guesser = new FileBinaryMimeTypeGuesser;
        $mimetype = $guesser->guess($tmpname);
        if (array_key_exists($identifier, $this->_pending_attachments)) {
            unset($this->_pending_attachments[$identifier]);
            return $this->update_attachment($blob_identifier, $filename, $title, $mimetype, $tmpname, false);
        }
        if (isset($this->_attachment_map)) {
            $this->_attachment_map[$blob_identifier] = [$this->_identifier, $identifier];
        }
        return $this->add_attachment($blob_identifier, $filename, $title, $mimetype, $tmpname, false);
    }

    /**
     * If we are configured to do so, we save the original image.
     *
     * @return boolean Indicating success
     */
    protected function _save_original()
    {
        if (!$this->keep_original) {
            return true;
        }
        return $this->_save_image('original', '', true);
    }

    /**
     * Automatically convert the uploaded file to a web-compatible type. Uses
     * only the first image of multi-page uploads (like PDFs). The original_tmpname
     * file is manipulated directly.
     *
     * Uploaded GIF, PNG and JPEG files are left untouched.
     *
     * In case of any conversions being done, the new extension will be appended
     * to the uploaded file.
     *
     * @return boolean Indicating success
     */
    protected function _auto_convert_to_web_type()
    {
        $guesser = new FileBinaryMimeTypeGuesser;
        $original_mimetype = $guesser->guess($this->_filter->get_file());
        switch ($original_mimetype) {
            case 'image/png':
            case 'image/gif':
            case 'image/jpeg':
                debug_add('No conversion necessary, we already have a web mime type');
                return true;

            case 'application/postscript':
            case 'application/pdf':
                $conversion = 'png';
                break;

            default:
                $conversion = 'jpg';
                break;
        }
        debug_add('convert ' . $original_mimetype . ' to ' . $conversion);

        if (!$this->imagemagick_available()) {
            throw new midcom_error('DM2 type image requires ImageMagick for manipulation operations, see debug log for details');
        }

        // Prevent double .jpg.jpg in case of trouble file the get_mimetype()
        if (!preg_match("/\.{$conversion}$/", $this->_filename)) {
            // Make sure there is only one extension on the file ??
            $this->_filename = midcom_db_attachment::safe_filename($this->_filename . ".{$conversion}", true);
        }

        try {
            $this->_filter->convert($conversion);
        } catch (midcom_error $e) {
            $e->log();
            return false;
        }
        return true;
    }

    /**
     * Calls the base type to read all attachments, then extracts the title of the
     * main attachment, if applicable.
     */
    public function convert_from_storage($source)
    {
        parent::convert_from_storage($source);

        if (array_key_exists('main', $this->attachments)) {
            $this->title = $this->attachments['main']->title;
        }
    }

    /**
     * Updates the attachment titles.
     */
    public function convert_to_storage()
    {
        if ($this->_instance_mode === 'single' && !empty($this->title)) {
            foreach ($this->attachments as $identifier => $copy) {
                if ($copy->title === $this->title) {
                    continue;
                }
                $this->update_attachment_title($identifier, $this->title);
            }
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
    public function convert_to_html()
    {
        $result = '';

        // Use either main or original image
        if (isset($this->attachments_info['main'])) {
            $main = $this->attachments_info['main'];
        } elseif (isset($this->attachments_info['original'])) {
            $main = $this->attachments_info['original'];
        } else {
            return $result;
        }

        switch ($this->output_mode) {
            case 'html':
                if (!empty($main['description'])) {
                    $title = $main['description'];
                } else {
                    $title = $main['filename'];
                }

                if (array_key_exists('thumbnail', $this->attachments_info)) {
                    $thumb = $this->attachments_info['thumbnail'];
                    $result .= "<a href=\"{$main['url']}\"><img src=\"{$thumb['url']}\" {$thumb['size_line']} alt=\"{$title}\" title=\"{$title}\" /></a>";
                } else {
                    $result .= "<img src=\"{$main['url']}\" {$main['size_line']} alt=\"{$title}\" title=\"{$title}\" />";
                }
                break;

            // FIXME: wouldn't it be better to access $dm2->types->imagefield->attachments_info ??
            case 'array':
                $result = $main;

                if (array_key_exists('thumbnail', $this->attachments_info)) {
                    $result['thumbnail'] = $this->attachments_info['thumbnail'];
                }

                break;
        }

        return $result;
    }

    /**
     * Validation
     *
     */
    public function validate()
    {
        if ($this->_validation_done) {
            return empty($this->validation_error);
        }
        $this->validation_error = "";
        $this->_validation_done = true;

        // if no file was uploaded, there is nothing to validate
        if (is_null($this->uploaded_file)) {
            return true;
        }

        $file = $this->uploaded_file->getValue();

        // use the imagefilters identify in order to check if its a valid image that might be converted
        $filter = new midcom_helper_imagefilter();
        if (!$filter->identify($file["tmp_name"])) {
            $this->validation_error = $this->_l10n->get('unsupported image format');
            return false;
        }

        // check filesize
        if (!is_null($this->max_filesize)) {
            $filesize_kb = ($file["size"] / 1024); // filesize in byte
            if ($filesize_kb > $this->max_filesize) {
                $this->validation_error = $this->_l10n->get('upload max filesize exceeded');
                return false;
            }
        }

        // we might get malicious upload, check it before further processing
        if (!$this->file_sanity_checks($file["tmp_name"])) {
            $this->validation_error = $this->_l10n->get('sanity check failed');
            return false;
        }

        return true;
    }
}
