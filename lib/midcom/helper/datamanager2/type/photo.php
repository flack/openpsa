<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: photo.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Image type specialized for org.routamc.photostream
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_photo extends midcom_helper_datamanager2_type_image
{
    /**
     * Set to true if for some reason you do not wish to store the archival version
     *
     * @var boolean
     */
    var $do_not_save_archival = false;

    /**
     * Preparation operations for recreate_xxx()
     */
    function _prepare_recreate($force = true)
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
        // Remove archival and main from pending attachments
        if (   !$this->do_not_save_archival
            && array_key_exists('archival', $this->_pending_attachments))
        {
            unset($this->_pending_attachments['archival']);
        }
        if (array_key_exists('archival', $this->attachments))
        {
            // PONDER: This could cause issues with RAW etc special format archived versions...
            // Copy archival as original
            $src = $this->attachments['archival']->open('r');
        }
        else
        {
            // Copy main as original
            $src = $this->attachments['main']->open('r');
        }
        // Create tmp file and copy by handles
        $this->_original_tmpname = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], "midcom_helper_datamanager2_type_photo");
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
        $this->_original_mimetype = $this->_get_mimetype($this->_original_tmpname);
        return true;
    }

    /**
     * Recreate all images
     */
    function recreate()
    {
        return $this->recreate_main_image();
    }

    /**
     * recreates main image if archival is available
     */
    function recreate_main_image()
    {
        if (!array_key_exists('archival', $this->attachments))
        {
            // Allow main image only be recreated if we have original stored
            return false;
        }
        if (!$this->_prepare_recreate())
        {
            return false;
        }
        if (   !$this->_filter->set_file($this->_original_tmpname)
            || !$this->_preprocess_raw()
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
     * Adds the image to the type. Loads and processes the $tmpname file on disk.
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
        // Ensure that the filename is URL safe and contains only one extension
        $filename = midcom_helper_datamanager2_type_blobs::safe_filename($filename, true);

        $this->_pending_attachments = $this->attachments;

        // Prepare Internal Members
        $this->title = $title;
        $this->_filename = $filename;
        $this->_original_tmpname = $tmpname;
        $this->_original_mimetype = $this->_get_mimetype($this->_original_tmpname);
        $this->_filter = new midcom_helper_imagefilter();

        if (array_key_exists('archival', $this->_pending_attachments))
        {
            // We never touch the archival version after it has been uploaded
            unset($this->_pending_attachments['archival']);
        }
        else
        {
            if (!$this->save_archival_image())
            {
                debug_add("Failed to store the archival image for the uploaded file {$filename} in {$tmpname}, aborting type processing.", MIDCOM_LOG_ERROR);
                // Could not store even the archival image properly, clean up and abort
                $this->delete_all_attachments();
                return false;
            }
        }
        if (!$this->_filter->set_file($this->_original_tmpname))
        {
            debug_add("this->_filter->set_file('{$this->_original_tmpname}') returned failure, aborting type processing.", MIDCOM_LOG_ERROR);
            // NOTE: absense of delete_all_attachments is intentional
            return false;
        }
        $this->_preprocess_raw();
        if (!$this->_auto_convert_to_web_type())
        {
            debug_add("failed to convert to web type, aborting type processing.", MIDCOM_LOG_ERROR);
            // NOTE: absense of delete_all_attachments is intentional
            return false;
        }
        $this->_add_thumbnail_to_derived_images();
        if (!$this->_save_main_image())
        {
            debug_add("failed to save 'main' image, aborting type processing.", MIDCOM_LOG_ERROR);
            // NOTE: absense of delete_all_attachments is intentional
            return false;
        }
        if (!$this->_save_derived_images())
        {
            debug_add("failed to save derived images, aborting type processing.", MIDCOM_LOG_ERROR);
            // NOTE: absense of delete_all_attachments is intentional
            return false;
        }

        // Clear up all attachments no longer in use
        $this->_clean_pending_attachments();

        if ($autodelete)
        {
            unlink ($this->_original_tmpname);
        }

        return true;
    }

    /**
     * If the original is a RAW file makes a PNG of it (or replaces with placeholder)
     *
     * We do not wish to rewrite _auto_convert_to_web_type() logic just because we
     * must be able to handle also RAW files, so here we try to render a PNG and
     * should we fail we use a placeholder image (which user can then replace with
     * a web-compatible one).
     *
     * @return boolean indicating whether we could render the RAW to web type properly or not
     */
    function _preprocess_raw()
    {
        // TODO: More sophisticated RAW format detection ??
        if (!preg_match('/\.raw$/', $this->_filename))
        {
            // Not a RAW image, no need for tricks.
            return true;
        }
        if ($this->_auto_convert_to_web_type())
        {
            // Rendering succeeded
            return true;
        }
        // PONDER: Read placeholder image file location from configuration somehow ??
        $replacement = MIDCOM_ROOT . '/midcom/helper/datamanager2/type/type_photo_placeholder.png';
        // TODO: Error handling
        $src = fopen($replacement, 'r');
        $dst = fopen($this->_original_tmpname, 'w+');
        while (!feof($src))
        {
            $buffer = fread($src, 131072); /* 128 kB */
            fwrite($dst, $buffer, 131072);
        }
        fclose($src);
        fclose($dst);
        return false;
    }

    function save_archival_image()
    {
        if ($this->do_not_save_archival)
        {
            return true;
        }
        $identifier = 'archival';
        return $this->add_attachment($identifier,
                                     "{$identifier}_{$this->_filename}",
                                     $this->title,
                                     $this->_get_mimetype($this->_original_tmpname),
                                     $this->_original_tmpname,
                                     false);
    }

    /**
     * a wrapper for set_image
     */
    function set_photo($filename, $tmpname, $title, $autodelete = true)
    {
        return $this->set_image($filename, $tmpname, $title, $autodelete);
    }

    function convert_to_html()
    {
        $img = false;
        $linkto = false;
        switch (true)
        {
            case (   array_key_exists('view', $this->attachments_info)
                  && array_key_exists('main', $this->attachments_info)):
                $img = $this->attachments_info['view'];
                $linkto = $this->attachments_info['main'];
                break;
            case (   array_key_exists('thumbnail', $this->attachments_info)
                  && array_key_exists('main', $this->attachments_info)):
                $img = $this->attachments_info['thumbnail'];
                $linkto = $this->attachments_info['main'];
                break;
            case (   array_key_exists('main', $this->attachments_info)
                  && array_key_exists('archival', $this->attachments_info)):
                $img = $this->attachments_info['main'];
                $linkto = $this->attachments_info['archival'];
                break;
            case (array_key_exists('main', $this->attachments_info)):
                $img = $this->attachments_info['main'];
                break;
            default:
                $ret = "";
	        if (sizeof($this->attachments_info) > 0)
                {
                    // Could not figure out what to do, listing all files we have...
                    $ret .= "\n" . $this->_l10n->get('could not figure out which image to show, listing files') . "\n<ul>\n";
                    foreach ($this->attachments_info as $key => $data)
                    {
                        $ret .= "    <li><a href='{$data['url']}'>{$key}</a></li>\n";
                    }
                    $ret .= "</ul>\n";
                }
                return $ret;
                break;
        }
        $return = "\n<div class='midcom_helper_datamanager2_type_photo'>\n";
        $img_tag = "<img src='{$img['url']}' {$img['size_line']} class='photo {$img['identifier']}' />";
        if ($linkto)
        {
            $return .= "    <a href='{$linkto['url']}' target='_BLANK' class='{$linkto['identifier']} {$linkto['mimetype']}'>\n        {$img_tag}\n    </a>\n";
        }
        else
        {
            $return .= "    {$img_tag}\n";
        }
        if (array_key_exists('archival', $this->attachments_info))
        {
            $arch = $this->attachments_info['archival'];
            $return .= "    <br/>\n    <a href='{$arch['url']}' class='archival {$arch['mimetype']}' target='_BLANK' >" . $this->_l10n->get('archived image') . "</a>\n";
        }
        $return .= "</div>\n";
        return $return;
    }

    function convert_to_storage()
    {
        return parent::convert_to_storage();
    }

    /**
     * Applies a given filter to all (except original) images in the type
     *
     * @param string $filter the midcom_helper_imagefilter filter chain to apply
     * @return boolean indicating success/failure
     */
    function apply_filter_all($filter)
    {
        foreach($this->attachments as $identifier => $image)
        {
            if ($identifier === 'archival')
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
}
?>