'name' => 'net.nehmer.static',
'icon' => 'stock-icons/16x16/document.png',
'class_mapping' => array
(
    'net_nehmer_static_link' => 'net_nehmer_static_link_dba',
),
'description' => 'Static content display component emulating regular directory of HTML files',
'watches' => array
(
    array
    (
        'classes' => array('midcom_db_article'),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
