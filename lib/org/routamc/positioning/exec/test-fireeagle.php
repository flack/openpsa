<?php
$_MIDCOM->auth->require_valid_user();

$user = $_MIDCOM->auth->user->get_storage();

// Use the FireEagle PHP library from http://fireeagle.yahoo.net/developer/code/php
require_once(MIDCOM_ROOT . '/org/routamc/positioning/lib/fireeagle.php');

$access_key = $user->get_parameter('net.yahoo.fireeagle', 'access_key');
$access_secret = $user->get_parameter('net.yahoo.fireeagle', 'access_secret');

$fireeagle_consumer_key = midcom_baseclasses_components_configuration::get('org.routamc.positioning', 'config')->get('fireeagle_consumer_key');
$fireeagle_consumer_secret = midcom_baseclasses_components_configuration::get('org.routamc.positioning', 'config')->get('fireeagle_consumer_secret');

if (   !$access_key
    || !$access_secret)
{
    $session = new midcom_services_session('org_routamc_positioning_fireeagle');
    if (   isset($_GET['f'])
        && $_GET['f'] == 'start')
    {
        // get a request token + secret from FE and redirect to the authorization page
        $fireeagle = new FireEagle($fireeagle_consumer_key, $fireeagle_consumer_secret);
        $request_token = $fireeagle->getRequestToken();
        if (   !isset($request_token['oauth_token'])
            || !is_string($request_token['oauth_token'])
            || !isset($request_token['oauth_token_secret'])
            || !is_string($request_token['oauth_token_secret']))
        {
            _midcom_stop_request("Failed to get FireEagle request token\n");
        }

        // Save request token to session and redirect user
        $session->set('auth_state', 'start');
        $session->set('request_token', $request_token['oauth_token']);
        $session->set('request_secret', $request_token['oauth_token_secret']);

        ?>
        <p><a href="<?php echo $fireeagle->getAuthorizeURL($request_token['oauth_token']); ?>" target="_blank">Authorize this application</a></p>
        <p><a href="?f=callback">And then click here</a></p>
        <?php
        $_MIDCOM->finish();
        _midcom_stop_request();
    }
    elseif (   isset($_GET['f'])
            && $_GET['f'] == 'callback')
    {
        // the user has authorized us at FE, so now we can pick up our access token + secret
        if (   !$session->exists('auth_state')
            || $session->get('auth_state') != 'start')
        {
            _midcom_stop_request("Out of sequence.");
        }

        $fireeagle = new FireEagle($fireeagle_consumer_key, $fireeagle_consumer_secret, $session->get('request_token'), $session->get('request_secret'));
        $access_token = $fireeagle->getAccessToken();
        if (   !isset($access_token['oauth_token'])
            || !is_string($access_token['oauth_token'])
            || !isset($access_token['oauth_token_secret'])
            || !is_string($access_token['oauth_token_secret']))
        {
            _midcom_stop_request("Failed to get FireEagle access token\n");
        }

        $user->set_parameter('net.yahoo.fireeagle', 'access_key', $access_token['oauth_token']);
        $user->set_parameter('net.yahoo.fireeagle', 'access_secret', $access_token['oauth_token_secret']);

        $_MIDCOM->relocate($_SERVER['SCRIPT_NAME']);
        // This will exit
    }

    ?>
    <p><a href="?f=start">Start Fire Eagle authentication</a></p>
    <?php
    $_MIDCOM->finish();
    _midcom_stop_request();
}

$fireeagle = org_routamc_positioning_importer::create('fireeagle');
$coordinates = $fireeagle->get_fireeagle_location($user);

if ($coordinates)
{
    echo sprintf('According to Fire Eagle your position since %s is %s', strftime('%x %X', $coordinates['date']), org_routamc_positioning_utils::microformat_location($coordinates['latitude'], $coordinates['longitude']));
}
else
{
    echo "Failed to get position, last error is {$fireeagle->error} {$fireeagle->error_string}";
}
?>