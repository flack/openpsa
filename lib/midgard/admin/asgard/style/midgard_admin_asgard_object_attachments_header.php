<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="file-manager">
    <div class="filelist">
        <?php
        if (count($data['files']) > 0) {
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

                $delete_title = htmlentities(sprintf($data['l10n']->get('delete %s %s'), $data['l10n']->get('attachment'), $file->name), ENT_QUOTES, 'utf-8');
                echo "<tr>\n";
                echo "  <td{$class}>\n";
                echo "    <a href=\"{$prefix}__mfa/asgard/object/attachments/{$data['object']->guid}/{$file->name}/\">\n";
                echo "      <img src=\"{$mime_icon}\" width=\"16\" height=\"16\"/>\n";
                echo "        {$file->name}\n";
                echo "    </a>\n ";
                echo "  </td>\n";
                $last_edit = $file->metadata->revised ?: $file->metadata->created;
                echo "  <td>" . strftime('%x %X', $last_edit) . "</td>\n";
                if (!empty($revisor->guid)) {
                    echo "<td><a href=\"{$prefix}__mfa/asgard/object/open/{$revisor->guid}/\">{$revisor->name}</a></td>\n";
                } else {
                    echo "<td>&nbsp;</td>\n";
                }
                $stat = $file->stat();
                echo "  <td>" . midcom_helper_misc::filesize_to_string($stat[7]) . "</td>\n";
                echo "  <td>\n";
                $class = "";
                if (strpos($file->mimetype, "application") !== 0) {
                    $class = 'class="thickbox"';
                }
                echo "    <a {$class} title=\"{$file->name}\" target=\"_self\" href=\"{$prefix}midcom-serveattachmentguid-{$file->guid}/{$file->name}\">\n";
                echo "      <img alt=\"{$file->name}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/view.png\"/>\n";
                echo "    </a> \n";
                echo "    <a title=\"{$delete_title}\" href=\"{$prefix}__mfa/asgard/object/attachments/delete/{$data['object']->guid}/{$file->name}/\" " . $workflow->render_attributes() . ">\n";
                echo "      <img alt=\"{$delete_title}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/trash.png\"/>\n";
                echo "    </a>\n";
                $manage_title = $data['l10n']->get('manage object');
                echo "    <a title=\"{$manage_title}\" href=\"{$prefix}__mfa/asgard/object/open/{$file->guid}/\">\n";
                echo "      <img alt=\"{$manage_title}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/properties.png\"/>\n";
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
