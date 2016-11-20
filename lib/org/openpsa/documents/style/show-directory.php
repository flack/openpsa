<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="wide">
<h1><?php echo $data['directory']->extra; ?></h1>
<div id="elfinder"></div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#elfinder').elfinder({
            url : '&(prefix);connector/',
            defaultView: 'list',
            lang: '&(data["lang"]);',
            uiOptions : {
                cwd : {
                    listView : {
                        columns : ['owner', 'date', 'size', 'kind'],
                        columnsCustomName : {
                            owner : '<?php echo $data['l10n']->get("creator"); ?>',
                        }
                    }
                },
                toolbar : [
                    ['back', 'up', 'forward'],
                    ['reload'],
                    ['mkdir', 'upload'],
                    ['details', 'download'],
                    ['info'],
                    ['quicklook'],
                    ['copy', 'cut', 'paste'],
                    ['rm'],
                    ['duplicate', 'rename'],
                    ['search'],
                    ['view'],
                    ['help']
                ]
            },
            handlers:  {
                resize: function(event, elfinder) {
                    if ($('#elfinder').height() !== $('#elfinder').parent().parent().height() - 2) {
                        $('#elfinder')
                            .css('width', 'auto')
                            .height($('#elfinder').parent().parent().height() - 2)
                            .trigger('resize');
                    }
                }
            },
            sortRules : {
                owner : function(file1, file2) {
                    var name1 = $('.family-name', file1.owner).text() + '//' + $('.given-name', file1.owner).text(),
                        name2 = $('.family-name', file2.owner).text() + '//' + $('.given-name', file2.owner).text();

                    return name1.toLowerCase().localeCompare(name2.toLowerCase());
                }
            },
            commands : [
                'open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
                'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
                'cut', 'paste', 'edit', 'search', 'info', 'view', 'help',
                'sort', 'details'
            ],
            contextmenu : {
                // navbarfolder menu
                navbar : ['details', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],

                // current directory menu
                cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],

                // current directory file menu
                files  : [
                    'getfile', '|','details', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
                    'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info'
                ]
            },
        });
    });
</script>