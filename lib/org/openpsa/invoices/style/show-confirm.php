<div >
<?php echo $data['l10n']->get('current pdf file was manually uploaded shall it be replaced ?'); ?>
</div>
<form style='padding-top:20px;' action="" method="post">
<input class="save" type="submit" value="<?php echo $_MIDCOM->i18n->get_string('confirm', 'midcom');?>" name="save"/>
<input class="cancel" type="submit" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom');?>" name="cancel"/>
</form>
