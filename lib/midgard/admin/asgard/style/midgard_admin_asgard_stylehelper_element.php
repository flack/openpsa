<div class="midgard_admin_asgard_stylehelper_help">
    <h3><?php printf($data['l10n']->get('element %s from component %s'), $data['object']->name, midcom::get()->i18n->get_string($data['help_style_element']['component'], $data['help_style_element']['component'])); ?></h3>
    <div>
        <label for="midgard_admin_asgard_stylehelper_help_default">
            <?php echo $data['l10n']->get('element defaults'); ?>
        </label>
        <textarea class="default" id="midgard_admin_asgard_stylehelper_help_default" readonly="readonly"><?php echo $data['help_style_element']['default']; ?></textarea>
        <?php
        if ($data['handler_id'] == 'object_edit') {
            ?>
            <button type="button" class="copy">
                <i class="fa fa-clone"></i>
                <?php echo $data['l10n']->get('copy to editor'); ?>
            </button>
            <?php

        }
        ?>
    </div>
</div>

<script type="text/javascript">
    $(".midgard_admin_asgard_stylehelper_help").accordion({ header: 'h3', active: false, collapsible: true });

    $('.midgard_admin_asgard_stylehelper_help button.copy').on('click', function() {
        var field_id = $('.object_edit form.datamanager2').attr('id') + '_value',
            inserttext = $(this).parent().find('textarea').text();

        if (typeof editors[field_id] !== 'undefined') {
            editors[field_id].setValue(inserttext);
        } else {
            $('#' + field_id).val(inserttext);
        }
    });
</script>
