<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Image type specialized for photos
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
    protected function _prepare_recreate($force = true)
    {
        if (   !empty($this->_original_tmpname)
            && !$force) {
            // We have prepared.
            return true;
        }
        // Prepare internal members
        $this->_filter = new midcom_helper_imagefilter();
        $this->_pending_attachments = $this->attachments;
        // Remove archival and main from pending attachments
        if (!$this->do_not_save_archival) {
            unset($this->_pending_attachments['archival']);
        }
        if (array_key_exists('archival', $this->attachments)) {
            // PONDER: This could cause issues with RAW etc special format archived versions...
            // Copy archival as original
            $att = $this->attachments['archival'];
        } elseif (array_key_exists('main', $this->attachments)) {
            // Copy main as original
            $att = $this->attachments['main'];
        } else {
            return false;
        }
        $this->_filter = new midcom_helper_imagefilter($att);
        $this->_original_tmpname = $this->_filter->get_file();
        $this->title = $this->attachments['main']->title;
        $this->_filename = $this->attachments['main']->name;
        return true;
    }

    /**
     * recreates main image if archival is available
     */
    function recreate()
    {
        if (!array_key_exists('archival', $this->attachments)) {
            // Allow main image only be recreated if we have original stored
            return false;
        }
        return $this->recreate_main_image();
    }

    /**
     * Adds the image to the type. Loads and processes the $tmpname file on disk.
     *
     * @param string $filename The name of the image attachment to be created.
     * @param string $tmpname The file to load.
     * @param string $title The title of the image.
     * @return boolean Indicating success.
     */
    function set_image($filename, $tmpname, $title)
    {
        $force_attachments = $this->attachments;
        unset($force_attachments['archival']);
        return parent::set_image($filename, $tmpname, $title, $force_attachments);
    }

    protected function _save_original()
    {
        if ($this->do_not_save_archival) {
            return true;
        }
        return $this->_save_image('archival', '', true);
    }

    public function convert_to_html()
    {
        if (!array_key_exists('main', $this->attachments_info)) {
            $ret = "";
            if (sizeof($this->attachments_info) > 0) {
                $ret .= "\n" . $this->_l10n->get('could not figure out which image to show, listing files') . "\n<ul>\n";
                foreach ($this->attachments_info as $key => $data) {
                    $ret .= "    <li><a href='{$data['url']}'>{$key}</a></li>\n";
                }
                $ret .= "</ul>\n";
            }
            return $ret;
        }

        $img = $this->attachments_info['main'];
        $linkto = false;
        if (array_key_exists('view', $this->attachments_info)) {
            $img = $this->attachments_info['view'];
            $linkto = $this->attachments_info['main'];
        } elseif (array_key_exists('thumbnail', $this->attachments_info)) {
            $img = $this->attachments_info['thumbnail'];
            $linkto = $this->attachments_info['main'];
        } elseif (array_key_exists('archival', $this->attachments_info)) {
            $linkto = $this->attachments_info['archival'];
        }

        $return = "\n<div class='midcom_helper_datamanager2_type_photo'>\n";
        $img_tag = "<img src='{$img['url']}' {$img['size_line']} class='photo {$img['identifier']}' />";
        if ($linkto) {
            $return .= "    <a href='{$linkto['url']}' target='_blank' class='{$linkto['identifier']} {$linkto['mimetype']}'>\n        {$img_tag}\n    </a>\n";
        } else {
            $return .= "    {$img_tag}\n";
        }
        if (array_key_exists('archival', $this->attachments_info)) {
            $arch = $this->attachments_info['archival'];
            $return .= "    <br/>\n    <a href='{$arch['url']}' target='_blank' class='archival {$arch['mimetype']}'>" . $this->_l10n->get('archived image') . "</a>\n";
        }
        $return .= "</div>\n";
        return $return;
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
            if ($identifier === 'archival') {
                continue;
            }
            if (!$this->apply_filter($identifier, $filter)) {
                debug_add("Failed to apply filter '{$filter}' to image '{$identifier}', aborting", MIDCOM_LOG_ERROR);
                return false;
            }
        }
        return true;
    }
}
