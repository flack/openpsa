<?php
// Uncomment to get debug level logs only in this file
/*
$GLOBALS['midcom_config']['log_level'] = 5;
$GLOBALS["midcom_debugger"]->_loglevel = 5;
*/
function org_routamc_positioning_send_sms($to, $message, $from, $config)
{
    $sms_lib = 'org.openpsa.smslib';
    $_MIDCOM->load_library($sms_lib);
    $sms_lib_api = $config->get('smslib_api');
    $sms_lib_location = $config->get('smslib_uri');
    $sms_lib_client_id = $config->get('smslib_client_id');
    $sms_lib_user = $config->get('smslib_user');
    $sms_lib_password = $config->get('smslib_password');

    if (   !$sms_lib_api
        || !$sms_lib_location
        || !$sms_lib_user)
    {
        return false;
    }

    @ini_set('max_execution_time', 0);

    //Initializing SMS broker
    $smsbroker = call_user_func(array(str_replace('.', '_', $sms_lib), 'factory'), $sms_lib_api);
    if (!is_object($smsbroker))
    {
        debug_add(str_replace('.', '_', $sms_lib) . "::factory({$sms_lib_api}) returned: {$smsbroker}", MIDCOM_LOG_ERROR);
        debug_pop();
        return false;
    }
    $smsbroker->location = $sms_lib_location;
    $smsbroker->client_id = $sms_lib_client_id;
    $smsbroker->user = $sms_lib_user;
    $smsbroker->password = $sms_lib_password;

    return $smsbroker->send_sms($to, $message, $from);
}

function org_routamc_positioning_msg_to_utf8($msg)
{
    //TODO: Detect server charset somehow and use that in stead of hardcoded UTF-8
    if (   function_exists('mb_detect_encoding')
        && function_exists('iconv'))
    {
        //TODO: Should we specify a larger list ?? (apparently we should, auto does not inclide latin1)
        $encoding = strtoupper(mb_detect_encoding($msg, 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS'));
        debug_add("msg is {$encoding} encoded");
        if (   $encoding
            && (   $encoding != 'UTF-8'
                /* ASCII needs not be converted it's a subset of UTF-8 */
                && $encoding != 'ASCII'))
        {
            debug_add("converting msg from {$encoding} to UTF-8", MIDCOM_LOG_WARN);
            $stat = iconv($encoding, 'UTF-8', $msg);
            if ($stat)
            {
                $msg = $stat;
            }
        }
    }
    return $msg;
}


$config = $GLOBALS['midcom_component_data']['org.routamc.positioning']['config'];
if (   array_key_exists('msisdn', $_GET)
    && $config->get('sms_import_enable'))
{
    // We're in SMS mode
    if (!is_null($config->get('sms_import_ip')))
    {
        // Check where the request is from
        if ($_SERVER['REMOTE_ADDR'] != $config->get('sms_import_ip'))
        {
            $_MIDCOM->finish();
            _midcom_stop_request();
        }
    }

    if (!$_MIDCOM->auth->request_sudo('org.routamc.positioning'))
    {
        debug_add('Could not get sudo rights (check debug log for details), abort', MIDCOM_LOG_ERROR);
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Could not get sudo rights (check debug log for details), abort');
    }

    // Find matching person
    $person_qb = midcom_db_person::new_query_builder();
    $person_qb->add_constraint('handphone', '=', "+{$_GET['msisdn']}");
    $persons = $person_qb->execute();

    if (count($persons) != 1)
    {
        $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "User matching number +{$_GET['msisdn']} not found");
    }

    $person = $persons[0];

    // Make sure the message is in server charset (which is UTF-8)
    debug_add("_GET['msg'] before charset check/convert: {$_GET['msg']}");
    $_GET['msg'] = org_routamc_positioning_msg_to_utf8($_GET['msg']);
    debug_add("_GET['msg'] after charset check/convert: {$_GET['msg']}");

    if ($_GET['msg'] == 'CANCEL')
    {
        // We must cancel previous report
        $user_position = new org_routamc_positioning_person($person);
        $latest_log = $user_position->seek_log();
        $stat = $latest_log->delete();
        if ($stat)
        {
            //echo "Log deleted";
            org_routamc_positioning_send_sms($person->handphone, 'Log deleted', $config->get('smslib_from'), $config);
        }
        else
        {
            //echo "Failed to delete log, reason " . midcom_application::get_error_string();
            org_routamc_positioning_send_sms($person->handphone, 'Failed to delete log, reason ' . midcom_application::get_error_string(), $config->get('smslib_from'), $config);
        }
        $_MIDCOM->auth->drop_sudo();
        $_MIDCOM->finish();
        _midcom_stop_request();
    }

    $params = explode(',', $_GET['msg']);
    if (count($params) == 2)
    {
        $manual = org_routamc_positioning_importer::create('manual');
        $manual_position = Array();
        $manual_position['city'] = trim($params[0]);
        $manual_position['country'] = trim($params[1]);
        $manual_position['person'] = $person->id;

        $import = $manual->import($manual_position);

        if (!$import)
        {
            // Send error message to user
            org_routamc_positioning_send_sms($person->handphone, "Failed to store position, reason {$manual->error}", $config->get('smslib_from'), $config);
        }
        else
        {
            // Get current coordinates
            $user_position = new org_routamc_positioning_person($person);
            $latest_log = $user_position->seek_log();
            $latest_coord = Array(
                'latitude'  => $latest_log->latitude,
                'longitude' => $latest_log->longitude
            );
            $previous_log = $latest_log->get_previous();

            $message = "New location is " . org_routamc_positioning_utils::pretty_print_coordinates($latest_log->latitude, $latest_log->longitude) . ".";
            if ($previous_log)
            {
                $previous_coord = Array(
                    'latitude'  => $previous_log->latitude,
                    'longitude' => $previous_log->longitude
                );
                $message .= " Previous was " . org_routamc_positioning_utils::get_distance($previous_coord, $latest_coord) . "km " .  org_routamc_positioning_utils::get_bearing($latest_coord, $previous_coord) . ".";
            }

            org_routamc_positioning_send_sms($person->handphone, $message, $config->get('smslib_from'), $config);
        }
    }
    $_MIDCOM->auth->drop_sudo();
    $_MIDCOM->finish();
    _midcom_stop_request();
}
$_MIDCOM->auth->require_valid_user();

$user = $_MIDCOM->auth->user->get_storage();

if (array_key_exists('add_position', $_POST))
{
    $manual = org_routamc_positioning_importer::create('manual');

    $manual_position = Array();
    
    if (array_key_exists('geocoder', $_POST))
    {
        $manual_position['geocoder'] = $_POST['geocoder'];
    }

    if (array_key_exists('street', $_POST))
    {
        $manual_position['street'] = $_POST['street'];
    }

    if (array_key_exists('city', $_POST))
    {
        $manual_position['city'] = $_POST['city'];
    }

    if (array_key_exists('country', $_POST))
    {
        $manual_position['country'] = $_POST['country'];
    }

    if (   array_key_exists('latitude', $_POST)
        && !empty($_POST['latitude']))
    {
        $manual_position['latitude'] = $_POST['latitude'];
    }

    if (   array_key_exists('longitude', $_POST)
        && !empty($_POST['longitude']))
    {
        $manual_position['longitude'] = $_POST['longitude'];
    }

    $import = $manual->import($manual_position);
    echo $manual->error."<br />\n";
}

$user_position = new org_routamc_positioning_person($user);
$coordinates = $user_position->get_coordinates();

if ($coordinates)
{
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
            <option value="yahoo">Yahoo!</option>
        </select>
    </label>
    <p>OR</p>
    <label>Latitude <input type="text" name="latitude" /></label>
    and <label>Longitude <input type="text" name="longitude" /></label><br />
    <input type="submit" name="add_position" value="Set" />
</form>
