<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="calendar-container">
    <div id="org_openpsa_calendar_widget">
    </div>
</div>

<script type="text/javascript">
    openpsa_calendar_widget.initialize('#org_openpsa_calendar_widget', '&(prefix);', <?php echo json_encode($data['calendar_options']); ?>);
    $('body')
       .on('dialogsaved', '#midcom-dialog', function(e) {
           openpsa_calendar_instance.refetchEvents();
       });
</script>