<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="midgard_admin_asgard_objecthelper_help">
    <h3><a href="#"><?php echo sprintf($data['l10n']->get('element %s from component %s'), $data['object']->name, midcom::get('i18n')->get_string($data['help_style_element']['component'], $data['help_style_element']['component'])); ?></a></h3>
    <div>
        <label for="midgard_admin_asgard_objecthelper_help_default">
            <?php echo $data['l10n']->get('element defaults'); ?>
        </label>
        <textarea class="default" id="midgard_admin_asgard_objecthelper_help_default" readonly="readonly"><?php echo $data['help_style_element']['default']; ?></textarea>
        <?php
        if ($data['handler_id'] == '____mfa-asgard-object_edit')
        {
            ?>
            <button type="button" class="copy" onclick="if (editor) { editor.setValue(this.parentNode.getElementsByTagName('textarea')[0].innerHTML); } else { document.getElementById('net_nehmer_static_value').innerHTML=this.parentNode.getElementsByTagName('textarea')[0].innerHTML;}">
                <?php echo $data['l10n']->get('copy default value'); ?>
            </button>
            <?php
        }
        ?>
    </div>
</div>

<script type="text/javascript">
// <![CDATA[
$(document).ready(function(){
    $(".midgard_admin_asgard_objecthelper_help").accordion({ header: 'h3', active: false, collapsible: true });
});
// ]]>
</script>
