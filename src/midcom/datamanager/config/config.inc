'templates' => [
    'view' => '\midcom\datamanager\template\view',
    'form' => '\midcom\datamanager\template\form',
    'csv' => '\midcom\datamanager\template\csv',
],

// TinyMCE Defaults
// The Theme is only used if the config snippet is not set (an invalid snippet name
// will be silently ignored).
'tinymce_default_theme' => 'simple',
'tinymce_default_config_snippet' => "file://midcom/datamanager/config/tinymce_default",
'tinymce_url' => MIDCOM_STATIC_URL . '/midcom.datamanager/tinymce',

'codemirror_config_snippet' => "file://midcom/datamanager/config/codemirror_default",

//Pre-made chooser configurations
'clever_classes' => array
(
    'contact' => array
    (
        'class' => 'org_openpsa_contacts_person_dba',
        'component' => 'org.openpsa.contacts',
        'titlefield' => 'name',
        'result_headers' => array
        (
            array
            (
                'title' => 'email',
                'name' => 'email',
            ),
        ),
        'searchfields' => array
        (
            'firstname',
            'lastname',
            'username',
        ),
        'orders' => array
        (
            array('lastname' => 'ASC'),
            array('firstname' => 'ASC'),
        ),
    ),
    'article' => array
    (
        'class' => 'midcom_db_article',
        'component' => 'net.nehmer.static',
        'searchfields' => array
        (
            'title'
        ),
        'orders' => array
        (
            array('title' => 'ASC'),
            array('metadata.published' => 'ASC'),
        ),
        'id_field' => 'guid',
    ),
    'group' => array
    (
        'class' => 'midcom_db_group',
        'component' => 'midgard.admin.asgard',
        'result_headers' => array
        (
            array
            (
                'title' => 'name',
                'name' => 'name',
            ),
        ),
        'searchfields' => array
        (
            'name',
            'official',
        ),
        'orders' => array
        (
            array('extra' => 'ASC'),
            array('metadata.published' => 'ASC'),
        ),
        'id_field' => 'id',
        'generate_path_for' => 'name',
    ),
    'task' => array
    (
        'class'       => 'org_openpsa_projects_task_dba',
        'component'   => 'org.openpsa.projects',
        'titlefield'  => 'title',
        'id_field'     => 'id',
        'searchfields'  => array
        (
            'title',
            'project.title'
        ),
        'orders'        => array
        (
            array('title'    => 'ASC')
        ),
        'categorize_by_parent_label' => true
    ),
    'invoice' => array
    (
        'class' => 'org_openpsa_invoices_invoice_dba',
        'component' => 'org.openpsa.invoices',
        'id_field' => 'id',
        'searchfields' => array
        (
            'number',
            'description',
        ),
        'orders' => array
        (
            array('number' => 'ASC'),
        ),
    ),
),

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
        'SerializerPath' => midcom::get()->config->get('cache_base_directory') . 'htmlpurifier',
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
