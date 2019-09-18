function submit_privileges(form) {
    $('#submit_action', form)
        .prop('name', 'midcom_helper_datamanager2_add')
        .val('add');
    form.submit();
}

$(document).ready(function() {
    $('.maa_permissions_items tr.maa_permissions_rows_row').each(function() {
        $(this).privilege_actions(this.id.substr(14));
    });
});
