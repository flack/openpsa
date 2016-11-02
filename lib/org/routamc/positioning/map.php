<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapper class for map display via the mapstraction library
 *
 * Example usage:
 *
 * $map = new org_routamc_positioning_map('my_example_map');
 * $map->add_object($article);
 * $map->add_object($another_article);
 * $map->show();
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_map extends midcom_baseclasses_components_purecode
{
    /**
     * ID of the map
     */
    private $id = '';

    /**
     * Type of the map to use
     */
    private $type = 'google';

    /**
     * API key to use with the mapping service, if needed
     */
    private $api_key = '';

    /**
     * Markers to display on the map
     */
    private $markers = array();

    /**
     * Set map zoom level to this value (note: effect of zoom level varies by map provider)
     */
    var $zoom_level = false;

    /**
     * Constructor
     *
     * @param string $id    Id string for the map
     */
    public function __construct($id, $type = null)
    {
        $this->id = $id;
        parent::__construct();

        if (is_null($type))
        {
            $this->type = $this->_config->get('map_provider');
        }
        else
        {
            $this->type = $type;
        }
        $this->api_key = $this->_config->get('map_api_key');
    }

    /**
     * Add an object to the map
     *
     * @return boolean
     */
    function add_object($object, $icon = null)
    {
        $object_position = new org_routamc_positioning_object($object);
        $coordinates = $object_position->get_coordinates();
        if (is_null($coordinates))
        {
            return false;
        }

        $marker = array();
        $marker['coordinates'] = $coordinates;

        // TODO: Use reflection to get the label property
        if (isset($object->title))
        {
            $marker['title'] = $object->title;
        }
        elseif (isset($object->name))
        {
            $marker['title'] = $object->name;
        }
        else
        {
            $marker['title'] = $object->guid;
        }

        if (isset($object->abstract))
        {
            $marker['abstract'] = $object->abstract;
        }

        if (!is_null($icon))
        {
            $marker['icon'] = $icon;
        }

        if (!empty($object->abstract_allow_html))
        {
            $marker['abstract_allow_html'] = true;
        }

        return $this->add_marker($marker);
    }

    /**
     * Add a marker to the map
     *
     * Marker array should contain the following:
     *
     * - coordinates array with latitude, longitude (and possibly altitude)
     * - title string
     *
     * In addition it may contain:
     *
     * - abstract string containing HTML to be shown in the infobubble
     * - icon string URL to image file
     *
     * @param array $marker Marker array
     * @return boolean Whether the operation was successful
     */
    function add_marker($marker)
    {
        // Perform sanity checks
        if (   !isset($marker['coordinates'])
            || !is_array($marker['coordinates'])
            || !isset($marker['coordinates']['latitude'])
            || !isset($marker['coordinates']['longitude']))
        {
            return false;
        }

        if (empty($marker['title']))
        {
            return false;
        }

        $this->markers[] = $marker;
        return true;
    }

    /**
     * Include the javascript files and code needed for map display
     */
    function add_jsfiles($echo_output=true)
    {
        static $added = array();
        if (isset($added[$this->type]))
        {
            return false;
        }

        if ($echo_output)
        {
            echo "<script type=\"text/javascript\" src=\"" . MIDCOM_STATIC_URL . "/org.routamc.positioning/mapstraction.js\"></script>\n";
        }
        else
        {
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.routamc.positioning/mapstraction.js');
        }

        // TODO: We can remove this once mapstraction does the includes by itself
        switch ($this->type)
        {
            case 'microsoft':
                if ($echo_output)
                {
                    echo "<script type=\"text/javascript\" src=\"http://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js\"></script>\n";
                }
                else
                {
                    midcom::get()->head->add_jsfile('http://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js');
                }
                break;
            case 'openlayers':
                if ($echo_output)
                {
                    echo "<script type=\"text/javascript\" src=\"http://www.openlayers.org/api/OpenLayers.js\"></script>\n";
                }
                else
                {
                    midcom::get()->head->add_jsfile('http://www.openlayers.org/api/OpenLayers.js');
                }
                break;
            case 'openstreetmap':
            case 'google':
            default:
                if ($echo_output)
                {
                    echo "<script type=\"text/javascript\" src=\"http://maps.google.com/maps?file=api&amp;v=2&amp;key={$this->api_key}\"></script>\n";
                }
                else
                {
                    midcom::get()->head->add_jsfile("http://maps.google.com/maps?file=api&amp;v=2&amp;key={$this->api_key}");
                }
                break;
        }

        $added[$this->type] = true;
        return true;
    }

    /**
     * Display the map
     *
     * @param integer $width Width of the map in pixels
     * @param integer $height Height of the map in pixels
     * @param integer $zoom_level Zoom level of the map. Leave to null for autozoom
     * @param boolean $echo_output Whether output should be echoed or returned
     */
    function show($width = 300, $height = 200, $zoom_level = null, $echo_output = true)
    {
        $callbacks = $this->_config->get('map_onshow_callbacks');
        if (is_array($callbacks))
        {
            $callback_args = array(&$this);
            foreach ($callbacks as $callback)
            {
                call_user_func($callback, $callback_args);
            }
        }
        unset($callbacks);
        $html = '';
        $script = '';

        $this->add_jsfiles($echo_output);

        // Show the map div
        $html .= "<div class=\"org_routamc_positioning_map\" id=\"{$this->id}\"";
        if (   !is_null($width)
            && !is_null($height))
        {
            $html .= " style=\"width: {$width}px; height: {$height}px\"";
        }
        $html .= "></div>\n";

        // Start mapstraction
        if ($echo_output)
        {
            $script .= "<script type=\"text/javascript\">\n";
        }
        $script .= "var mapelement = document.getElementById('{$this->id}');\n";
        $script .= "if (mapelement) {\n";
        $script .= "    var mapstraction_{$this->id} = new Mapstraction('{$this->id}','{$this->type}', true);\n";

        if ($this->type == 'google')
        {
            // Workaround, Google requires you to start with a center
            $script .= "    mapstraction_{$this->id}.setCenter(new LatLonPoint(0, 0));\n";
        }

        foreach ($this->markers as $marker)
        {
            $marker_instance = $this->create_js_marker($marker, $script);
            $script .= "    mapstraction_{$this->id}.addMarker({$marker_instance});\n";
        }
        $script .= "    mapstraction_{$this->id}.addSmallControls();\n";
        $script .= "    mapstraction_{$this->id}.autoCenterAndZoom();\n";
        if ($this->zoom_level !== false)
        {
            // FIXME: if this is set do not bother with autozoom
            $script .= "    mapstraction_{$this->id}.setZoom({$this->zoom_level});\n";
        }
        $script .= "}\n";
        if ($echo_output)
        {
            $script .= "</script>\n";
        }

        if (!$echo_output)
        {
            midcom::get()->head->add_jquery_state_script($script);
            return $html;
        }

        echo "{$html}{$script}";
    }

    /**
     * Create a marker javascript object and return its name
     */
    function create_js_marker($marker, &$script)
    {
        static $i = 0;
        $i++;

        // Just in case.. cast lat/lon to 'dot' delimited numbers
        $lat = number_format($marker['coordinates']['latitude'], 6, '.', '');
        $lon = number_format($marker['coordinates']['longitude'], 6, '.', '');
        $script .= "var marker_{$i} = new Marker(new LatLonPoint({$lat}, {$lon}))\n";

        $title = htmlspecialchars($marker['title'], ENT_QUOTES);
        $script .= "marker_{$i}.setLabel('{$title}');\n";

        if (   !isset($marker['icon'])
            || !is_array($marker['icon']))
        {
            $marker['icon'] = array();
        }
        if (!isset($marker['icon']['path']))
        {
            $marker['icon']['path'] = MIDCOM_STATIC_URL . '/org.routamc.positioning/pin-regular.png';
        }

        $script .= "marker_{$i}.setIcon('{$marker['icon']['path']}');\n";

        if (   isset($marker['icon']['width'])
            && isset($marker['icon']['height']))
        {
            $script .= "marker_{$i}.setIconSize([{$marker['icon']['width']}, {$marker['icon']['height']}]);\n";
        }

        if (   isset($marker['shadow_icon'])
            && is_array($marker['shadow_icon']))
        {
            if (   isset($marker['shadow_icon']['width'])
                && isset($marker['shadow_icon']['height']))
            {
                $script .= "marker_{$i}.setShadowIcon('{$marker['shadow_icon']['path']}', [{$marker['shadow_icon']['width']}, {$marker['shadow_icon']['height']}]);\n";
            }
            else
            {
                $script .= "marker_{$i}.setShadowIcon('{$marker['shadow_icon']['path']}');\n";
            }
        }

        if (isset($marker['abstract']))
        {
            if (!empty($marker['abstract_allow_html']))
            {
                $abstract = $marker['abstract'];
            }
            else
            {
                $abstract = htmlspecialchars($marker['abstract'], ENT_QUOTES);
            }
            $script .= "marker_{$i}.setInfoBubble('{$abstract}');\n";
        }

        // TODO: Set other marker properties

        return "marker_{$i}";
    }
}
