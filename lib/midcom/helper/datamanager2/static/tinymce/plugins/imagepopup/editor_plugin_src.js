/**
 * $RCSfile$
 * $Revision: 4266 $
 * $Date: 2006-10-01 18:53:32 +0300 (Sun, 01 Oct 2006) $
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('imagepopup');
	tinymce.create('tinymce.plugins.ImagepopupPlugin', {
	
		/**
		* Initializes the plugin, this will be executed after the plugin has been created.
		* This call is done before the editor instance has finished it's initialization o use the onInit event
		* of the editor instance to intercept that event.
		*
		* @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		* @param {string} url Absolute URL to where the plugin is located.
		*/
		init : function(ed, url) {
// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mceImagepopup', function() {
				ed.windowManager.open({
					file : tinyMCE.activeEditor.getParam("plugin_imagepopup_popupurl"),
					width : 800,
					height : 400,
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					some_custom_arg : 'custom arg' // Custom argument
				});

			});
			// Register buttons
			ed.addButton('imagepopup', {
				title : 'imagepopup.desc',
				cmd : 'mceImagepopup',
				image : url + '/img/image.gif'
			});
			// Register custom keyboard shortcut
			ed.addShortcut('ctrl+i', 'imagepopup.desc', 'mceImagepopup');
		},
	
		/**
		* Returns information about the plugin as a name/value array.
		* The current keys are longname, author, authorurl, infourl and version.
		*
		* @returns Name/value array containing information about the plugin.
		* @type Array 
		*/
		getInfo : function() {
			return {
				longname : 'Image popup plugin',
				author : 'Tarjei Huse',
				authorurl : 'http://www.midgard-project.org',
				infourl : 'http://www.midgard-project.org/documentation/images-in-midcom/',
				version : "1.0"
			};
		}
	
	});

	// Register plugin
	tinymce.PluginManager.add('imagepopup', tinymce.plugins.ImagepopupPlugin);
})();