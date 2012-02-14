<?php
midcom::get('auth')->require_valid_user();

$user = midcom::get('auth')->user->get_storage();
$api_key = $user->parameter('org.routamc.statusmessage:qaiku', 'apikey');
if (!$api_key)
{
    if (isset($_POST['qaiku_apikey']))
    {
        $user->set_parameter('org.routamc.statusmessage:qaiku', 'apikey', $_POST['qaiku_apikey']);
        $api_key = $_POST['qaiku_apikey'];
    }
    else
    {
        ?>
        <h1>Set your Qaiku API key</h1>

        <p>
        You can get the key from <a href="http://www.qaiku.com/settings/api/">Qaiku</a>.
        </p>

        <form method="post">
            <label>
                <span>API key</span>
                <input type="text" name="qaiku_apikey" />
            </label>
            <input type="submit" value="Save" />
        </form>
        <?php
    }
}

if ($api_key)
{
    $importer = org_routamc_positioning_importer::create('qaiku');
    $coordinates = $importer->get_qaiku_location($user);
    if ($coordinates)
    {
        echo sprintf('According to Qaiku your position is %s', org_routamc_positioning_utils::microformat_location($coordinates['latitude'], $coordinates['longitude']));
    }
    else
    {
        echo "Failed to get position, last error is {$importer->error}";
    }
}
?>