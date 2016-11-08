<div>
    <h2><?php echo $data['l10n']->get("my contacts"); ?></h2>

    <input type="hidden" name="url" value="" />
    <div id="add_contact" >
    <script type="text/javascript">
    $(document).ready(function()
    {
        function add_member(event, ui)
        {
            midcom_helper_datamanager2_autocomplete.select(event, ui);
            if ($('#add_contact_selection').val() !== '')
            {
                var value = $('#add_contact_selection').val()
                guid = value.substr(2).substr(0, value.length - 4);
                location.href += 'mycontacts/add/' + guid + '/?return_url=' + location.href;
            }
        }

        var widget_conf =
        {
            id: 'add_contact',
            appendTo: '#add_contact',
            widget_config: <?php echo json_encode($data['widget_config']); ?>,
            placeholder: '<?php echo $data['l10n']->get('add contact'); ?>'
        },
        autocomplete_conf =
        {
            select: add_member,
            open: midcom_helper_datamanager2_autocomplete.open,
            position: {collision: 'fit none'}
        }

        midcom_helper_datamanager2_autocomplete.create_widget(widget_conf, autocomplete_conf);
        $('#add_contact_search_input').show();
    });
    </script>
    </div>

    <dl>