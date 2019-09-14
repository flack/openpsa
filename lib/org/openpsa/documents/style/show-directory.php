<?php
$url = $data['router']->generate('finder-connector');
?>

<div class="wide">
<h1><?php echo $data['directory']->extra; ?></h1>
<div id="elfinder"></div>
</div>

<script type="text/javascript">
        function get_available_height() {
            return $(window).height() - $('#elfinder').offset().top - 15;
        }

        function resize() {
            var available = get_available_height();
            if ($('#elfinder').height() !== available) {
                $('#elfinder')
                    .css('width', 'auto')
                    .height(available)
                    .trigger('resize');
            }
        }

        $('#elfinder').elfinder({
            url : '&(url);',
            defaultView: 'list',
            lang: '&(data["lang"]);',
            cssAutoLoad: false,
            uiOptions: {
                cwd: {
                    listView : {
                        columns : ['owner', 'date', 'size', 'kind'],
                        columnsCustomName : {
                            owner : '<?php echo $data['l10n']->get("creator"); ?>',
                        }
                    }
                },
                toolbar : [
                    ['back', 'up', 'forward'],
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
            height: get_available_height(),
            handlers:  {
                resize: resize,
                load: resize
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
            sound: false
        });
</script>