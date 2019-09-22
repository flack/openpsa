const tiny = {
    filepicker: function(title, url, suffix) {
        return function(callback, value, meta) {
            var height = Math.min(document.body.clientHeight - 50, 600),
                width = Math.min(document.body.clientWidth - 20, 800);
            tinymce.activeEditor.windowManager.open({
                title: title,
                url: url + meta.filetype + '/' + suffix,
                width: width,
                height: height
            }, {
                oninsert: function(url, meta) {
                    callback(url, meta);
                }
            });
        };
    },
    image_upload_handler: function(url) {
        return function(blobInfo, success, failure) {
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open('POST', url);
            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }

                json = JSON.parse(xhr.responseText);

                if (!json || typeof json.location != 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }

                success(json.location);
            };

            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        };
    }
};
