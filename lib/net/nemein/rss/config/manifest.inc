'name' => 'net.nemein.rss',
'icon' => 'rss',
'purecode' => true,
'privileges' => [],
'class_mapping' => [
    'net_nemein_rss_feed' => 'net_nemein_rss_feed_dba',
],
'description' => 'Aggregator that supports populating news items from remote RSS and Atom sources.',
'customdata' => [
    'midcom.services.cron' => [
        [
            'handler' => 'net_nemein_rss_cron_fetchfeeds',
            'recurrence' => MIDCOM_CRON_HOUR,
        ],
    ],
],
