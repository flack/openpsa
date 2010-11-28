#!/usr/bin/php
<?php
/**
 * To be used with the various email import facilities of OpenPSA 2
 * takes single argument of URL to post to and message from stdin as usual
 * Makes a single http POST to the given url, message is in 'message_source'
 * in future extra variables (like for auth or something) may be added
 */
require_once 'HTTP/Client.php';
// Get the configuration arguments
if ($argc != 2)
{
    error_log("Usage: php bounce_detector.php HANDLER_URL");
    exit(0);
}
$POST_TO = $argv[1];

//Open stdin by hand
$stdin = fopen('php://stdin', 'r');
if (!$stdin)
{
    error_log("Cannot open stdin");
    exit(1);
}
//Get the message source from it
$message_source = '';
while (!feof($stdin))
{
    $message_source .= fread($stdin, 4096);
}
fclose($stdin);

//Sanity check
if (empty($message_source))
{
    error_log('Got empty message, aborting');
    exit(1);
}

//Post to target
$client = new HTTP_Client();
$stat = $client->post($POST_TO, array('message_source' => $message_source));
$response = $client->currentResponse();
//If anything at all goes wrong dump debug data and exit with errorcode
if (   $stat != 200
    || $response['code'] != 200
    || stristr($response['body'], 'error'))
{
    ob_start();
    print_r($response);
    $response_str = ob_get_contents();
    ob_end_clean();
    error_log("Error posting message to: {$POST_TO} ({$stat}), dumping response data:\n===\n{$response_str}===\n");
    exit(1);
}

exit(0);
?>