<div class="wide">
    <?php  $data['controller']->display_form();  ?>
    <!-- To open the report in new window we need to set the target via JS -->
    <script type="text/javascript">
    $('.datamanager2 button[type="submit"].save').attr('formtarget', '_blank');
    </script>
</div>