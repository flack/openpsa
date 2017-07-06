<div class="wide">
    <?php  $data['controller']->display_form();  ?>
    <!-- To open the report in new window we need to set the target via JS -->
    <script type="text/javascript">
    $('.datamanager2 button[type="submit"]').on('click', function()
    {
        if ($(this).hasClass('save')) {
            $(this).closest('form').attr('target', '_blank');
        } else {
            $(this).closest('form').removeAttr('target');
        }
    });
    </script>
</div>