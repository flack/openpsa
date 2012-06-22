<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$formname = substr($data['controller']->formmanager->namespace, 0, -1);
?>
<div class="wide">
    <?php  $data['controller']->display_form();  ?>
    <!-- To open the report in new window we need to set the target via JS -->
    <script type="text/javascript">
        document.<?php echo $formname; ?>.target = '_BLANK';
    </script>
</div>