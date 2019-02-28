var tiny = {
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
    imagetools: {
        map: new Map(),
        setup: function(editor) {
            editor.on('click', function() {
                var node = tinymce.activeEditor.selection.getNode();
                if (node.hasAttribute("src")) {
            	    tiny.imagetools.map.set(tinyMCE.activeEditor.id, node.src.split("/").pop());
                }
            });
        },
        upload_handler: function(url) {
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

                var name = tiny.imagetools.map.get(tinyMCE.activeEditor.id).split(".").shift() + "." + blobInfo.filename().split(".").pop();
                formData = new FormData();
                formData.append('file', blobInfo.blob(), name);
                xhr.send(formData);
            };
        }
    }
};
