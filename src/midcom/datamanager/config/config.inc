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
        'result_headers' => array
        (
            array
            (
                'title' => 'name',
                'name' => 'name',
            ),
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
    'wikipage' => array
    (
        'class' => 'net_nemein_wiki_wikipage',
        'component' => 'net.nemein.wiki',
        'result_headers' => array
        (
            array
            (
                'title' => 'revised',
                'name' => 'revised',
            ),
            array
            (
                'title' => 'title',
                'name' => 'title',
            ),
        ),
        'searchfields' => array
        (
            'title',
        ),
        'orders' => array
        (
            array('title' => 'ASC'),
            array('metadata.published' => 'ASC'),
        ),
        'creation_default_key' => 'title',
    ),
    'article' => array
    (
        'class' => 'midcom_db_article',
        'component' => 'net.nehmer.static',
        'result_headers' => array
        (
            array
            (
                'title' => 'title',
                'name' => 'title',
            ),
        ),
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
    'topic' => array
    (
        'class' => 'midcom_db_topic',
        'component' => 'midcom.admin.folder',
        'result_headers' => array
        (
            array
            (
                'title' => 'extra',
                'name' => 'extra',
            ),
            array
            (
                'title' => 'component',
                'name' => 'component',
            ),
        ),
        'searchfields' => array
        (
            'extra',
            'name',
            'component',
        ),
        'orders' => array
        (
            array('extra' => 'ASC'),
            array('metadata.published' => 'ASC'),
        ),
        'generate_path_for' => 'extra',
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
            array
            (
                'title' => 'official',
                'name' => 'official',
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
    'event' => array
    (
        'class' => 'net_nemein_calendar_event',
        'component' => 'net.nemein.calendar',
        'result_headers' => array
        (
            array
            (
                'title' => 'start',
                'name' => 'start',
            ),
            array
            (
                'title' => 'end',
                'name' => 'end',
            ),
            array
            (
                'title' => 'title',
                'name' => 'title',
            ),
            array
            (
                'title' => 'location',
                'name' => 'location',
            ),
        ),
        'searchfields' => array
        (
            'title',
            'location',
        ),
        'orders' => array
        (
            array('title' => 'ASC'),
            array('start' => 'ASC'),
            array('metadata.published' => 'ASC'),
        ),
        'creation_default_key' => 'title',
    ),
    'task' => array
    (
        'class'       => 'org_openpsa_projects_task_dba',
        'component'   => 'org.openpsa.projects',
        'titlefield'  => 'title',
        'id_field'     => 'id',
        'result_headers' => array
        (
            array
            (
                'title' => 'title',
                'name' => 'title',
            ),
        ),
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
        'titlefield' => 'number',
        'id_field' => 'id',

        'result_headers' => array
        (
            array
            (
                'title' => 'number',
                'name' => 'number',
            ),
        ),
        'get_label_for' => 'number',
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