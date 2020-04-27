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

//Pre-made autocomplete configurations
'clever_classes' => [
    'contact' => [
        'class' => 'org_openpsa_contacts_person_dba',
        'component' => 'org.openpsa.contacts',
        'titlefield' => 'name',
        'result_headers' => [
            [
                'title' => 'email',
                'name' => 'email',
            ],
        ],
        'searchfields' => [
            'firstname',
            'lastname',
            'username',
        ],
        'orders' => [
            ['lastname' => 'ASC'],
            ['firstname' => 'ASC'],
        ],
    ],
    'organization' => [
        'class'       => 'org_openpsa_contacts_group_dba',
        'component'   => 'org.openpsa.contacts',
        'titlefield'  => 'official',
        'id_field'     => 'id',
        'searchfields'  => [
            'name',
            'official',
        ],
        'orders'        => [
            ['official'    => 'ASC'],
            ['name'    => 'ASC'],
        ],
    ],
    'article' => [
        'class' => 'midcom_db_article',
        'component' => 'net.nehmer.static',
        'searchfields' => ['title'],
        'orders' => [
            ['title' => 'ASC'],
            ['metadata.published' => 'ASC'],
        ],
        'id_field' => 'guid',
    ],
    'group' => [
        'class' => 'midcom_db_group',
        'component' => 'midgard.admin.asgard',
        'result_headers' => [
            [
                'title' => 'name',
                'name' => 'name',
            ],
        ],
        'searchfields' => [
            'name',
            'official',
        ],
        'orders' => [
            ['extra' => 'ASC'],
            ['metadata.published' => 'ASC'],
        ],
        'id_field' => 'id',
        'categorize_by_parent_label' => true,
    ],
    'task' => [
        'class'       => 'org_openpsa_projects_task_dba',
        'component'   => 'org.openpsa.projects',
        'titlefield'  => 'title',
        'id_field'     => 'id',
        'searchfields'  => [
            'title',
            'project.title'
        ],
        'orders'        => [
            ['title'    => 'ASC']
        ],
        'categorize_by_parent_label' => true
    ],
    'invoice' => [
        'class' => 'org_openpsa_invoices_invoice_dba',
        'component' => 'org.openpsa.invoices',
        'id_field' => 'id',
        'searchfields' => [
            'number',
            'description',
        ],
        'orders' => [
            ['number' => 'ASC'],
        ],
    ],
    'product' => [
        'class'       => 'org_openpsa_products_product_dba',
        'id_field'     => 'id',
        'searchfields'  => [
            'code',
            'title'
        ],
        'result_headers' => [[
            'name' => 'code',
        ]],
        'orders'        => [
            ['title'    => 'ASC'],
            ['code'    => 'ASC'],
        ],
        'categorize_by_parent_label' => true,
    ]
],

'html_purify_config' => [
    'HTML.Attr.Name.UseCDATA' => true,
    'HTML' => [
        /**
         * These two must be changed to something unique to your situation
         * if you change any of the html_purify_xx configs
         *
         * DEVELOPERS: If you change these defaults remember to bump DefinitionRev
         */
        'DefinitionID' => 'Datamanager Default definition set',
        'DefinitionRev' => 3,
        /* NOTE: You must use transitional doctype if you want to use <a name> (see html_purify_HTMLDefinition below) */
        'Doctype' => 'XHTML 1.0 Transitional',
        'TidyLevel' => 'light',
        'SafeObject' => true,
    ],
    'Attr' => [
        /**
         * To disable mangling of id/name attributes in content set this to empty string
         */
        'IDPrefix' => 'user_',
        'AllowedFrameTargets' => ['_blank', '_self', '_top'],
        'EnableID' => true,
    ],
    'Cache' => [
        'SerializerPath' => midcom::get()->getCacheDir() . '/htmlpurifier',
        /* Disable cache while testing
        'DefinitionImpl' => null,
        */
    ],
    'Output' => [
        'FlashCompat' => true,
    ],
],
'html_purify_HTMLDefinition' => [
    /**
     * each value passed in order with call_user_func_array to $def->addAttribute()
     *
     * See http://htmlpurifier.org/docs/enduser-customize.html
     */
    'addAttribute' => [
        ['a', 'name', 'Text'],
        ['a', 'rel', 'Text'],
        ['img', 'hspace', 'Length'],
        ['img', 'vspace', 'Length'],
    ],
    /**
     * each value passed in order with call_user_func_array to $def->addElement()
     *
     * See http://htmlpurifier.org/docs/enduser-customize.html
     */
    'addElement' => [],
],
