</ul></div>
<script type="text/javascript">

      $(document).ready(function () {
        $("#treemenu").dynatree({

            title: "openpsa root",
            rootVisible: false,
            minExpandLevel: 2,
            persist: true,
            cookiePath: "/",
            cookieId: "ui-dynatree-openpsa-cookie",
            clickFolderMode: 2,
            autoCollapse: false,



            onClick: function(dtnode, event) {
                event_string = event.target.toString();
                if( dtnode.data.url && (event_string.search("http") > -1))
                {
                    window.location.href = dtnode.data.url;
                }
            }

        });
      });

</script>