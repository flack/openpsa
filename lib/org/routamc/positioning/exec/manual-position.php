<?php
$config = midcom_baseclasses_components_configuration::get('org.routamc.positioning', 'config');
midcom::get()->auth->require_valid_user();

$user = midcom::get()->auth->user->get_storage();

if (array_key_exists('add_position', $_POST)) {
    $manual = org_routamc_positioning_importer::create('manual', $user->id);

    $manual_position = array();

    foreach (array('geocoder', 'street', 'city', 'country') as $property) {
        if (array_key_exists($property, $_POST)) {
            $manual_position[$property] = $_POST[$property];
        }
    }

    if (!empty($_POST['latitude'])) {
        $manual_position['latitude'] = $_POST['latitude'];
    }

    if (!empty($_POST['longitude'])) {
        $manual_position['longitude'] = $_POST['longitude'];
    }

    $import = $manual->import($manual_position);
    echo $manual->error."<br />\n";
}

$user_position = new org_routamc_positioning_person($user);
$coordinates = $user_position->get_coordinates();

if ($coordinates) {
    echo "<p>" . sprintf('According to Midgard your position is now %s', org_routamc_positioning_utils::pretty_print_coordinates($coordinates['latitude'], $coordinates['longitude'])) . "</p>\n";
}
?>
<form method="post">
    <label>Street <input type="text" name="street" value="Valhallankatu" /></label>,
    <label>City <input type="text" name="city" value="Helsinki" /></label>
    and <label>Country <input type="text" name="country" value="FI" /></label><br />
    <label>
        Geocoder
        <select name="geocoder">
            <option value="city">Local city database</option>
            <option value="geonames">GeoNames</option>
        </select>
    </label>
    <p>OR</p>
    <label>Latitude <input type="text" name="latitude" /></label>
    and <label>Longitude <input type="text" name="longitude" /></label><br />
    <input type="submit" name="add_position" value="Set" />
</form>
