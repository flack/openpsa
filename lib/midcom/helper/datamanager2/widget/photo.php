<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Image widget specialized for photos
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_photo extends midcom_helper_datamanager2_widget_image
{
    public $show_action_elements = false;

    public $show_title = false; // we render no title

    /**
     * Creates the upload elements for empty types.
     *
     * @param array &$elements The array where the references to the created elements should
     *     be added.
     */
    function _create_upload_elements(array &$elements)
    {
        $static_html = "<label for='{$this->_namespace}{$this->name}'>" . $this->_l10n->get('upload image') . ": \n";
        $elements[] = $this->_form->createElement('static', "{$this->name}_start", '', $static_html);
        $elements[] = $this->_upload_element;
        $static_html = "\n</label>\n";
        $elements[] = $this->_form->createElement('static', "{$this->name}_end", '', $static_html);
    }

    /**
     * Creates the elements to manage an existing upload, offering "delete" and "upload new file"
     * operations.
     *
     * @param array &$elements The array where the references to the created elements should
     *     be added.
     */
    function _create_replace_elements(array &$elements)
    {
        switch (true) {
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
                $main_info = end($this->_type->attachments_info);
        }

        $static_html = $this->_get_preview_html($main_info);

        // Statistics & Available sizes
        $static_html .= "</td>\n";
        if (isset($main_info)) {
            $static_html .= "<td valign='top' class='midcom_helper_datamanager2_widget_image_stats'>" . $this->_l10n->get('type blobs: file size') . ": {$main_info['formattedsize']}<br/>\n";
        }
        $static_html .= $this->_l10n->get('type image: available sizes') . ":\n
                <ul class='midcom_helper_datamanager2_widget_image_sizelist'>";
        foreach ($this->_type->attachments_info as $info) {
            if (   $info['size_x']
                && $info['size_y']) {
                $size = "{$info['size_x']}x{$info['size_y']}";
            } else {
                $size = $this->_l10n_midcom->get('unknown');
            }
            $static_html .= "<li title=\"{$info['guid']}\"><a href='{$info['url']}' target='_new'>{$info['filename']}:</a>
                {$size}, {$info['formattedsize']}</li>\n";
        }
        $static_html .= "</ul>\n";
        $elements[] = $this->_form->createElement('static', "{$this->name}_start", '', $static_html);

        // Add action buttons
        if ($this->show_action_elements) {
            $this->add_action_elements($elements);
        }

        // Add the upload widget
        $static_html = "</td>\n</tr>\n
            <tr>\n<td class='midcom_helper_datamanager2_widget_image_label'>" .
            $this->_l10n->get('replace image') . ":</td>\n
            <td class='midcom_helper_datamanager2_widget_image_upload'>";
        $elements[] = $this->_form->createElement('static', "{$this->name}_inter1", '', $static_html);

        $elements[] = $this->_upload_element;
        $attributes = array(
            'id'    => "{$this->_namespace}{$this->name}_upload_button",
        );
        $elements[] = $this->_form->createElement('submit', "{$this->name}_upload", $this->_l10n->get('upload file'), $attributes);

        // Add the Delete button
        $attributes = array(
            'id'    => "{$this->_namespace}{$this->name}_delete_button",
        );
        $elements[] = $this->_form->createElement('submit', "{$this->name}_delete", $this->_l10n->get('delete image'), $attributes);

        $static_html = "\n</td>\n</tr>\n</table>\n";
        $elements[] = $this->_form->createElement('static', "{$this->name}_end", '', $static_html);
    }
}
