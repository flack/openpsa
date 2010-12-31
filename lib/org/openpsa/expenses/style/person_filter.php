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
function send_form(form_id , set)
{
    if(set == 'void' || set == undefined)
    {
        $("#"+form_id).submit();
        return true;
    }
    $("#"+form_id).append("<input type='hidden' name='" + set + "' value = 'true' />");
    $("#"+form_id).submit();
    return true;
}
</script>

<div class="area">
    <form id = 'person_form' action="" method="post">
        <select id="select_person" name="person[]" multiple="multiple" >
            <?php
            foreach($data['filter_persons'] as $person)
            {
            ?>
                <option value="<?php echo $person['userid'];?>"
                <?php
                if($person['selected'] == true)
                {
                    echo "selected=\"selected\"";
                }
                ?>
                >
                <?php echo $person['username'];?>
                </option>
            <?php
            }
            ?>
        </select>
        <img src ="<?php echo MIDCOM_STATIC_URL ;?>/stock-icons/16x16/ok.png" onclick="send_form('person_for, 'void')" title="<?php echo $data['l10n']->get("apply"); ?>" />
        <img src ="<?php echo MIDCOM_STATIC_URL ;?>/stock-icons/16x16/cancel.png" onclick="send_form('person_form' , 'unset_filter')" title="<?php echo $data['l10n']->get("unset"); ?>" />
    </form>
</div>

