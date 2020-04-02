$(document).ready(function() {
    $('.content-area')
        .on('click', 'a[href], .preview-image', function(event) {
            event.preventDefault();
            var url = this.href || this.src,
                title = '';

            if ($(this).parent('td').length > 0) {
                title = $(this).parent('td').next().find('input[type="text"]').val();
            }

            window.parent.postMessage({
                mceAction: 'customAction',
                data: {
                    url: url,
                    alt: title
                }
            }, '*');

            window.parent.postMessage({
                mceAction: 'close'
            }, '*');
        })
        .on('mouseover', 'a[href], .preview-image', function() {
            this.title = 'Click to insert';
        });

    if ($('#links').length > 0) {
        $('#links').fancytree({
            click: function(event, data) {
                if (data.targetType === 'title') {
                    window.parent.postMessage({
                        mceAction: 'customAction',
                        data: {
                            url: data.node.data.href,
                            title: data.node.title
                        }
                    }, '*');

                    window.parent.postMessage({
                        mceAction: 'close'
                    }, '*');
                }
            },
            extensions: ['glyph'],
            glyph: {
                preset: "awesome4"
            }
        });
    }
});
