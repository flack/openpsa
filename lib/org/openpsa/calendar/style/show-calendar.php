<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div id="org_openpsa_calendar_widget">
</div>

<script type="text/javascript">
$(document).ready(function() {
    openpsa_calendar_widget.initialize('#org_openpsa_calendar_widget', '&(prefix);', <?php echo json_encode($data['calendar_options']); ?>);
    $('body')
       .on('dialogsaved', '#midcom-dialog', function(e) {
           openpsa_calendar_instance.fullCalendar('refetchEvents');
       });
});
</script>