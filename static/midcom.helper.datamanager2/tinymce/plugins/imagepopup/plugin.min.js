// Load plugin specific language pack
tinymce.PluginManager.requireLangPack('imagepopup');

/**
 * Initializes the plugin, this will be executed after the plugin has been created.
 * This call is done before the editor instance has finished it's initialization o use the onInit event
 * of the editor instance to intercept that event.
 *
 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
 * @param {string} url Absolute URL to where the plugin is located.
 */
tinymce.PluginManager.add('imagepopup', function(ed, url)
{
    // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
    ed.addCommand('mceImagepopup', function() {
        ed.windowManager.open({
            title : 'imagepopup_desc',
            file : ed.getParam("plugin_imagepopup_popupurl"),
            width : 800,
            height : 400,
            inline : 1,
            scrollbars : 'yes'
        }, {
            plugin_url : url // Plugin absolute URL
        });

    });
    // Register buttons
    ed.addButton('imagepopup', {
        title : 'imagepopup_desc',
        cmd : 'mceImagepopup',
        icon : 'image'
    });
    // Register custom keyboard shortcut
    ed.addShortcut('ctrl+i', 'imagepopup.desc', 'mceImagepopup');
});


