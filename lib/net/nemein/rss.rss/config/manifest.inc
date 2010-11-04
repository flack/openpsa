'name' => 'net.nemein.rss',
'icon' => 'net.nemein.rss/rss-16.png',
'purecode' => true,
'version' => '8.09.9',
'state' => 'stable',
'privileges' => array(),
'class_definitions' => array
(
    'midcom_dba_classes.inc',
),
'package.xml' => array
(
    'summary' => 'RSS and Atom aggregator',
    'description' => 'Aggregator that supports populating news items from remote RSS and Atom sources.',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.httplib' => array(),
        'net.nemein.tag' => array(),
        'de.bitfolge.feedcreator' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'net_nemein_rss_cron_fetchfeeds',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
    'skip_cache_generation' => true,
),
