<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="file-manager">
    <div class="filelist">
        <?php
        if (!empty($data['files'])) {
            echo "<table class=\"attachments table_widget\" id=\"attachment_table\">\n";
            echo "    <thead>\n";
            echo "        <tr>\n";
            echo "            <th>" . $data['l10n_midcom']->get('title') . "</th>\n";
            echo "            <th>" . $data['l10n']->get('revised on') . "</th>\n";
            echo "            <th>" . $data['l10n']->get('revised by') . "</th>\n";
            echo "            <th>" . $data['l10n']->get('size') . "</th>\n";
            echo "            <th>" . $data['l10n']->get('actions') . "</th>\n";
            echo "        </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";
            foreach ($data['files'] as $file) {
                $mime_icon = midcom_helper_misc::get_mime_icon($file->mimetype);
                $workflow = new midcom\workflow\delete(['object' => $file, 'label' => $file->name]);
                $revisor = midcom::get()->auth->get_user($file->metadata->revisor);
                $class = '';
                if (   isset($data['file'])
                    && $file->name == $data['file']->name) {
                    $class = ' class="selected"';
                }

                $manage_title = $data['l10n']->get('manage object');
                $delete_title = htmlentities(sprintf($data['l10n']->get('delete %s %s'), $data['l10n']->get('attachment'), $file->name), ENT_QUOTES, 'utf-8');
                $last_edit = $file->metadata->revised ?: $file->metadata->created;
                $stat = $file->stat();
                $preview_class = "";
                if (strpos($file->mimetype, "application") !== 0) {
                    $preview_class = 'class="thickbox"';
                }
                $manage_link = $data['router']->generate('object_open', ['guid' => $file->guid]);
                $edit_link = $data['router']->generate('object_attachments_edit', ['guid' => $data['object']->guid, 'filename' => $file->name]);
                $delete_link = $data['router']->generate('object_attachments_delete', ['guid' => $data['object']->guid, 'filename' => $file->name]);

                echo "<tr>\n";
                echo "  <td{$class}>\n";
                echo "    <a href=\"{$edit_link}\">\n";
                echo "      <img src=\"{$mime_icon}\" width=\"16\" height=\"16\"/>\n";
                echo "        {$file->name}\n";
                echo "    </a>\n ";
                echo "  </td>\n";
                echo "  <td>" . strftime('%x %X', $last_edit) . "</td>\n";
                if (!empty($revisor->guid)) {
                    $revisor_link = $data['router']->generate('object_open', ['guid' => $revisor->guid]);
                    echo "<td><a href=\"{$revisor_link}\">{$revisor->name}</a></td>\n";
                } else {
                    echo "<td>&nbsp;</td>\n";
                }
                echo "  <td>" . midcom_helper_misc::filesize_to_string($stat[7]) . "</td>\n";
                echo "  <td>\n";
                echo "    <a {$preview_class} title=\"{$file->name}\" target=\"_self\" href=\"{$prefix}midcom-serveattachmentguid-{$file->guid}/{$file->name}\">\n";
                echo "      <i class=\"fa fa-eye\"></i>\n";
                echo "    </a>\n";
                echo "    <a title=\"{$delete_title}\" href=\"{$delete_link}\" " . $workflow->render_attributes() . ">\n";
                echo "      <i class=\"fa fa-trash\"></i>\n";
                echo "    </a>\n";
                echo "    <a title=\"{$manage_title}\" href=\"{$manage_link}\">\n";
                echo "      <i class=\"fa fa-cog\"></i>\n";
                echo "    </a>\n";
                echo "  </td>\n";
                echo "</tr>\n";
            }

            echo "    </tbody>\n";
            echo "</table>\n";
            echo "<script type=\"text/javascript\">\n";
            echo "// <![CDATA[\n";
            echo "jQuery('#attachment_table').tablesorter(\n";
            echo "  {\n";
            echo "      widgets: ['zebra'],\n";
            echo "      sortList: [[0,0]]\n";
            echo "  });\n";
            echo "// ]]>\n";
            echo "</script>\n";
        } else {
            echo "<p>" . $data['l10n']->get('no files') . "</p>\n";
        }
        ?>
    </div>
    <div class="main">
