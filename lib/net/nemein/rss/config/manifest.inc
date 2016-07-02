'name' => 'net.nemein.rss',
'icon' => 'net.nemein.rss/rss-16.png',
'purecode' => true,
'version' => '9.0.0+git',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array
(
    'net_nemein_rss_feed' => 'net_nemein_rss_feed_dba',
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
),
