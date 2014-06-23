(function($){

    $.datamanager2 = $.datamanager2 || {};
    $.datamanager2.imagepopup = {
        items: []
    };

    function dm2ImagePopupConverter(item, options)
    {
        var jq_item = $(item);

        var item_type = 'image';
        if (jq_item.hasClass('midcom_helper_datamanager2_widget_downloads_download'))
        {
            item_type = 'attachment';
        }

        var converted_object = {
            guid: jq_item.attr('title'),
            title: '',
            name: '',
            url: '',
            type: item_type
        };

        if (converted_object.guid == '') {
            return;
        }

        converted_object.url = $('a:eq(0)', jq_item).attr('href');
        converted_object.name = $('td.filename', jq_item).attr('title');
        converted_object.title = $('td.title', jq_item).attr('title');

        if (   typeof converted_object.title == 'undefined'
            || converted_object.title == '')
        {
            converted_object.title = converted_object.name;
        }

        $('a', jq_item)
            .prop('title', 'Click to insert')
            .on('click', function(e)
            {
                e.preventDefault();
                $.datamanager2.imagepopup.InsertItem(converted_object.guid.toString());
            });

        $.datamanager2.imagepopup.items.push(converted_object);
    }

    $.datamanager2.imagepopup.InsertItem = function(guid)
    {
        var image_info = {};
        var html_code = '';

        $.each($.datamanager2.imagepopup.items, function(i,n){
            if (n.guid == guid) {
                image_info = n;
                return;
            }
        });

        switch (image_info['type'])  {
            case "attachment":
                html_code = '<a href="' + image_info['url'] + '" >' + image_info['title'] + '</a>';
                break;
            case "image":
            default:
                html_code = '<img src="' + image_info['url'] + '" alt="' +
                                image_info['title'] + '" title="' +
                                image_info['title'] + '"/>';
                break;
        }

        parent.tinyMCE.execCommand("mceInsertContent", true, html_code);
    };

    $.fn.extend({
        dm2ImagePopupConvert: function(options) {
            options = $.extend({}, options);
            return this.each(function(){
                new dm2ImagePopupConverter(this, options);
            });
        }
    });
})(jQuery);