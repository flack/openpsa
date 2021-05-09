<div>
    <h2><?php echo $data['l10n']->get("my contacts"); ?></h2>

    <input type="hidden" name="url" value="" />
    <div id="add_contact">
    <input type="text" id="add_contact_search_input">
    <script type="text/javascript">
        midcom_helper_datamanager2_autocomplete.create_widget({
            id: 'add_contact',
            widget_config: <?php echo json_encode($data['widget_config']); ?>,
            placeholder: '<?php echo $data['l10n']->get('add contact'); ?>',
            input: $('#add_contact_search_input')
        },
        {
            select: function (event, ui)
            {
                midcom_helper_datamanager2_autocomplete.select(event, ui);
                if ($('#add_contact_selection').val() !== '') {
                    let value = $('#add_contact_selection').val(),
                        guid = value.substr(2).substr(0, value.length - 4);
                    location.href += 'mycontacts/add/' + guid + '/?return_url=' + location.href;
                }
            },
            open: midcom_helper_datamanager2_autocomplete.open,
            position: {collision: 'fit none'}
        });
    </script>
    </div>

    <dl>