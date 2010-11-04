<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="file-manager">
    <div class="filelist">
        <?php
        if (count($data['files']) > 0)
        {
            static $persons = array();

            echo "<table class=\"attachments table_widget\" id=\"attachment_table\">\n";
            echo "    <thead>\n";
            echo "        <tr>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('title', 'midcom') . "</th>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('revised on', 'midgard.admin.asgard') . "</th>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('revised by', 'midgard.admin.asgard') . "</th>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('size', 'midgard.admin.asgard') . "</th>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('actions', 'midgard.admin.asgard') . "</th>\n";
            echo "        </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";
            foreach ($data['files'] as $file)
            {
                $mime_icon = midcom_helper_get_mime_icon($file->mimetype);

                if (!isset($persons[$file->metadata->revisor]))
                {
                    $persons[$file->metadata->revisor] = $_MIDCOM->auth->get_user($file->metadata->revisor);
                }

                $class = '';
                if (   isset($data['file'])
                    && $file->name == $data['file']->name)
                {
                    $class = ' class="selected"';
                }

                $delete_title = sprintf($_MIDCOM->i18n->get_string('delete %s %s', 'midgard.admin.asgard'), $_MIDCOM->i18n->get_string('attachment', 'midgard.admin.asgard'), $file->name);
                echo "<tr>\n";
                echo "  <td{$class}>\n";
                echo "    <a href=\"{$prefix}__mfa/asgard/object/attachments/{$data['object']->guid}/{$file->name}/\">\n";
                echo "      <img src=\"{$mime_icon}\" width=\"16\" height=\"16\"/>\n";
                echo "        {$file->name}\n";
                echo "    </a>\n ";
                echo "  </td>\n";
                $last_edit = ($file->metadata->revised == 0 ) ? $file->metadata->created : $file->metadata->revised;
                echo "  <td>" . strftime('%x %X', $last_edit) . "</td>\n";
                if ($persons[$file->metadata->revisor]->guid)
                {
                    echo "<td><a href=\"{$prefix}__mfa/asgard/object/view/{$persons[$file->metadata->revisor]->guid}/\">{$persons[$file->metadata->revisor]->name}</a></td>\n";
                }
                else
                {
                    echo "<td>&nbsp;</td>\n";
                }
                echo "  <td>" . midcom_helper_filesize_to_string($file->metadata->size) . "</td>\n";
                echo "  <td>\n";
                $class = "";
                if (strpos($file->mimetype, "application") !== 0)
                {
                    $class = 'class="thickbox"';
                }
                echo "    <a {$class} title=\"{$file->name}\" target=\"_self\" href=\"{$prefix}midcom-serveattachmentguid-{$file->guid}/{$file->name}\">\n";
                echo "      <img alt=\"{$file->name}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/view.png\"/>\n";
                echo "    </a> \n";
                echo "    <a title=\"{$delete_title}\" href=\"{$prefix}__mfa/asgard/object/attachments/delete/{$data['object']->guid}/{$file->name}/\">\n";
                echo "      <img alt=\"{$delete_title}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/trash.png\"/>\n";
                echo "    </a>\n";
                $manage_title = $_MIDCOM->i18n->get_string('manage object', 'midgard.admin.asgard');
                echo "    <a title=\"{$manage_title}\" href=\"{$prefix}__mfa/asgard/object/view/{$file->guid}/\">\n";
                echo "      <img alt=\"{$manage_title}\" src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/properties.png\"/>\n";
                echo "    </a>\n";
                echo "  </td>\n";
                echo "</tr>\n";
            }

            echo "    </tbody>\n";
            echo "</table>\n";
            echo "</form>\n";
            echo "<script type=\"text/javascript\">\n";
            echo "// <![CDATA[\n";
            echo "jQuery('#attachment_table').tablesorter(\n";
            echo "  {\n";
            echo "      widgets: ['zebra'],\n";
            echo "      sortList: [[0,0]]\n";
            echo "  });\n";
            echo "// ]]>\n";
            echo "</script>\n";

        }
        else
        {
            echo "<p>" . $_MIDCOM->i18n->get_string('no files', 'midgard.admin.asgard') . "</p>\n";
        }
        ?>
    </div>
    <div class="main">
