'geocoder_default_country' => '',

'sms_import_enable' => false,
'sms_import_ip' => null,
'smslib_api' => null, // tambur, clickatell or email2sms
'smslib_uri' => null,
'smslib_client_id' => null,
'smslib_user' => null,
'smslib_password' => null,
'smslib_from' => null,


/**
 * array of arguments acceptable for call_user_func() to be called just before map is shown
 *
 * The callback is passed single argument, an array with first key being reference to the map object
 * (we cannot use direct referencing with call_user_func)
 *
 * Example function:
 * function add_place_x($args)
 * {
 *     $map =& $args[0];
 *     if (strpos($map->id, 'chooser_widget_map'))
 *     {
 *         // Don't add to DM2 location widget map
 *         return;
 *     }
 *     $marker = array
 *     (
 *         ...
 *     );
 *     $map->add_marker($marker);
 * }
 */
'map_onshow_callbacks' => null,

'plazes_developer_key' => '4c637b4469bc11d13e73aa18a55ff6e6',

// By default we use OpenLayers with OpenStreetMap as this doesn't require API key
'map_provider' => 'openlayers',
'map_api_key'  => '',
