<h1><?php printf($data['l10n']->get('edit file %s'), $data['filename']); ?></h1>

<form method="post" enctype="multipart/form-data" class="datamanager2" action="<?php echo midcom_connection::get_url('uri'); ?>" onsubmit="midgard_admin_asgard_file_edit.toggleEditor();">
    <fieldset class="fieldset">
        <legend><?php echo $data['l10n']->get('upload file'); ?></legend>

        <input type="file" name="midgard_admin_asgard_file" />
    </fieldset>
    <?php
    if (array_key_exists($data['file']->mimetype, $data['attachment_text_types'])) {
        // Show file for editing only if it is a text file
        ?>
        <hr />
        <fieldset class="fieldset">
            <legend><?php echo $data['l10n']->get('edit text file'); ?></legend>

            <label>
                <span><?php echo $data['l10n']->get('filename'); ?></span>
                <input class="text" type="text" name="midgard_admin_asgard_filename" value="<?php echo $data['file']->name; ?>" />
            </label>

            <label>
                <span><?php echo $data['l10n']->get('file contents'); ?></span>
                <textarea name="midgard_admin_asgard_contents" cols="60" rows="30" wrap="none" id="midgard_admin_asgard_file_edit" class="codemirror &(data['file_syntax']);"><?php
                    $f = $data['file']->open('r');
        if ($f) {
            fpassthru($f);
        }
        $data['file']->close(); ?></textarea>
            </label>
        </fieldset>
       <?php

    }
    ?>
    <div class="form_toolbar">
        <input type="submit" class="submit save" name="midgard_admin_asgard_save" accesskey="s" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
    </div>
</form>

<form class="urlform" method="post" action="">
    <?php
    $file_url = midcom::get()->permalinks->create_attachment_link($data['file']->guid, $data['file']->name);
    if (!empty($data['file']->name)) {
        $ext = pathinfo($data['file']->name, PATHINFO_EXTENSION);;
        $mime_icon = '<i class="fa fa-file-o"></i><span class="extension">' . $ext . '</span>';
    } else {
        $mime_icon = '<span class="icon no-file"><i class="fa fa-file-o"></i></span>';
    }
    $stat = $data['file']->stat();
    ?>
    <fieldset>
        <legend><?php echo $data['l10n']->get('file information'); ?></legend>

        <div class="icon">
            <a href="&(file_url);">
                <span class="thumb">&(mime_icon:h);</span>
                <?php echo midcom_helper_misc::filesize_to_string($stat[7]) . " {$data['file']->mimetype}"; ?>
            </a>
        </div>

        <label><span><?php echo $data['l10n']->get('url'); ?></span>
            <input class="text" type="text" value="&(file_url);" readonly="readonly" />
        </label>
        <br>
    </fieldset>
</form>
