<script type="text/javascript">
$(document).ready(function()
{
    $("#select_person").dropdownchecklist({ maxDropHeight: 200 });
    if($(".ui-dropdownchecklist-text").html() == '')
    {
        var void_msg = "<?php echo $data['l10n']->get("choose user"); ?>";
        $(".ui-dropdownchecklist").children(".ui-dropdownchecklist-text").html(void_msg);
    }
});

//submit form + add hidden_field for reset if needed
function send_form(form_id, set)
{
    if (set == 'void' || set == undefined)
    {
        $("#" + form_id).submit();
        return true;
    }
    $("#" + form_id).append("<input type='hidden' name='" + set + "' value = 'true' />");
    $("#" + form_id).submit();
    return true;
}
</script>

<div class="area">
	<?php $data['qf']->render(); ?>
</div>

