'default_renderer' => 'simple',
'default_renderer_src' => null,
/**
 * Whether to verify that ImageMagick is in fact available (in type_image(s)) every time 
 * it's needed or not. This can be adjusted in type_config as well.
 * 
 * Setting this to false will win some performance but in case ImageMagick is/goes missing
 * You will also gain really weird and hard-to-debug problems with images handling
 */
'verify_imagemagick' => true, 

// TinyMCE Defaults
// The Theme is only used if the config snippet is not set (an invalid snippet name
// will be silently ignored).
'tinymce_default_theme' => 'simple',
'tinymce_default_config_snippet' => "file://midcom/helper/datamanager2/config/tinymce_default",
'tinymce_use_compressor' => false,
'tinymce_url' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/tinymce',
//'tinymce_default_config_snippet' => "{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/midcom.helper.datamanager2/tinymce",

'html_purify' => true,
'html_purify_config' => array
(
    'HTML.Attr.Name.UseCDATA' => true,
    'HTML' => array
    (
        /**
         * These two must be changed to something unique to your situation 
         * if you change any of the html_purify_xx configs
         *
         * DEVELOPERS: If you change these defaults remember to bump DefinitionRev
         */ 
        'DefinitionID' => 'DM2 Default definition set', 
        'DefinitionRev' => 3,
        /* NOTE: You must use transitional doctype if you want to use <a name> (see html_purify_HTMLDefinition below) */
        'Doctype' => 'XHTML 1.0 Transitional',
        'TidyLevel' => 'light',
        'SafeObject' => true,
    ),
    'Attr' => array
    (
        /**
         * To disable mangling of id/name attributes in content set this to empty string
         */
        'IDPrefix' => 'user_',
        'AllowedFrameTargets' => array
        (
            '_blank',
            '_self',
            '_top',
        ),
        'EnableID' => true,
    ),
    'Cache' => array
    (
        'SerializerPath' => $GLOBALS['midcom_config']['cache_base_directory'] . 'htmlpurifier',
        /* Disable cache while testing
        'DefinitionImpl' => null,
        */
    ),
    'Output' => array
    (
        'FlashCompat' => true,
    ),
),
'html_purify_HTMLDefinition' => array
(
    /**
     * each value passed in order with call_user_func_array to $def->addAttribute()
     *
     * See http://htmlpurifier.org/docs/enduser-customize.html
     */
    'addAttribute' => array
    (
        array('a', 'name', 'Text'),
        array('a', 'rel', 'Text'),
        array('img', 'hspace', 'Length'),
        array('img', 'vspace', 'Length'),
    ),
    /**
     * each value passed in order with call_user_func_array to $def->addElement()
     *
     * See http://htmlpurifier.org/docs/enduser-customize.html
     */
    'addElement' => array
    (
    ),
),
'html_purify_markdown' => true,

'include_metadata_required' => false,

/* This is passed to sprintf, use %s where file name should be */
'type_blobs_avscan_command' => '',
/* Example command using ClamAV
'type_blobs_avscan_command' => 'clamscan --stdout -i --no-summary %s',
*/

// Lock timeout, which defines how many seconds the object should be locked
'lock_timeout' => 1800,
