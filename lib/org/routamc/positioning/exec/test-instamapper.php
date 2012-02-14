<?php
midcom::get('auth')->require_valid_user();

$user = midcom::get('auth')->user->get_storage();
$api_key = $user->parameter('org.routamc.positioning:instamapper', 'api_key');
if (!$api_key)
{
    if (isset($_POST['instamapper_api_key']))
    {
        $user->parameter('org.routamc.positioning:instamapper', 'api_key', $_POST['instamapper_api_key']);
        $api_key = $_POST['instamapper_api_key'];
    }
    else
    {
        ?>
        <h1>Set your InstaMapper API key</h1>

        <p>
        You can get the key from the <a href="http://www.instamapper.com/">InstaMapper</a> site.
        </p>

        <form method="post">
            <label>
                <span>API key</span>
                <input type="text" name="instamapper_api_key" />
            </label>
            <input type="submit" value="Save" />
        </form>
        <?php
    }
}

if ($api_key)
{
    $importer = org_routamc_positioning_importer::create('instamapper');
    $coordinates = $importer->get_instamapper_location($user);
    if ($coordinates)
    {
        echo sprintf('According to InstaMapper your position is %s', org_routamc_positioning_utils::microformat_location($coordinates['latitude'], $coordinates['longitude']));
    }
    else
    {
        echo "Failed to get position, last error is {$importer->error}";
    }
}
?>