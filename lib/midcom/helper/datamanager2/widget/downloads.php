<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 download widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports the blobs type or any subtype thereof.
 *
 * All processing is done during the on_submit handlers, enforcing immediate update of the
 * associated storage objects. No other solution is possible, since we need to transfer
 * uploaded files somehow through multiple requests.
 *
 * Note, that this widget (as opposed to the image & co widgets) uses the blobs base type
 * directly and thus has no post-processing capabilities whatsoever.
 *
 * The type will show a tabular view of all uploaded attachments. Existing attachments have
 * an editable tile and can be deleted or replaced. A single new upload line is displayed
 * always. There is no preview, but there is a download link.
 *
 * <b>Available configuration options:</b>
 *
 * - none
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
 * The table gets the Name of the field as id and midcom_helper_datamanager2_widget_downloads
 * as class. Each column also gets its own CSS class: filename, title, file, upload and delete.
 * An additional class is assigned depending whether this is a row for an existing item (exist) or
 * a new one (new). So a full class for the new filename element would be "new filename". Note,
 * that the classes are assigned to both the td and input elements. The th elements do not have
 * any additional class
 *
 * 3. Attachment identifiers
 *
 * Attachments are identified using an MD5 hash constructed from original upload time, uploaded
 * file name and the temporary file name used during upload. Before adding the actual attachments,
 * they are ordered by filename.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_downloads extends midcom_helper_datamanager2_widget
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
     * Maximum amount of blobs allowed to be stored in the same field
     *
     * @var integer
     */
    public $max_count = 0;

    /**
     * ID for upload-progress
     *
     * @var string
     */
    public $progress_id = 0;

    /**
     * indicates if progressbar is shown
     *
     * @var bool
     */
    public $show_progressbar = false;

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
        $this->_require_type_class('midcom_helper_datamanager2_type_blobs');

        // Reflect the type config setting for maximum count
        if (   isset($this->_type->max_count)
            && !$this->max_count) {
            $this->max_count = $this->_type->max_count;
        }

        // Create sortable
        if ($this->_type->sortable) {
            // Enable jQuery
            midcom::get()->head->enable_jquery();

            // Add the JavaScript file to aid in sorting, if requested for
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
        // controls if required apc-settings for the progressbar are set
        if (   ini_get("apc.rfc1867") == 1
            && ini_get("apc.enabled") == 1) {
            $this->show_progressbar = true;
            $this->progress_id = uniqid("");
            // js Progressbar
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jquery.progressbar/js/jquery.progressbar.js');
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jquery.progressbar/js/progress.functions.js');
            midcom::get()->head->add_jscript(
                "$(document).ready(function() {
                    $(\".progressbar\").progressBar(
                    {
                        boxImage: '" . MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/jquery.progressbar/js/images/progressbar.gif',
                        barImage: {
                            0:  '" . MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/jquery.progressbar/js/images/progressbg_red.gif',
                            30: '" . MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/jquery.progressbar/js/images/progressbg_orange.gif',
                            70: '" . MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/jquery.progressbar/js/images/progressbg_green.gif'
                            },
                        showText: true
                    }
                    );
                });"
            );
        }
    }

    /**
     * Adds the table header to the widget.
     *
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_table_header($frozen)
    {
        if ($frozen) {
            $html = "<table class=\"midcom_helper_datamanager2_widget_downloads\" id=\"{$this->_namespace}{$this->name}\" >\n
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
    private function _add_new_upload_row($frozen)
    {
        // Show only a configured amount of new image rows
        if (   $this->max_count
            && count($this->_type->attachments_info) >= $this->max_count) {
            return;
        }

        // Initialize the string
        $sortable = '';

        if ($this->_type->sortable) {
            $sortable = "<td class=\"index\"></td>\n";
        }

        // Filename column
        $html = "<tr>\n" .
                $sortable .
                "<td class=\"new filename\">";
        $this->_elements['s_new_filename'] = $this->_form->createElement('static', 's_new_filename', '', $html);
        $attributes = array(
            'class' => 'new filename',
            'id'    => "{$this->_namespace}{$this->name}_e_new_filename",
        );
        $this->_elements['e_new_filename'] = $this->_form->createElement('text', 'e_new_filename', '', $attributes);

        // Title Column
        $html = "</td>\n" .
                "<td class=\"new title\">";
        $this->_elements['s_new_title'] = $this->_form->createElement('static', 's_new_title', '', $html);
        $attributes = array(
            'class' => 'new title',
            'id'    => "{$this->_namespace}{$this->name}_e_new_title",
        );
        $this->_elements['e_new_title'] = $this->_form->createElement('text', 'e_new_title', '', $attributes);

        if (!$frozen) {
            // Controls Column
            $html = "</td>\n<td class=\"new upload\">";
            $html .= "<input type=\"hidden\" name=\"APC_UPLOAD_PROGRESS\" id=\"{$this->progress_id}_progress_key\"
                      value=\"" . $this->progress_id . "\" />";
            $this->_elements['s_new_upload'] = $this->_form->createElement('static', 's_new_upload', '', $html);
            $attributes = array(
                'class' => 'new file',
                'id'    => "{$this->_namespace}{$this->name}_e_new_file",
            );
            $this->_elements['e_new_file'] = $this->_form->createElement('file', 'e_new_file', '', $attributes);

            $attributes = array(
                'class' => 'submit new upload',
                'id'    => "{$this->_namespace}{$this->name}_e_new_upload",
            );
            if ($this->show_progressbar) {
                $url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/get_progress.php';
                $attributes['onclick'] = "beginUpload('{$this->progress_id}','{$url}');$(this).prop('disabled', true)";
                $this->_form->setAttribute("onsubmit", "beginUpload('{$this->progress_id}','{$url}')");
            }
            $this->_elements['e_new_upload'] = $this->_form->createElement('submit', "{$this->name}_e_new_upload", $this->_l10n->get('upload file'), $attributes);

            if ($this->show_progressbar) {
                $html = "<span style=\"visibility:hidden;\" class=\"progressbar\"></span>";
                $this->_elements['new_progress'] = & $this->_form->createElement('static', "new_progress", '', $html);
            }
        }

        $html = "</td>\n</tr>\n";
        $this->_elements['s_new_file'] = $this->_form->createElement('static', 's_new_file', '', $html);
    }

    /**
     * Adds a row for an existing attachment
     *
     * @param string $identifier Row identifier
     * @param array $info Row data
     * @param boolean $frozen Set this to true, if you want to skip all elements which cannot be frozen.
     */
    private function _add_attachment_row($identifier, array $info, $frozen)
    {
        // Initialize the string
        $sortable = '';

        if ($this->_type->sortable) {
            $sortable  = "            <td class=\"midcom_helper_datamanager2_helper_sortable\">\n";
            $sortable .= "               <input type=\"text\" class=\"downloads_sortable\" name=\"midcom_helper_datamanager2_sortable[{$this->name}][{$identifier}]\" value=\"{$this->_sort_index}\" />\n";
            $sortable .="             </td>\n";

            $this->_sort_index++;
        }

        // Filename column
        $html = "<tr class=\"midcom_helper_datamanager2_widget_downloads_download\" title=\"{$info['guid']}\">\n" .
                $sortable .
                "<td class=\"exist filename\" title=\"{$info['filename']}\">\n
                 <a href=\"{$info['url']}\" class=\"download\">{$info['filename']}</a>\n
                 </td>\n";
        $this->_elements["s_exist_{$identifier}_filename"] = $this->_form->createElement('static', "s_exist_{$identifier}_filename", '', $html);

        // Title Column, set the value explicitly, as we are sometimes called after the defaults kick in.
        $html = "<td class=\"exist title\" title=\"{$info['description']}\">";
        $this->_elements["s_exist_{$identifier}_title"] = $this->_form->createElement('static', "s_exist_{$identifier}_title", '', $html);
        $attributes = array(
            'class' => 'exist title',
            'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_title",
        );
        $this->_elements["e_exist_{$identifier}_title"] = $this->_form->createElement('text', "e_exist_{$identifier}_title", '', $attributes);
        $this->_elements["e_exist_{$identifier}_title"]->setValue($info['description']);

        if (!$frozen) {
            // Controls Column
            if ($this->_type->attachments[$identifier]->can_do('midgard:update')) {
                $html = "</td>\n<td class=\"exist upload\">";
                $html .= "<input type=\"hidden\" name=\"APC_UPLOAD_PROGRESS\" id=\"{$this->progress_id}_progress_key\"
                          value=\"" . $this->progress_id . "\" />";
                $this->_elements["s_exist_{$identifier}_upload"] = $this->_form->createElement('static', "s_exist_{$identifier}_upload", '', $html);
                $attributes = array(
                    'class' => 'exist file',
                    'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_file",
                );
                $this->_elements["e_exist_{$identifier}_file"] = $this->_form->createElement('file', "e_exist_{$identifier}_file", '', $attributes);

                $attributes = array(
                    'class' => 'submit exist upload',
                    'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_upload",
                );
                if ($this->show_progressbar) {
                    $url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/get_progress.php';
                    $attributes['onclick'] = "beginUpload('{$this->progress_id}','{$url}');$(this).prop('disabled', true)";
                    $this->_form->setAttribute("onsubmit", "beginUpload('{$this->progress_id}','{$url}')");
                }
                $this->_elements["s_exist_{$identifier}_br"] = $this->_form->createElement('static', "s_exist_{$identifier}_upload", '', "<br/>");
                $this->_elements["e_exist_{$identifier}_upload"] = $this->_form->createElement('submit', "{$this->name}_e_exist_{$identifier}_upload", $this->_l10n->get('replace file'), $attributes);
            }
            if ($this->_type->attachments[$identifier]->can_do('midgard:delete')) {
                $attributes = array(
                    'class' => 'submit exist delete',
                    'id'    => "{$this->_namespace}{$this->name}_e_exist_{$identifier}_delete",
                );
                $this->_elements["e_exist_{$identifier}_delete"] = $this->_form->createElement('submit', "{$this->name}_e_exist_{$identifier}_delete", $this->_l10n->get('delete file'), $attributes);
            }

            if ($this->show_progressbar) {
                $html = "<span style=\"visibility:hidden;\" class=\"progressbar\"></span>";
                $this->_elements['new_progress'] = & $this->_form->createElement('static', "new_progress", '', $html);
            }
        }

        $html = "</td>\n</tr>\n";
        $this->_elements["s_exist_{$identifier}_file"] = $this->_form->createElement('static', "s_exist_{$identifier}_file", '', $html);
    }

    /**
     * Adds the table footer.
     */
    private function _add_table_footer()
    {
        $html = "</tbody>\n</table>\n";
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

        foreach ($this->_type->attachments_info as $identifier => $info) {
            $this->_add_attachment_row($identifier, $info, $frozen);
        }
        $this->_add_new_upload_row($frozen);
        $this->_add_table_footer();
    }

    function _extension_to_mimetype($extension, $mimetype)
    {
        switch ($extension) {
            case 'ai':
                return 'application/illustrator';
            case 'eps':
                return 'application/x-eps';
            case 'indd':
                return 'application/x-indesign';
            default:
                return $mimetype;
        }
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

        if ($this->_elements['e_new_file']->isUploadedFile()) {
            $file = $this->_elements['e_new_file']->getValue();
            $title = $values['e_new_title'];
            $filename = $values['e_new_filename'];

            if (!$filename) {
                $filename = $file['name'];
            }
            if (!$title) {
                $title = $filename;
            }

            $identifier = md5(time() . $filename . $file['tmp_name']);

            // In some cases we want to tweak the mimetype based on file extension
            $filename_parts = explode('.', $filename);
            if (count($filename_parts) > 1) {
                $extension = $filename_parts[count($filename_parts) - 1];
                $file['type'] = $this->_extension_to_mimetype($extension, $file['type']);
            }

            if (!$this->_type->add_attachment($identifier, $filename, $title, $file['type'], $file['tmp_name'])) {
                debug_add("Failed to add an attachment to the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * The following checks are made, in order:
     *
     * 1. If the delete button was clicked, the attachment is dropped.
     * 2. If a new file has been uploaded, it replaces the current one.
     * 3. If neither of the above is triggered, the title of the attachment is
     *    synchronized.
     *
     * Calls for attachments which are not listed in the form, will be silently ignored.
     * This may happen, for example, if two users edit the same object simultaneoulsy,
     * or during addition of new elements.
     *
     * @param string $identifier The attachment identifier to check for updates.
     * @param array $values The values associated with our element group (not the full submit value list).
     */
    private function _check_for_update($identifier, $values)
    {
        if (!array_key_exists($identifier, $this->_type->attachments_info)) {
            // The attachment does no longer exist
            return;
        }

        if (array_key_exists("{$this->name}_e_exist_{$identifier}_delete", $values)) {
            if (!$this->_type->delete_attachment($identifier)) {
                debug_add("Failed to delete the attachment {$identifier} on the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
        } elseif (   array_key_exists("e_exist_{$identifier}_file", $this->_elements)
                 && $this->_elements["e_exist_{$identifier}_file"]->isUploadedFile()) {
            $file = $this->_elements["e_exist_{$identifier}_file"]->getValue();
            $title = $values["e_exist_{$identifier}_title"];
            $old_filename = $this->_type->attachments_info[$identifier]['filename'];

            //If the user didn't bother to enter a real title, update it to the new filename
            if ($title == $old_filename) {
                $title = $file['name'];
            }

            if (!$this->_type->update_attachment($identifier, $file['name'], $title, $file['type'], $file['tmp_name'])) {
                debug_add("Failed to update the attachment {$identifier} on the field '{$this->name}'. Ignoring silently.", MIDCOM_LOG_WARN);
            }
        } elseif (   array_key_exists("e_exist_{$identifier}_title", $values)
                 && $values["e_exist_{$identifier}_title"] != $this->_type->attachments_info[$identifier]['description']) {
            $this->_type->update_attachment_title($identifier, $values["e_exist_{$identifier}_title"]);
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

        foreach (array_keys($this->_type->attachments_info) as $identifier) {
            $this->_check_for_update($identifier, $values);
        }

        // Rebuild Widget
        $this->_compute_elements();
        $this->_group->setElements($this->_elements);
    }

    /**
     * Freeze the entire group, special handling applies to skip all elements which cannot be
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
     * Check if the sorted order should be returned to type
     */
    public function sync_type_with_widget($results)
    {
        // NOTE: Updating titles etc is done already on _on_submit
        if (   $this->_type->sortable
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'])
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'][$this->name])) {
            $this->_type->_sorted_list = $_REQUEST['midcom_helper_datamanager2_sortable'][$this->name];
        }
    }

    /**
     * Populates the title fields with their defaults.
     */
    public function get_default()
    {
        if (sizeof($this->_type->attachments_info) == 0) {
            return null;
        }
        $defaults = array();
        foreach ($this->_type->attachments_info as $identifier => $info) {
            $defaults["e_exist_{$identifier}_title"] = $info['description'];
        }
        return array($this->name => $defaults);
    }
}
