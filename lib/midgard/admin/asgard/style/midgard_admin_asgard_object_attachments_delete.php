<h1><?php echo sprintf($_MIDCOM->i18n->get_string('delete file %s', 'midcom.admin.styleeditor'), $data['filename']); ?></h1>
<p><?php echo sprintf($_MIDCOM->i18n->get_string('confirm delete of file %s', 'midcom.admin.styleeditor'), $data['filename']); ?></p>
<?php
if (array_key_exists($data['file']->mimetype, $data['attachment_text_types']))
{
    // Show file for preview only if it is a text file
    ?>
            <textarea name="midcom_admin_styleeditor_contents" cols="60" rows="30" wrap="none" id="midcom_admin_styleeditor_file_edit" readonly="readonly" class="editarea &(data['file_syntax']);"><?php
                $f = $data['file']->open('r');
                if ($f)
                {
                    fpassthru($f);
                }
                $data['file']->close();
            ?></textarea>
   <?php
}
?>
<form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" class="datamanager2">
    <div class="form_toolbar">
        <input type="submit" class="delete" name="f_confirm" value="<?php echo $_MIDCOM->i18n->get_string('delete', 'midcom'); ?>" />
        <input type="submit" class="cancel" name="f_cancel" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom'); ?>" />
    </div>
</form>