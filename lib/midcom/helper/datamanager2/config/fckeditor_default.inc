'width' => '600px',
'height' => '450px',

// This is passed to FCKEditor instance on creation
// Available keys can be found from HTML_Quickform_fckeditor.php
'fckconfig' => array
(
    'SkinPath' => 'editor/skins/office2003/',
    'DefaultLanguage' => 'en-uk',
    'UseBROnCarriageReturn' => 'false',
    'StartupFocus' => 'false',
),

// This is converted to javascript
// keys are to come FCKConfig.KEY
// values are used as they are, except that arrays are converted to javascript arrays.
// These override FCKEditors default config options
'js_config' => array
(
    '_FileBrowserLanguage' => "'php'",
    'SmileyPath' => "FCKConfig.BasePath + 'images/smiley/tango/'",
    'SmileyImages' => array
    (
        'face-angel.png', 'face-kiss.png', 'face-smile.png',
        'face-crying.png', 'face-monkey.png', 'face-surprise.png',
        'face-devilish.png', 'face-plain.png', 'face-wink.png',
        'face-glasses.png', 'face-sad.png', 'face-grin.png', 'face-smile-big.png',
    ),
),

// This will be used as default toolbar_set if none is specified in widget config
'toolbar_set' => 'dm2',

// Here one can define globally usable toolbar_sets
// key => array()
'toolbar_set_content' => array
(
),