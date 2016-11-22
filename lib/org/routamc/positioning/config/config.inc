'geocoder_default_country' => '',

/**
 * array of arguments acceptable for call_user_func() to be called just before map is shown
 *
 * The callback is passed single argument, an array with first key being reference to the map object
 * (we cannot use direct referencing with call_user_func)
 *
 * Example function:
 * function add_place_x($args)
 * {
 *     $map = $args[0];
 *     if (strpos($map->id, 'chooser_widget_map')) {
 *         // Don't add to DM2 location widget map
 *         return;
 *     }
 *     $marker = array(
 *         ...
 *     );
 *     $map->add_marker($marker);
 * }
 */
'map_onshow_callbacks' => null,

// By default we use OpenLayers with OpenStreetMap as this doesn't require API key
'map_provider' => 'openlayers',
'map_api_key'  => '',
