<div>
<?php echo $data['l10n']->get($data['confirmation_message']); ?>
</div>
<form style='padding-top:20px;' action="" method="post">
<input class="save" type="submit" value="<?php echo $data['l10n_midcom']->get('confirm');?>" name="save"/>
<input class="cancel" type="submit" value="<?php echo $data['l10n_midcom']->get('cancel');?>" name="cancel"/>
</form>
