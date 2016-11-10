<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 images widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports the images type or any subtype thereof.
 *
 * All processing is done during the on_submit handlers, enforcing immediate update of the
 * associated storage objects. No other solution is possible, since we need to transfer
 * uploaded files somehow through multiple requests.
 *
 * The type will show a tabular view of all uploaded images. Existing images have
 * an editable title and can be deleted or replaced. A single new upload line is displayed
 * always. There is no preview, but there is a download link.
 *
 * <b>Available configuration options:</b>
 *
 * - set_name_and_title_on_upload use this if you want the user to be able to set the
 *   filename and title when uploading a form.
 * - integer max_count Maximum number of images allowed for a field. Set this
 *
 * <b>Implementation notes:</b>
 *
 * The construction of the widget is relatively complex, it relies on a combination of
 * static and input elements to do its work. It should be fairly customizable using CSS.
 *
 * 1. Quickform Element Naming
 *
 * All elements will be added in a group using the groupname[elementname] Feature of QF.
 * Static elements are all prefixed s_, f.x. s_header. The actual elements use an e_, f.x.
 * e_new_title. All elements in the new upload row append a new_ to this prefix as seen
 * in the last example. Finally, elements referencing existing attachments append an
 * exist_{$identifier}_ to the prefix, f.x. e_exist_{$identifier}_title.
 *
 * 2. CSS names
 *
 * The table gets the Name of the field as id and midcom_helper_datamanager2_widget_images
 * as class. Each column also gets its own CSS class: filename, title, file, upload and delete.
 * An additional class is assigned depending whether this is a row for an existing item (exist) or
 * a new one (new). So a full class for the new filename element would be "new filename". Note,
 * that the classes are assigned to both the td and input elements. The th elements do not have
 * any additional class
 *
 * 3. Image identifiers
 *
 * The auto-generated image identifiers from the images base type are used.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_images extends midcom_helper_datamanager2_widget
{
    /**
     * The group of elements forming our widget.
     *
     * @var HTML_QuickForm_Group
     */
    private $_group = null;

    /**
     * The list of elements added to the widget, indexed by their element name.
     *
     * @var Array
     */
    private $_elements = null;

    /**
     * Should the user be able to set the filename and title on upload?
     * If so, set this to true.
     *
     * @var boolean
     */
    var $set_name_and_title_on_upload = true;

    /**
     * Maximum amount of images allowed to be stored in the same field
     *
     * @var integer
     */
    public $max_count = 0;

    /**
     * Sort index or what is the position in the list
     *
     * @var integer
     */
    private $_sort_index = 1;

    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_images');

        // Reflect the type config setting for maximum count
        if (   isset($this->_type->max_count)
            && !$this->max_count) {
            $this->max_count = $this->_type->max_count;
        }

        // Create sortable
        if ($this->_type->sortable) {
            midcom::get()->head->enable_jquery();
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/datamanager2.tablesorter.js');
            // Configuration options
            midcom::get()->head->add_jscript("
                jQuery(document).ready(function()
                {
                    jQuery('#{$this->_namespace}{$this->name}')
                        .create_tablesorter({
                            max_count: 0,
                            sortable_rows: true,
                            allow_delete: false
                        });
                });
            ");
        }

        midcom::get()->head->add_jscript($this->_get_filename_validation_script());
    }

    private function _get_filename_validation_script()
    {
        return <<<END
function midcom_helper_dm2_widget_images_check(evt, id) {
    evt = (evt) ? evt: ( (window.event) ? event : null);
    var obj,reg, msg;
    if (evt) {
        reg = /\.(png|gif|jpe?g|tiff?)$/;
        obj = (evt.target) ? evt.target : evt.srcElement;
        if (!obj.value.match(reg)) {
            obj.style.color = "red";
            msg = document.getElementById(id);
            msg.style.display = "block";
        }
    }
}

END;
    }

    /**
     * Adds the table header to the widget.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_table_header($frozen)
    {
        if ($frozen) {
            $html = "<table class=\"midcom_helper_datamanager2_widget_images\" id=\"{$this->_namespace}{$this->name}\" >\n
                        <thead>\n
                            <tr>\n
                                <th class=\"filename\">" . $this->_l10n_midcom->get('name') . "</th>\n
                                <th class=\"title\">" . $this->_l10n_midcom->get('title') . "</th>\n
                            </tr>\n
                        </thead>\n
                        <tbody>\n";
        } else {
            $index = '';
            if ($this->_type->sortable) {
                $index = "            <th class=\"index\">" . $this->_l10n->get('index') . "</th>\n";
            }

            $html = "<table class=\"midcom_helper_datamanager2_widget_downloads\" id=\"{$this->_namespace}{$this->name}\" >\n
                        <thead>\n
                            <tr>\n" .
                    $index .
                    "            <th class=\"filename\">" . $this->_l10n_midcom->get('name') . "</th>\n
                                 <th class=\"title\">" . $this->_l10n_midcom->get('title') . "</th>\n
                                 <th class=\"upload\">" . $this->_l10n_midcom->get('upload') . "</th>\n
                             </tr>\n
                         </thead>\n
                        <tbody>\n";
        }
        $this->_elements['s_header'] = $this->_form->createElement('static', 's_header', '', $html);
    }

    /**
     * Adds the new upload row to the bottom of the table.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_new_upload_row_old($frozen)
    {
        // Show only a configured amount of new image rows
        if (   $this->max_count
            && count($this->_type->images) >= $this->max_count) {
            return;
        }

        $sortable = '';
        if ($this->_type->sortable) {
            $sortable = "            <td class=\"sortable_new\"></td>\n";
        }

        // Filename column
        $html = "         <tr>\n" .
                $sortable .
                "            <td class=\"new filename\">";
        $this->_elements['s_new_filename'] = $this->_form->createElement('static', 's_new_filename', '', $html);
        $attributes = array(
            'class' => 'new filename',
            'id'    => "{$this->_namespace}{$this->name}_e_new_filename",
        );
        $this->_elements['e_new_filename'] = $this->_form->createElement('text', 'e_new_filename', '', $attributes);

        // Title Column
        $html = "            </td>\n
                            <td class=\"new title\">";
        $this->_elements['s_new_title'] = $this->_form->createElement('static', 's_new_title', '', $html);
        $attributes = array(
            'class' => 'new title',
            'id'    => "{$this->_namespace}{$this->name}_e_new_title",
        );
        $this->_elements['e_new_title'] = $this->_form->createElement('text', 'e_new_title', '', $attributes);

        if (!$frozen) {
            // Controls Column
            $html = "            </td>\n
                                <td class=\"new upload\">";
            $this->_elements['s_new_upload'] = $this->_form->createElement('static', 's_new_upload', '', $html);
            $attributes = array(
                'class' => 'new file',
                'id'    => "{$this->_namespace}{$this->name}_e_new_file",
            );
            $this->_elements['e_new_file'] = $this->_form->createElement('file', 'e_new_file', '', $attributes);
            $attributes = array(
                'class' => 'new upload',
                'id'    => "{$this->_namespace}{$this->name}_e_new_upload",
            );

            $this->_elements['e_new_upload'] = $this->_form->createElement('submit', "{$this->name}_e_new_upload", $this->_l10n->get('upload file'), $attributes);
        }

        $html = "            </td>\n
                         </tr>\n";
        $this->_elements['s_new_file'] = $this->_form->createElement('static', 's_new_file', '', $html);
    }

    /**
     * Adds the new upload row to the bottom of the table.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_new_upload_row($frozen)
    {
        // Show only a configured amount of new image rows
        if (   $this->max_count
            && count($this->_type->images) >= $this->max_count) {
            return;
        }
        $html = "        <tr>\n";
        if ($this->_type->sortable) {
            $html .= "            <td class=\"new sortable\"></td>\n";
        }
        // Filename column
        $html .= "            <td class=\"new text\" colspan=\"2\">";
        $html .= $this->_l10n->get('add new file') . ':';
        $this->_elements['s_new_filename'] = $this->_form->createElement('static', 's_new_filename', '', $html);

        if (!$frozen) {
            // Controls Column
            $html = "</td><td class=\"new upload\" colspan=\"2\">";
            $this->_elements['s_new_upload'] = $this->_form->createElement('static', 's_new_upload', '', $html);
            $attributes = array(
                'class' => 'new file',
                'id'    => "{$this->_namespace}{$this->name}_e_new_file",
            );
            $this->_elements['e_new_file'] = $this->_form->createElement('file', 'e_new_file', '', $attributes);
            $attributes = array(
                'class' => 'new upload',
                'id'    => "{$this->_namespace}{$this->name}_e_new_upload",
            );
            $this->_elements['e_new_upload'] = $this->_form->createElement('submit', "{$this->name}_e_new_upload", $this->_l10n->get('upload file'), $attributes);
        }

        $html = "            </td>\n
                         </tr>\n";
        $this->_elements['s_new_file'] = $this->_form->createElement('static', 's_new_file', '', $html);
    }

    /**
     * Adds a row for an existing image
     *
     * @param string $identifier The identifier of the image to add.
     * @param array $images The images to add.
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_image_row($identifier, array $images, $frozen)
    {
        if (isset($images['main'])) {
            $info = $images['main'];
        } elseif (isset($images['original'])) {
            $info = $images['original'];
        } else {
            $info = current($images);
        }
        if (empty($info['object']->guid)) {
            //Panic, broken identifier
            debug_add("Identifier '{$identifier}' does not have a valid object behind it",  MIDCOM_LOG_ERROR);
            return;
        }

        $preview = $this->_get_preview_html($info, $identifier);

        $img_title = '';
        // Some reason we're kicking out-of-sync, check explicitly for POSTed value
        if (!empty($_POST[$this->name]["e_exist_{$identifier}_title"])) {
            $img_title = $_POST[$this->name]["e_exist_{$identifier}_title"];
        }
        // Otherwise use the type title if available
        elseif (isset($this->_type->titles[$identifier])) {
            $img_title = $this->_type->titles[$identifier];
        }

        // Initialize the string
        $sortable = '';

        if ($this->_type->sortable) {
            $sortable = "            <td class=\"midcom_helper_datamanager2_helper_sortable\"><input type=\"text\" class=\"image_sortable\" name=\"midcom_helper_datamanager2_sortable[{$this->name}][{$identifier}]\" value=\"{$this->_sort_index}\" /></td>\n";
            $this->_sort_index++;
        }

        // Filename column
        $html = "        <tr title=\"{$info['guid']}\" class=\"midcom_helper_datamanager2_widget_images_image\">\n" .
                $sortable .
                "            <td class=\"exist filename\" title=\"{$info['filename']}\">\n
                                 {$preview}<br />\n
                                 <a href=\"{$info['url']}\">{$info['filename']}</a>\n
                             </td>\n";
        $this->_elements["s_exist_{$identifier}_filename"] = $this->_form->createElement('static', "s_exist_{$identifier}_filename", '', $html);

        // Title Column, set the value explicitly, as we are sometimes called after the defaults kick in.
        $html = "            <td class=\"exist title\" title=\"{$img_title}\">";
        $this->_elements["s_exist_{$identifier}_title"] = $this->_form->createElement('static', "s_exist_{$identifier}_title", '', $html);
        $attributes = array(
            'class' => 'exist title',
            'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_title",
        );
        $this->_elements["e_exist_{$identifier}_title"] = $this->_form->createElement('text', "e_exist_{$identifier}_title", '', $attributes);
        $this->_elements["e_exist_{$identifier}_title"]->setValue($img_title);

        if (!$frozen) {
            $this->_add_controls($info, $identifier);
        }

        $html = "            </td>\n
                         </tr>\n";
        $this->_elements["s_exist_{$identifier}_file"] = $this->_form->createElement('static', "s_exist_{$identifier}_file", '', $html);
    }

    private function _get_preview_html($info, $identifier)
    {
        // Get preview image source
        if (array_key_exists('thumbnail', $this->_type->images[$identifier])) {
            $url = $this->_type->images[$identifier]['thumbnail']['url'];
            $size_line = $this->_type->images[$identifier]['thumbnail']['size_line'];
            $preview = "<a href=\"{$info['url']}\" class=\"download\"><img src=\"{$url}\" {$size_line} /></a>";
        } else {
            $url = $info['url'];
            $x = $info['size_x'];
            $y = $info['size_y'];

            // Downscale Preview image to max 75px, protect against broken images:
            if (   $x != 0
                && $y != 0) {
                $aspect = $x/$y;
                if ($x > 75) {
                    $x = 75;
                    $y = round($x / $aspect);
                }
                if ($y > 75) {
                    $y = 75;
                    $x = round($y * $aspect);
                }
            } else {
                // Final safety to prevent the editor from exploding with large images
                $x = 75;
                $y = 75;
            }

            $size_line = "width=\"{$x}\" height=\"{$y}\"";
            $preview = "                <a href=\"{$url}\" class=\"download\"><img src=\"{$url}\" {$size_line} /></a>\n";
        }
        return $preview;
    }

    private function _add_controls($info, $identifier)
    {
        if ($info['object']->can_do('midgard:update')) {
            $html = "            </td>\n
                                 <td class=\"exist upload\">\n";
            $this->_elements["s_exist_{$identifier}_upload"] = $this->_form->createElement('static', "s_exist_{$identifier}_upload", '', $html);
            $attributes = array(
                'class' => 'exist file',
                'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_file",
                'onchange' => "midcom_helper_dm2_widget_images_check(event, 'e_exist_{$identifier}_delete')",
            );
            $this->_elements["e_exist_{$identifier}_file"] = $this->_form->createElement('file', "e_exist_{$identifier}_file", '', $attributes);
            $this->_elements["s_exist_{$identifier}_br"] = $this->_form->createElement('static', "s_exist_{$identifier}_upload", '', "<br/>");
            $attributes = array(
                'class' => 'exist upload',
                'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_upload",
            );
            $this->_elements["e_exist_{$identifier}_upload"] = $this->_form->createElement('submit', "{$this->name}_e_exist_{$identifier}_upload", $this->_l10n->get('replace file'), $attributes);
        }
        if ($info['object']->can_do('midgard:delete')) {
            $attributes = array(
                'class' => 'exist delete',
                'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_delete",
            );
            $this->_elements["e_exist_{$identifier}_delete"] = $this->_form->createElement('submit', "{$this->name}_e_exist_{$identifier}_delete", $this->_l10n->get('delete file'), $attributes);
        }
        // WTF, the identifiers look wonky here
        $html = sprintf("<span id=\"e_exist_{$identifier}_delete\" style=\"display:none;color:red\">%s</span>",
                        $this->_l10n_midcom->get('You can only upload images here. This file will not be saved.'));
        $this->_elements["s_exist_{$identifier}_error"] = $this->_form->createElement('static', "s_exist_{$identifier}_upload", '', $html);
    }

    /**
     * Adds the table footer.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_table_footer()
    {
        $html = "    </tbody>\n
                 </table>\n";
        $this->_elements['s_footer'] = $this->_form->createElement('static', 's_footer', '', $html);
    }

    /**
     * Constructs the upload list.
     */
    public function add_elements_to_form($attributes)
    {
        $frozen = false;
        if (   $this->_type->storage->object
            && (   !$this->_type->storage->object->can_do('midgard:attachments')
                || !$this->_type->storage->object->can_do('midgard:update')
                || !$this->_type->storage->object->can_do('midgard:parameters'))) {
            $frozen = true;
        }
        if (!$this->_type->imagemagick_available(true)) {
            $frozen = true;
        }

        $this->_compute_elements($frozen);
        $this->_group = $this->_form->addGroup($this->_elements, $this->name, $this->_translate($this->_field['title']), "\n");
    }

    /**
     * Computes the element list to form the widget. It populates the _elements member, which is
     * initialized with a new, empty array during startup.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _compute_elements($frozen = false)
    {
        $this->_elements = array();

        $this->_add_table_header($frozen);

        foreach ($this->_type->images as $identifier => $images) {
            $this->_add_image_row($identifier, $images, $frozen);
        }

        if ($this->set_name_and_title_on_upload) {
            $this->_add_new_upload_row_old($frozen);
        } else {
            $this->_add_new_upload_row($frozen);
        }
        $this->_add_table_footer();
    }

    /**
     * Checks whether a new file has been uploaded. If yes, it is processed.
     *
     * @param array $values The values associated with our element group (not the full submit value list).
     */
    private function _check_new_upload($values)
    {
        if (!array_key_exists('e_new_file', $this->_elements)) {
            // We are frozen, no upload can happen, so we exit immediately.
            return;
        }

        if (!$this->_elements['e_new_file']->isUploadedFile()) {
            // not uploaded file, abort
            return;
        }

        $file = $this->_elements['e_new_file']->getValue();

        if ( preg_match('/\.(zip|tar(\.gz|\.bz2)?|tgz)$/', strtolower($file['name']), $extension_matches)) {
            if (!$this->_type->_batch_handler($extension_matches[1], $file)) {
                debug_add("Failed to add attachments from compressed files to the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
            return;
        }

        if (!empty($values['e_new_title'])) {
            $title = $values['e_new_title'];
        } else {
            $title = $file['name'];
        }

        if (!empty($values['e_new_filename'])) {
            $filename = $values['e_new_filename'];
        } else {
            $filename = $file['name'];
        }

        if (!$this->_type->add_image($filename, $file['tmp_name'], $title)) {
            debug_add("Failed to add an attachment to the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
        }
    }

    /**
     * The following checks are made, in order:
     *
     * 1. If the delete button was clicked, the image is dropped.
     * 2. If a new file has been uploaded, it replaces the current one.
     *
     * Calls for images which are not listed in the form, will be silently ignored.
     * This may happen, for example, if two users edit the same object simultaneoulsy,
     * or during addition of new elements.
     *
     * @param string $identifier The attachment identifier to check for updates.
     * @param array $values The values associated with our element group (not the full submit value list).
     */
    private function _check_for_update($identifier, $values)
    {
        if (!array_key_exists($identifier, $this->_type->images)) {
            // The image does no longer exist
            return;
        }

        // Image to be deleted
        if (array_key_exists("{$this->name}_e_exist_{$identifier}_delete", $values)) {
            if (!$this->_type->delete_image($identifier)) {
                debug_add("Failed to delete the image {$identifier} on the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
        }
        // Image to be updated
        elseif (   array_key_exists("e_exist_{$identifier}_file", $this->_elements)
                  && $this->_elements["e_exist_{$identifier}_file"]->isUploadedFile()) {
            $file = $this->_elements["e_exist_{$identifier}_file"]->getValue();
            $title = $values["e_exist_{$identifier}_title"];
            $filename = $this->_type->images[$identifier]['main']['filename'];

            if (!$title) {
                $title = $filename;
            }

            if (!$this->_type->update_image($identifier, $filename, $file['tmp_name'], $title)) {
                debug_add("Failed to update the image {$identifier} on the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * The on_submit event handles all operations immediately. This includes title updates (all
     * are done regardless of actual updates).
     */
    function on_submit($results)
    {
        if (!array_key_exists($this->name, $results)) {
            return;
        }

        $values = $results[$this->name];

        $this->_check_new_upload($values);

        foreach (array_keys($this->_type->images) as $identifier) {
            $this->_check_for_update($identifier, $values);
        }

        // Rebuild Widget
        $this->_compute_elements();
        $this->_group->setElements($this->_elements);
    }

    /**
     * Freeze the entire group, special handling applies to skipp all elements which cannot be
     * frozen.
     */
    public function freeze()
    {
        // Rebuild Widget
        $this->_compute_elements(true);
        $this->_group->setElements($this->_elements);
    }

    /**
     * Unfreeze the entire group, special handling applies, the formgroup is replaced by a the
     * full input widget set.
     */
    public function unfreeze()
    {
        // Rebuild Widget
        $this->_compute_elements(false);
        $this->_group->setElements($this->_elements);
    }

    /**
     * Prepare for sorting the results
     */
    public function sync_type_with_widget($results)
    {
        $values = $results[$this->name];
        if (   $this->_type->sortable
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'])
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'][$this->name])) {
            /**
             * The regex match was *not* a good idea
            $this->_type->_sorted_list = $_REQUEST['midcom_helper_datamanager2_sortable'][$this->name];
             */
            // Explicitly set scores for each attachment found in the blobs type
            $this->_type->_sorted_list = array();
            $images_scores = $_REQUEST['midcom_helper_datamanager2_sortable'][$this->name];
            $images_scores = array_intersect_key($images_scores, $this->_type->images);
            foreach ($images_scores as $images_identifier => $score) {
                foreach ($this->_type->images[$images_identifier] as $info) {
                    /**
                     * This is still not 100% perfect (the absolute score values will be "too high" for
                     * each attachment stored, but relative to each other they all have correct values)
                     * but it works well enough.
                     */
                    $this->_type->_sorted_list[$info['identifier']] = $score;
                }
            }
        }

        foreach ($this->_type->images as $identifier => $info) {
            if (!isset($values["e_exist_{$identifier}_title"])) {
                continue;
            }
            $this->_type->titles[$identifier] = $values["e_exist_{$identifier}_title"];
        }
    }

    /**
     * Populates the title fields with their defaults.
     */
    public function get_default()
    {
        if (sizeof($this->_type->images) == 0) {
            return null;
        }
        $defaults = array();
        foreach (array_keys($this->_type->images) as $identifier) {
            if (isset($this->_type->titles[$identifier])) {
                $defaults["e_exist_{$identifier}_title"] = $this->_type->titles[$identifier];
            } else {
                $defaults["e_exist_{$identifier}_title"] = '';
            }
        }
        return array($this->name => $defaults);
    }
}
