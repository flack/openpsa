'name' => 'net.nemein.rss',
'icon' => 'net.nemein.rss/rss-16.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
    'net_nemein_rss_feed' => 'net_nemein_rss_feed_dba',
),
'description' => 'Aggregator that supports populating news items from remote RSS and Atom sources.',
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
