'name' => 'net.nehmer.account',
'icon' => 'stock-icons/16x16/stock_person.png',
'version' => '8.09.9',
'state' => 'stable',
'class_mapping' => array
(
    'net_nehmer_accounts_invites_invite' => 'net_nehmer_account_invites_invite_dba',
    'net_nehmer_account_karma' => 'net_nehmer_account_karma_dba',
),
'package.xml' => array
(
    'summary' => 'Account management and registration',
    'description' => 'Allows users to manage their accounts. New users can register for new accounts as well.',
    'maintainers' => array
    (
        'torben' => array
        (
            'name' => 'Torben Nehmer',
            'email' => 'torben@nehmer.net',
            'role' => 'lead',
            'active' => 'no',
        ),
        'juhana' => array
        (
            'name' => 'Juhana Hirvilahti',
            'email' => 'ihirvila@cc.hut.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'lead',
        ),
        'w_i' => array
        (
            'name' => 'Jerry Jalava',
            'email' => 'jerry.jalava@gmail.com',
            'role' => 'lead',
            'active' => 'no',
        ),
        'xfade' => array
        (
            'name' => 'Niels Breet',
            'email' => 'nbreet@gmail.com',
            'role' => 'lead',
        ),
        'netblade' => array
        (
            'name' => 'Oskari Kokko',
            'email' => 'oskari.kokko@nemein.com',
            'role' => 'lead',
        ),
        'neithan' => array
        (
            'name' => 'Lauri Manner',
            'email' => 'lauri.manner@nemein.com',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
        'org.openpsa.mail' => array(),
    ),
),
'customdata' => array
(
    'midcom.services.cron' => array
    (
        array
        (
            'handler' => 'net_nehmer_account_cron_cleanup',
            'recurrence' => MIDCOM_CRON_DAY,
        ),
        array
        (
            'handler' => 'net_nehmer_account_cron_karma',
            'recurrence' => MIDCOM_CRON_HOUR,
        ),
    ),
),
