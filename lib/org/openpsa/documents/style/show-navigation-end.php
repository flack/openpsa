</ul></div>
<script type="text/javascript">

      $(document).ready(function () {
        $("#treemenu").dynatree(
        {
            minExpandLevel: 2,
            persist: true,
            cookie: {path: "/"},
            cookieId: "ui-dynatree-openpsa-documents-cookie",
            clickFolderMode: 2,
            autoCollapse: false,
            debugLevel: -1,

            onCustomRender: function(dtnode)
            {
                var url = '#',
                tooltip = dtnode.data.tooltip ? " title='" + dtnode.data.tooltip + "'" : "";

                if (typeof dtnode.data.href !== 'undefined')
                {
                    url = dtnode.data.href;
                }
                return '<a href="' + url + '" class="' + dtnode.tree.options.classNames.title + '"' + tooltip + '>' + dtnode.data.title + '</a>';

            },
            onClick: function(dtnode, event) {
                event_string = event.target.toString();
                if (dtnode.data.href && (event_string.search("http") > -1))
                {
                    window.location.href = dtnode.data.href;
                }
            }
        });
      });

</script>