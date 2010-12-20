#!/usr/bin/php
<?php
/**
 * @author Eero af Heurlin
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 */
error_reporting(E_ALL);
require_once('Console/Getargs.php');
$path_parts = explode('/', dirname(__FILE__));
array_pop($path_parts);
require_once(implode('/', $path_parts) . '/nonmidcom.php');


$opts_config = array();
$opts_config['user'] = array
(
    'short' => 'u',
    'max'   => 1,
    'min'   => 0,
    'desc'  => 'Username to log in with',
    'default' => '',
);
$opts_config['password'] = array
(
    'short' => 'p',
    'max'   => 1,
    'min'   => 0,
    'desc'  => 'Password to log in with',
    'default' => '',
);
$opts_config['variable'] = array
(
    'short' => 'v',
    'max'   => 1,
    'min'   => 0,
    'desc'  => 'POST variable name',
    'default' => 'org_openpsa_httplib_mda',
);
$opts_config[CONSOLE_GETARGS_PARAMS] = array
(
    'max'   => 1,
    'min'   => 1,
    'desc'  => 'URL to POST to',
);

$args = Console_Getargs::factory($opts_config);

$url = false;
if (method_exists($args, 'getValue'))
{
    $url = $args->getValue('parameters');
    if (is_array($url))
    {
        $url = $url[0];
    }
}

if (   PEAR::isError($args)
    || empty($url))
{
    $header = "Usage: " . basename(__FILE__) ." [options] URL\n\n" ;
    if (   empty($url)
        || $args->getCode() === CONSOLE_GETARGS_HELP)
    {
        echo Console_Getargs::getHelp($opts_config, $header)."\n";
    }
    else if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER)
    {
        echo Console_Getargs::getHelp($opts_config, $header, $args->getMessage())."\n";
    }

    exit(1);
}

$input = '';
while (!feof(STDIN))
{
    $input .= fread(STDIN, 1024);
}
fclose(STDIN);

$client = new org_openpsa_httplib();
if (   $args->isDefined('user')
    && $args->isDefined('password'))
{
    $client->basicauth = array
    (
        'user' => $args->getValue('user'),
        'password' => $args->getValue('password'),
    );
}
$varname = false;
if ($args->isDefined('variable'))
{
    $varname = $args->getValue('variable');
}
if (empty($varname))
{
    $varname = 'org_openpsa_httplib_mda';
}

$var = array($varname => &$input);

$response = $client->post($url, $var);
if ($response === false)
{
    echo "\nERROR: Got '{$client->error}' when POSTing to {$url}";
    if (is_callable($client->_client->getResponseBody()))
    {
        $body = $client->_client->getResponseBody();
        echo ", see below\n===\n{$body}\n===\n";
    }
    else
    {
        echo "\n\n";
    }
    exit(1);
}
if (stristr($response, 'error'))
{
    echo "\nERROR: 'error' found in response when POSTing to {$url}, see below\n===\n{$response}\n===\n";
    exit(1);
}
if (!preg_match('/OK$/', trim($response)))
{
    echo "ERROR: when POSTing to {$url}\n last line of response is not 'OK', see below\n===\n{$response}\n===\n";
    exit(1);
}

exit(0);
?>