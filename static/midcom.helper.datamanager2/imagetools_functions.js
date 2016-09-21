var imagetools_functions =
{
    setup: function(editor) 
    {
        editor.on('click', function(e) 
        {
            var node = tinymce.activeEditor.selection.getNode();
            if(node.hasAttribute("src"))
            {
                original = node.src.split("/").pop();
            }
        });
    },
    images_upload_handler: function(url)
    {
        return function(blobInfo, success, failure)
        {
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = true;
            xhr.open('POST', tinyMCE.settings.url);

            xhr.onload = function() 
            {
                var json;
                if(xhr.status != 200) 
                {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }

                json = JSON.parse(xhr.responseText);

                if(!json || typeof json.location != 'string') 
                {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }
              
                success(json.location);
            };
             
            var name = original.split(".").shift() + "." + blobInfo.filename().split(".").pop();
            formData = new FormData();
            formData.append('file', blobInfo.blob(), name);
            xhr.send(formData);
        }
    }
};