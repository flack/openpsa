<?php
/**
 * Converter script for mgd1 databases. Use at you own risk, and remember to backup before!
 */
if (count($argv) != 2)
{
    die("Usage: php update_db.php midgardconffile\n");
}

if (!extension_loaded('midgard2'))
{
    die("Midgard2 is not installed in your PHP environment.\n");
}

//Some fiddling to get autoloader...
$paths = array
(
    dirname(__DIR__) . '/vendor/autoload.php', // installed as root package
    dirname(dirname(dirname(__DIR__))) . '/autoload.php' // installed as dependency
);

// Create a config file
$config = new midgard_config();
if (!$config->read_file($argv[1], false))
{
    die("Failed to load Midgard2 config file");
}

// Open a DB connection with the config
$midgard = midgard_connection::get_instance();
if (!$midgard->open_config($config))
{
    die("Failed to open Midgard database connection to {$argv[1]}: " . $midgard->get_error_string() ."\n");
}

// This will create any classes that might be missing
midgard_storage::create_base_storage();
echo "  Created base storage\n";

$re = new ReflectionExtension('midgard2');
$classes = $re->getClasses();
foreach ($classes as $refclass)
{
    if (!$refclass->isSubclassOf('midgard_object'))
    {
        continue;
    }
    $type = $refclass->getName();

    midgard_storage::update_class_storage($type);
    echo "  Updated storage for {$type}\n";
}
//No idea why this has to be listed explicitly...
midgard_storage::update_class_storage('MidgardRepligard');
echo "  Updated storage for MidgardRepligard\n";

//Copy stuff from multilang tables by doing straight SQL
$m_tables = array
(
    'topic',
    'article',
    'element',
    'net_nemein_redirector_tinyurl',
    'org_openpsa_products_product_group',
    'org_openpsa_products_product',
    'pageelement',
    'page',
    'snippet',
    'topic',
);

$db = mysql_connect($config->host, $config->dbuser, $config->dbpass) or die(mysql_error());

mysql_select_db($config->database, $db);
mysql_set_charset('utf8', $db);

$res = mysql_query('SET NAMES utf8', $db) or die(mysql_error());

foreach ($m_tables as $table)
{
    $stmt = 'UPDATE ' . $table . ', ' . $table . '_i SET ' . $table . '.';

    switch ($table)
    {
        case 'topic':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.extra = ' . $table . '_i.extra, ' . $table . '.description = ' . $table . '_i.description';
            break;
        case 'snippet':
            $stmt .= 'code = ' . $table . '_i.code, ' . $table . '.doc = ' . $table . '_i.doc';
            break;
        case 'page':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.content = ' . $table . '_i.content, ' . $table . '.author = ' . $table . '_i.author, ' . $table . '.owner = ' . $table . '_i.owner';
            break;
        case 'article':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.abstract = ' . $table . '_i.abstract, ' . $table . '.content = ' . $table . '_i.content, ' . $table . '.url = ' . $table . '_i.url';
            break;
        case 'pageelement':
            $stmt .= 'value = ' . $table . '_i.value';
            break;
        case 'element':
            $stmt .= 'value = ' . $table . '_i.value';
            break;
        case 'org_openpsa_products_product':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.description = ' . $table . '_i.description';
            break;
        case 'org_openpsa_products_product_group':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.description = ' . $table . '_i.description';
            break;
        case 'net_nemein_redirector_tinyurl':
            $stmt .= 'title = ' . $table . '_i.title, ' . $table . '.description = ' . $table . '_i.description';
            break;
    }

    $stmt .= ' WHERE ' . $table . '_i.lang = 0 AND ' . $table . '_i.sid = ' . $table . '.id';

    mysql_query($stmt, $db) or die(mysql_error());

    //fix changed snippet parent property
    $stmt = 'UPDATE snippet SET snippetdir = up';
    mysql_query($stmt, $db) or die(mysql_error());
}

//Update all AT entries to host 0 (since mgd2 doesn't support hosts)
echo "  Updating AT entries";
$qb = midcom_services_at_entry_db::new_query_builder();
$results = $qb->execute();
foreach ($results as $result)
{
    $result->host = 0;
    $result->update();
}
echo "  ... Done.\n";

//Migrate accounts to new system
//You'll have to specify authtype manually if you don't want the default one

$GLOBALS['midcom_config_local']['person_class'] = 'openpsa_person';
$GLOBALS['midcom_config_local']['auth_type'] = 'SHA256';

$rootdir = realpath(dirname(__DIR__)) . '/';
require $rootdir . 'lib/midcom.php';

function _migrate_account($person)
{
    $user = new midgard_user();
    $user->authtype = midcom::get('config'->get('auth_type');
    $db_password = $person->password;

    if (substr($person->password, 0, 2) == '**')
    {
        $db_password = midcom_connection::prepare_password(substr($db_password, 2));
    }
    else
    {
        if ($user->authtype !== 'Legacy')
        {
            echo '    Legacy password detected for user ' . $person->username . "\n Resetting to 'password', please change ASAP\n";
            $db_password = midcom_connection::prepare_password('password');
        }
    }

    $user->password = $db_password;
    $user->login = $person->username;

    if (midcom::get('config')->get('person_class') != 'midgard_person')
    {
        $mgd_person = new midgard_person($person->guid);
    }
    else
    {
        $mgd_person = $person;
    }

    $user->set_person($mgd_person);
    $user->active = true;
    try
    {
        $user->create();
    }
    catch (midgard_error_exception $e)
    {
        return false;
    }
    return true;
}

echo "  Migrating accounts\n";
$qb = new midgard_query_builder(midcom::get('config')->get('person_class');
$qb->add_constraint('username', '<>', '');
$results = $qb->execute();

foreach ($results as $person)
{
    if (!_migrate_account($person))
    {
        echo '   Account for ' . $person->firstname . ' ' . $person->lastname . " couldn't be migrated!\n";
    }
}
echo "  Done.\n";
?>
