<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div id="treemenu">
<?php
$data['handler']->render_groups($data['groups']);
?>
</div>
<script type="text/javascript">

      $(document).ready(function () {
        $("#treemenu").dynatree(
        {
            minExpandLevel: 1,
            persist: true,
            cookie: {path: "<?php echo $prefix; ?>"},
            cookieId: "ui-dynatree-openpsa-groups-cookie",
            clickFolderMode: 2,
            autoCollapse: false,

            onActivate: function(dtnode) {
                if (typeof dtnode.data.url !== 'undefined')
                {
                    window.location.href = dtnode.data.url;
                }
            }
        });
      });

</script>