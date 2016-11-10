<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Positioning widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * It can only be bound to a position type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * Example:
 'location' => Array
 (
     'title' => 'location',
     'storage' => null,
     'type' => 'org_routamc_positioning_dm2_type',
     'widget' => 'org_routamc_positioning_dm2_widget',
     'widget_config' => Array
     (
         'service' => 'geonames', //Possible values are city, geonames
     ),
 ),
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_dm2_widget extends midcom_helper_datamanager2_widget
{
    /**
     * id of the element
     *
     * @var String
     */
    private $_element_id = "positioning_widget";

    /**
     * List of enabled positioning methods
     * Available methods: place, map, coordinates
     * Defaults to all.
     *
     * @var array
     */
    public $enabled_methods = null;

    /**
     * The service backend to use for searches. Defaults to geonames
     */
    var $service = null;

    /**
     * List of defaults used in location inputs
     * key => value pairs (ie. 'country' => 'FI')
     *
     * @var array
     */
    public $input_defaults = array();

    /**
     * List of additional XEP fields included in location
     * (ie. 'text', 'room')
     *
     * @var array
     */
    public $use_xep_keys = array();

    /**
     * The group of widgets items as QuickForm elements
     */
    var $_widget_elements = array();
    var $_main_group = array();
    var $_countrylist = array();
    var $_allowed_xep_keys = array();

    /**
     * Maximum amount of results returned. If this is set greater than 1,
     * the widget will show alternative results and lets user to choose the best match.
     * Defaults to: 20
     *
     * @var integer
     */
    public $js_maxRows = null;

    /**
     * Radius of the area we search for alternatives. (in Kilometers)
     * Defaults to: 5
     *
     * @var integer
     */
    public $js_radius = null;

    var $js_options = array();
    var $js_options_str = '';

    /**
     * The initialization event handler post-processes the maxlength setting.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('org_routamc_positioning_dm2_type');

        if (is_null($this->enabled_methods))
        {
            $this->enabled_methods = array
            (
                'place',
                'map',
                'coordinates'
            );
        }

        if (is_null($this->service))
        {
            $this->service = 'geonames';
        }
        $head = midcom::get()->head;
        $head->enable_jquery_ui(array('tabs'));

        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.routamc.positioning/widget/widget.js');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.routamc.positioning/widget/position_widget.css');

        $this->_element_id = "{$this->name}_position_widget";

        $config = "{
            heightStyle: 'auto',
            activate: function() {
                jQuery('#{$this->_element_id}').dm2_pw_position_map_to_current();
            }
        }";

        $script = "jQuery('#{$this->_element_id }').tabs({$config});\n";
        midcom::get()->head->add_jquery_state_script($script);

        $this->_get_country_list();
        $this->_init_widgets_js_options();

        $this->_allowed_xep_keys = array
        (
            'area',
            'building',
            'description',
            'floor',
            'room',
            'text',
            'uri',
        );
    }

    /**
     * Creates the tab view for all enabled positioning methods
     * Also adds static options to results.
     */
    public function add_elements_to_form($attributes)
    {
        // Get url to geocode handler
        $nav = new midcom_helper_nav();
        $root_node = $nav->get_node($nav->get_root_node());
        $this->_handler_url = $root_node[MIDCOM_NAV_FULLURL] . 'midcom-exec-org.routamc.positioning/geocode.php';

        $html = "<div id=\"{$this->_element_id}\" class=\"midcom_helper_datamanager2_widget_position\"><!-- widget starts -->\n";

        $input_key = $this->_get_input_key("id");
        $html .= "<input class=\"position_widget_id\" id=\"{$this->_element_id}_id\" name=\"".$input_key."\" type=\"hidden\" value=\"{$this->_element_id}\" />";
        $input_key = $this->_get_input_key("backend_url");
        $html .= "<input class=\"position_widget_backend_url\" id=\"{$this->_element_id}_backend_url\" name=\"".$input_key."\" type=\"hidden\" value=\"{$this->_handler_url}\" />";
        $input_key = $this->_get_input_key("backend_service");
        $html .= "<input class=\"position_widget_backend_service\" id=\"{$this->_element_id}_backend_service\" name=\"".$input_key."\" type=\"hidden\" value=\"{$this->service}\" />";

        $html .= "    <ul>\n";

        foreach ($this->enabled_methods as $method)
        {
            $html .= "        <li><a href=\"#{$this->_element_id}_tab_content_{$method}\"><span>" . midcom::get()->i18n->get_string($method, 'org.routamc.positioning') . "</span></a></li>\n";
        }

        $html .= "    </ul>\n";
        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_static_widget_start",
            '',
            $html
        );

        foreach ($this->enabled_methods as $method)
        {
            $function = "_add_{$method}_method_elements";
            $this->$function();
        }

        $html = "</div><!-- widget ends -->\n";
        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_static_widget_end",
            '',
            $html
        );

        $this->_main_group = $this->_form->addGroup
        (
            $this->_widget_elements,
            $this->name,
            $this->_translate($this->_field['title']),
            ''
        );
    }

    private function _get_input_value($fieldname)
    {
        $value = $this->_type->location->$fieldname;
        $value_input = $this->_get_input($fieldname, $_REQUEST);
        if (!$value && $value_input !== null)
        {
            $value = $value_input;
        }
        if (   !$value
            && isset($this->input_defaults[$fieldname]))
        {
            $value = $this->input_defaults[$fieldname];
        }
        return $value;
    }

    public function _add_place_method_elements()
    {
        $html = "\n<div id=\"{$this->_element_id}_tab_content_place\" class=\"position_widget_tab_content position_widget_tab_content_place\"><!-- tab_content_place starts -->\n";

        $html .= "<div class=\"geodata_btn\" id='{$this->_element_id}_geodata_btn'></div>";
        $html .= "<div class=\"indicator\" id='{$this->_element_id}_indicator' style=\"display: none;\"></div>";

        $country = $this->_get_input_value('country');
        $html .= $this->_render_country_list($country);

        $html .= $this->_render_input('city', $this->_get_city_name());
        $html .= $this->_render_input('region');
        $html .= $this->_render_input('street');
        $html .= $this->_render_input('postalcode');
        $html .= $this->_render_xep_keys();

        $html .= "<div id=\"{$this->_element_id}_status_box\" class=\"status_box\"></div>";

        $html .= "\n</div><!-- tab_content_place ends -->\n";

        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_static_place",
            '',
            $html
        );
    }

    private function _render_input($fieldname, $value = null)
    {
        if (null === $value)
        {
            $value = $this->_get_input_value($fieldname);
        }
        $input_key = $this->_get_input_key($fieldname);
        $html = "<label for='{$this->_element_id}_input_place_{$fieldname}' id='{$this->_element_id}_input_place_{$fieldname}_label'>";
        $html .= "<span class=\"field_text\">" . midcom::get()->i18n->get_string("xep_{$fieldname}", 'org.routamc.positioning') . "</span><span class=\"proposal\"></span>";
        $html .= "<input size=\"40\" class=\"shorttext position_widget_input position_widget_input_place_{$fieldname}\" id=\"{$this->_element_id}_input_place_{$fieldname}\" name=\"".$input_key."\" type=\"text\" value=\"{$value}\" />";
        $html .= "</label>";
        return $html;
    }

    private function _get_city_name()
    {
        $city_name = '';
        $city_id = $this->_type->location->city;

        $city_input = $this->_get_input("city", $_REQUEST);
        if (!$city_id && $city_input !== null)
        {
            $city_id = $this->_get_city_by_name($city_input);
            if (!$city_id)
            {
                $city_name = $city_input;
            }
        }

        if (   !$city_id
            && isset($this->input_defaults['city'])
            && is_numeric($this->input_defaults['city']))
        {
            $city_id = $this->input_defaults['city'];
        }
        if (   !$city_name
            && isset($this->input_defaults['city'])
            && is_string($this->input_defaults['city']))
        {
            $city_name = $this->input_defaults['city'];
        }

        if ($city_id)
        {
            try
            {
                $city = new org_routamc_positioning_city_dba($city_id);
                $city_name = $city->city;
            }
            catch (midcom_error $e){}
        }
        return $city_name;
    }

    private function _render_xep_keys()
    {
        $html = '';
        $inserted_xep_keys = array();

        foreach ($this->_allowed_xep_keys as $xep_key)
        {
            if (   !in_array($xep_key, $this->use_xep_keys)
                || !midcom::get()->dbfactory->property_exists($this->_type->location, $xep_key)
                || in_array($xep_key, $inserted_xep_keys))
            {
                // Skip
                continue;
            }
            $inserted_xep_keys[] = $xep_key;
            $html .= $this->_render_input($xep_key);
        }
        return $html;
    }

    public function _add_map_method_elements()
    {
        $html = "\n<div id=\"{$this->_element_id}_tab_content_map\" class=\"position_widget_tab_content position_widget_tab_content_map\"><!-- tab_content_map starts -->\n";

        $html .= "\n<div class=\"position_widget_actions\">\n";
        $html .= "\n<div id=\"{$this->_element_id}_position_widget_action_cam\">[ Clear alternatives ]</div> \n";
        $html .= "\n</div>\n";

        $orp_map = new org_routamc_positioning_map("{$this->_element_id}_map");
        $html .= $orp_map->show(420, 300, null, false);

        $html .= "\n</div><!-- tab_content_map ends -->\n";

        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_static_map",
            '',
            $html
        );

        $script = "jQuery('#{$this->_element_id}').dm2_position_widget(mapstraction_{$this->_element_id}_map, {$this->js_options_str});";
        midcom::get()->head->add_jquery_state_script($script);
    }

    public function _add_coordinates_method_elements()
    {
        $html = "\n<div id=\"{$this->_element_id}_tab_content_coordinates\" class=\"position_widget_tab_content position_widget_tab_content_coordinates\"><!-- tab_content_coordinates starts -->\n";

        $html .= "<div class=\"geodata_btn\" id='{$this->_element_id}_revgeodata_btn'></div>";
        $html .= "<div class=\"indicator\" id='{$this->_element_id}_revindicator' style=\"display: none;\"></div>";

        $lat = $this->_type->location->latitude;
        $lat_input = $this->_get_input("latitude", $_REQUEST);
        if (!$lat && $lat_input !== null)
        {
            $lat = $lat_input;
        }
        $lon = $this->_type->location->longitude;
        $lon_input = $this->_get_input("longitude", $_REQUEST);
        if (!$lon && $lon_input)
        {
            $lon = $lon_input;
        }

        $lat = str_replace(",", ".", $lat);
        $lon = str_replace(",", ".", $lon);

        $input_key = $this->_get_input_key("latitude");
        $html .= "<label for='{$this->_element_id}_input_coordinates_latitude' id='{$this->_element_id}_input_coordinates_latitude_label'>";
        $html .= "<span class=\"field_text\">" . midcom::get()->i18n->get_string('latitude', 'org.routamc.positioning') . "</span>";
        $html .= "<input size=\"20\" class=\"shorttext position_widget_input position_widget_input_coordinates_latitude\" id=\"{$this->_element_id}_input_coordinates_latitude\" name=\"".$input_key."\" type=\"text\" value=\"{$lat}\" />";
        $html .= "</label>";

        $input_key = $this->_get_input_key("longitude");
        $html .= "<label for='{$this->_element_id}_input_coordinates_longitude' id='{$this->_element_id}_input_coordinates_longitude_label'>";
        $html .= "<span class=\"field_text\">" . midcom::get()->i18n->get_string('longitude', 'org.routamc.positioning') . "</span>";
        $html .= "<input size=\"20\" class=\"shorttext position_widget_input position_widget_input_coordinates_longitude\" id=\"{$this->_element_id}_input_coordinates_longitude\" name=\"".$input_key."\" type=\"text\" value=\"{$lon}\" />";
        $html .= "</label>";

        $html .= "\n</div><!-- tab_content_coordinates ends -->\n";

        $this->_widget_elements[] = $this->_form->createElement
        (
            'static',
            "{$this->_element_id}_static_coordinates",
            '',
            $html
        );
    }

    public function _get_country_list()
    {
        $this->_countrylist = array
        (
            '' => midcom::get()->i18n->get_string('select your country', 'org.routamc.positioning'),
        );

        $qb = org_routamc_positioning_country_dba::new_query_builder();
        $qb->add_constraint('code', '<>', '');
        $qb->add_order('name', 'ASC');
        $countries = $qb->execute_unchecked();

        if (count($countries) == 0)
        {
            debug_add('Cannot render country list: No countries found. You have to use org.routamc.positioning to import countries to database.');
        }

        foreach ($countries as $country)
        {
            $this->_countrylist[$country->code] = $country->name;
        }
    }

    public function _render_country_list($current='')
    {
        $html = '';

        if (   empty($this->_countrylist)
            || count($this->_countrylist) == 1)
        {
            return $html;
        }

        $input_key = $this->_get_input_key("country");
        $html .= "<label for='{$this->_element_id}_input_place_country' id='{$this->_element_id}_input_place_country_label'>";
        $html .= "<span class=\"field_text\">" . midcom::get()->i18n->get_string('xep_country', 'org.routamc.positioning') . "</span>";
        $html .= "<select class=\"dropdown position_widget_input position_widget_input_place_country\" id=\"{$this->_element_id}_input_place_country\" name=\"".$input_key."\">";

        foreach ($this->_countrylist as $code => $name)
        {
            $selected = '';
            if ($code == $current)
            {
                $selected = 'selected="selected"';
            }
            $html .= "<option value=\"{$code}\" {$selected}>{$name}</option>";
        }

        $html .= "</select>";
        $html .= "</label>";

        return $html;
    }

    public function _init_widgets_js_options()
    {
        $this->js_options['maxRows'] = $this->js_maxRows ?: 20;
        $this->js_options['radius'] = $this->js_radius ?: 5;
        $this->js_options_str = json_encode($this->js_options);
    }

    public function get_default()
    {
        try
        {
            $city = new org_routamc_positioning_city_dba($this->_type->location->city);
            $city_name = $city->city;
        }
        catch (midcom_error $e)
        {
            $city_name = '';
        }

        $lat = $this->_type->location->latitude;
        $lat_input = $this->_get_input("latitude", $_REQUEST);
        if (!$lat && $lat_input !== null)
        {
            $lat = $lat_input;
        }
        $lon = $this->_type->location->longitude;
        $lon_input = $this->_get_input("longitude", $_REQUEST);
        if (!$lon && $lon_input !== null)
        {
            $lon = $lon_input;
        }

        $lat = str_replace(",", ".", $lat);
        $lon = str_replace(",", ".", $lon);

        if (!empty($lat) && !empty($lon))
        {
            $script = "jQuery('#{$this->_element_id}').dm2_pw_init_current_pos({$lat},{$lon});";
            midcom::get()->head->add_jquery_state_script($script);
        }

        return array
        (
            $this->_get_input_key("country") => $this->_type->location->country,
            $this->_get_input_key("city") => $city_name,
            $this->_get_input_key("street") => $this->_type->location->street,
            $this->_get_input_key("postalcode") => $this->_type->location->postalcode,
            $this->_get_input_key("latitude") => $this->_type->location->latitude,
            $this->_get_input_key("longitude") => $this->_type->location->longitude,
        );
    }

    public function _get_city_by_name($city_name, $results = array())
    {
        if (empty($city_name))
        {
            return 0;
        }
        $city_id = 0;
        $city = org_routamc_positioning_city_dba::get_by_name($city_name);
        if (!empty($city))
        {
            $city_id = $city->id;
        }
        else if (!empty($results))
        {
            $city = new org_routamc_positioning_city_dba();
            $city->city = $city_name;

            $country = $this->_get_input("country", $results);
            if (!empty($country))
            {
                $city->country = $country;
            }
            $region = $this->_get_input("region", $results);
            if ($region !== null)
            {
                $city->region = $region;
            }
            $lat = $this->_get_input("latitude", $results);
            if (!empty($lat))
            {
                $city->latitude = $lat;
            }
            $lon = $this->_get_input("longitude", $results);
            if (!empty($lon))
            {
                $city->longitude = $lon;
            }
            if (!$city->create())
            {
                debug_add("Cannot save new city '{$city_name}'");
            }

            $city_id = $city->id;
        }

        return $city_id;
    }

    private function _get_input_key($input_name)
    {
        return $this->_element_id . "_input[".$input_name."]";
    }

    private function _get_input($input_name, $data)
    {
        return isset($data[$this->_element_id . "_input"][$input_name]) ? $data[$this->_element_id . "_input"][$input_name] : null;
    }

    public function sync_type_with_widget($results)
    {
        $country = $this->_get_input("country", $results);
        if ($country !== null)
        {
            $this->_type->location->country = $country;
        }
        $city = $this->_get_input("city", $results);
        if ($city !== null)
        {
            $city_id = $this->_get_city_by_name($city, $results);
            $this->_type->location->city = $city_id;
        }
        $street = $this->_get_input("street", $results);
        if ($street !== null)
        {
            $this->_type->location->street = $street;
        }
        $region = $this->_get_input("region", $results);
        if ($region !== null)
        {
            $this->_type->location->region = $region;
        }
        $postalcode = $this->_get_input("postalcode", $results);
        if ($postalcode !== null)
        {
            $this->_type->location->postalcode = $postalcode;
        }
        $lat = $this->_get_input("latitude", $results);
        if (!empty($lat))
        {
            $this->_type->location->latitude = $lat;
        }
        $lon = $this->_get_input("longitude", $results);
        if (!empty($lon))
        {
            $this->_type->location->longitude = $lon;
        }

        foreach ($this->_allowed_xep_keys as $xep_key)
        {
            if (   !in_array($xep_key, $this->use_xep_keys)
                || !midcom::get()->dbfactory->property_exists($this->_type->location, $xep_key))
            {
                continue;
            }
            $this->_type->location->$xep_key = $this->_get_input($xep_key, $results);
        }
    }

    public function is_frozen()
    {
        return $this->_main_group->isFrozen();
    }
}
