'name' => 'org.routamc.positioning',
'icon' => 'stock-icons/16x16/geoclue.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array
(
    'org_routamc_positioning_country' => 'org_routamc_positioning_country_dba',
    'org_routamc_positioning_city' => 'org_routamc_positioning_city_dba',
    'org_routamc_positioning_aerodrome' => 'org_routamc_positioning_aerodrome_dba',
    'org_routamc_positioning_location' => 'org_routamc_positioning_location_dba',
    'org_routamc_positioning_log' => 'org_routamc_positioning_log_dba',
),
'version' => '8.09.9',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Geopositioning library',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'w_i' => array
        (
            'name' => 'Jerry Jalava',
            'email' => 'jerry.jalava@gmail.com',
            'role' => 'developer',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.httplib' => array(),
        'net.nemein.rss' => array(),
        'midcom' => array(),
    ),
),
'customdata' => array
(
    'net.nehmer.account.socialweb' => array
    (
        'qaiku_apikey' => array
        (
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.routamc.statusmessage:qaiku',
                'name' => 'apikey',
            ),
        ),
        'plazes_username' => array
        (
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.routamc.positioning:plazes',
                'name' => 'username',
            ),
        ),
        'plazes_password' => array
        (
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.routamc.positioning:plazes',
                'name' => 'password',
            ),
        ),
        'georss_url' => array
        (
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.routamc.positioning:georss',
                'name' => 'georss_url',
            ),
        ),
    ),
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchplazes',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchfireeagle',
            'recurrence' => MIDCOM_CRON_MINUTE,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchicbm',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchgeorss',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchinstamapper',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
        array
        (
            'handler' => 'org_routamc_positioning_cron_fetchqaiku',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
),
