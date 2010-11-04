<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: photo.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** @ignore */
require_once(MIDCOM_ROOT . '/midcom/helper/datamanager2/widget/image.php');

/**
 * Image widget specialized for org.routamc.photostream
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_photo extends midcom_helper_datamanager2_widget_image
{
    function __construct(&$renderer)
    {
        return parent::__construct($renderer);
    }

    /**
     * The on_submit event handles all file uploads immediately. They are passed through
     * the type at that point.
     */
    function on_submit($results)
    {
        // TODO: refactor these checks to separate methods
        if (array_key_exists("{$this->name}_rotate", $results))
        {
            // The direction is the key (since the value is the point clicked on the image input)
            list ($direction, $dummy) = each($results["{$this->name}_rotate"]);
            if (! $this->_type->rotate($direction))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to rotate image on the field {$this->name}.",
                    MIDCOM_LOG_ERROR);
                debug_pop();
            }
            $this->_cast_formgroup_to_replacedelete();
            return;
        }
        else if (!$this->_upload_element->isUploadedFile())
        {
            // _FILES juggling won't work anyways for doing archive extraction, drive the type directly for that
            return;
        }
        $file = $this->_upload_element->getValue();
        if (! $this->_type->set_image($file['name'], $file['tmp_name'], ''))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to process image {$this->name}.", MIDCOM_LOG_INFO);
            debug_pop();
            // This (in parent) calls the _create_upload_elements which we have overridden here to our liking.
            $this->_cast_formgroup_to_upload();
        }
        else
        {
            // This (in parent) in fact calls the _create_replace_elements which we have overridden here to our liking
            $this->_cast_formgroup_to_replacedelete();
        }
    }

    /**
     * Creates the upload elements for empty types.
     *
     * @param Array &$elements The array where the references to the created elements should
     *     be added.
     */
    function _create_upload_elements(&$elements)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called');
        $static_html = "<label for='{$this->_namespace}{$this->name}'>" . $this->_l10n->get('upload image') . ": \n";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_start", '', $static_html);
        $elements[] = $this->_upload_element;
        $static_html = "\n</label>\n";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_end", '', $static_html);
        debug_pop();
    }

    /**
     * Creates the elements to manage an existing upload, offering "delete" and "upload new file"
     * operations.
     *
     * @todo adjust to be more suited for the photostream use case
     * @param Array &$elements The array where the references to the created elements should
     *     be added.
     */
    function _create_replace_elements(&$elements)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('called');

        switch (true)
        {
            case (array_key_exists('main', $this->_type->attachments_info)):
                $main_info = $this->_type->attachments_info['main'];
                break;
            case (array_key_exists('archival', $this->_type->attachments_info)):
                $main_info = $this->_type->attachments_info['archival'];
                break;
            case (array_key_exists('view', $this->_type->attachments_info)):
                $main_info = $this->_type->attachments_info['view'];
                break;
            default:
                list($main_key, $main_info) = each($this->_type->attachments_info);
        }

        // Get preview image source
        if (array_key_exists('thumbnail', $this->_type->attachments))
        {
            $url = $this->_type->attachments_info['thumbnail']['url'];
            $x = $this->_type->attachments_info['thumbnail']['size_x'];
            $y = $this->_type->attachments_info['thumbnail']['size_y'];
            $is_thumbnail = true;
        }
        else
        {
            $is_thumbnail = false;
            $url = $main_info['url'];
            $x = $main_info['size_x'];
            $y = $main_info['size_y'];

            // Downscale Preview image to max 75px, protect against broken images:
            if (   $x != 0
                && $y != 0)
            {
                $aspect = $x/$y;
                if ($x > 75)
                {
                    $x = 75;
                    $y = round($x / $aspect);
                }
                if ($y > 75)
                {
                    $y = 75;
                    $x = round($y * $aspect);
                }
            }
        }

        $size = " width='{$x}' height='{$y}'";

        // Start widget table, add Thumbnail
        $static_html = "<table border='0' class='midcom_helper_datamanager2_widget_image_table' id='{$this->_namespace}{$this->name}_table'>\n<tr>\n" .
            "<td align='center' valign='top' class='midcom_helper_datamanager2_widget_image_thumbnail'><img class='midcom_helper_datamanager2_widget_image_thumbnail' src='{$url}' {$size} />";
        if ($is_thumbnail)
        {
            $static_html .= "<br />\n(" . $this->_l10n->get('type image: thumbnail') . ')';
        }

        // Statistics & Available sizes
        $static_html .= "</td>\n";
        if (isset($main_info))
        {
            $static_html .= "<td valign='top' class='midcom_helper_datamanager2_widget_image_stats'>" . $this->_l10n->get('type blobs: file size') . ": {$main_info['formattedsize']}<br/>\n";
        }
        $static_html .= $this->_l10n->get('type image: available sizes') . ":\n" .
                "<ul class='midcom_helper_datamanager2_widget_image_sizelist'>";
        foreach ($this->_type->attachments_info as $name => $info)
        {
            //PONDER: skip the archival version ?? - No
            if (   $info['size_x']
                && $info['size_y'])
            {
                $size = "{$info['size_x']}x{$info['size_y']}";
            }
            else
            {
                $size = $this->_l10n_midcom->get('unknown');
            }
            $static_html .= "<li title=\"{$info['guid']}\"><a href='{$info['url']}' target='_new'>{$info['filename']}:</a> " .
                "{$size}, {$info['formattedsize']}</li>\n";
        }
        $static_html .= "</ul>\n";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_start", '', $static_html);

        // Add action buttons
        $this->add_action_elements($elements);

        // Add the upload widget
        $static_html = "</td>\n</tr>\n" .
            "<tr>\n<td class='midcom_helper_datamanager2_widget_image_label'>" .
            $this->_l10n->get('replace image') . ":</td>\n" .
            "<td class='midcom_helper_datamanager2_widget_image_upload'>";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_inter1", '', $static_html);

        $elements[] = $this->_upload_element;
        $attributes = Array
        (
            'id'    => "{$this->_namespace}{$this->name}_upload_button",
        );

        $static_html = "\n</td>\n</tr>\n</table>\n";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_end", '', $static_html);
        debug_pop();
    }
}

?>