$.fn.extend(
{
    dm2_position_widget: function(mapstraction, options)
    {
        options = $.extend($.midcom_helper_datamanager2_widget_position.defaults, options);
        return this.each(function() {
            return new $.midcom_helper_datamanager2_widget_position(this, mapstraction, options);
        });
    },
    dm2_pw_new_position: function(point) {
        return this.trigger("new_position",[point]);
    },
    dm2_pw_clear_alternative_markers: function() {
        return this.trigger("clear_alternative_markers",[]);
    },
    dm2_pw_set_marker: function(label, info) {
        return this.trigger("set_marker",[label, info]);
    },
    dm2_pw_init_current_pos: function(lat, lon)
    {
        return this.trigger("init_current_pos",[lat, lon]);
    },
    dm2_pw_position_map_to_current: function() {
        return this.trigger("position_map_to_current",[]);
    }
});

$.midcom_helper_datamanager2_widget_position = function(widget_block, mapstraction, options) {

    var widget = $(widget_block),
    widget_id = widget.attr('id');

    mapstraction.addMapTypeControls();
    mapstraction.addEventListener('click', new_position);

    var geodata_btn = $('#' + widget_id + '_geodata_btn', widget),
    indicator = $('#' + widget_id + '_indicator', widget),

    revgeodata_btn = $('#' + widget_id + '_revgeodata_btn', widget),
    revindicator = $('#' + widget_id + '_revindicator', widget),

    actions_cam = $('#' + widget_id + '_position_widget_action_cam', widget),

    status_box = $('#' + widget_id + '_status_box', widget),

    current_pos_icon_url = MIDCOM_STATIC_URL + '/org.routamc.positioning/pin-selected.png',
    backend_url = $('input.position_widget_backend_url',widget).val(),
    backend_service = $('input.position_widget_backend_service',widget).val(),
    input_data = {},
    current_pos = null,
    position_marker = null,
    alternative_markers = null,
    alternative_markers_visible = true;

    widget.bind("new_position", function(event, point)
    {
        new_position(point);
    }).bind("clear_alternative_markers", function(event, point){
        clear_alternative_markers();
    }).bind("set_marker", function(event, label, info){
        set_marker(label, info);
    }).bind("init_current_pos", function(event, lat, lon){
        init_current_pos(lat, lon);
    }).bind("position_map_to_current", function(event){
        position_map_to_current();
    });

    indicator.hide();
    revindicator.hide();

    geodata_btn.bind('click', function(e){
        refresh_geodata();
        geodata_btn.hide();
        indicator.show();
    });
    revgeodata_btn.bind('click', function(e){
        var lat = $.trim($('#' + input_data.latitude.id).val()),
        lon = $.trim($('#' + input_data.longitude.id).val());
        lat = lat.replace(/,/,'.');
        lon = lon.replace(/,/,'.');
        new_position(new LatLonPoint(parseFloat(lat), parseFloat(lon)));

        revgeodata_btn.hide();
        revindicator.show();
    });

    actions_cam.bind('click', function(e){
        clear_alternative_markers();
    });

    $('.position_widget_input', widget).each(function(i, o){
        var jqo = $(o),
        key = get_key_name(jqo.attr('name'));
        input_data[key] = {
            id: jqo.attr('id'),
            value: jqo.val()
        };

        if (input_data[key].value === undefined)
        {
            input_data[key].value = '';
        }
    });

    function disable_tabs()
    {
        widget.tabs('disable');
    }
    function enable_tabs()
    {
        widget.tabs('enable');
    }

    function new_position(point)
    {
        switch (mapstraction.api)
        {
            case 'openlayers':
                var lonlat = mapstraction.maps.openlayers.getLonLatFromViewPortPx(point.xy),
                latlon = new LatLonPoint(lonlat.lat, lonlat.lon);
                latlon.fromOpenLayers();
                current_pos = latlon;
                break;
            default:
                current_pos = point;
                break;
        }
        $('#' + input_data.latitude.id).val(String(current_pos.lat).replace(/,/, '.'));
        $('#' + input_data.longitude.id).val(String(current_pos.lon).replace(/,/, '.'));
        set_marker('Current position', '');
        get_reversed_geodata();
    }

    function get_reversed_geodata()
    {
        disable_tabs();
        clear_alternative_markers();

        var opts_str = '?';
        $.each(options, function(key, value){
            opts_str += 'options[' + key + ']=' + value + '&';
        });

        opts_str = opts_str.substr(0, opts_str.length - 1);

        var get_params = {
            service: backend_service,
            dir: 'reverse',
            latitude: String(current_pos.lat).replace(/,/,'.'),
            longitude: String(current_pos.lon).replace(/,/,'.')
        };

        $.ajax({
            type: "GET",
            url: backend_url + opts_str,
            data: get_params,
            dataType: "xml",
            error: function(request, type, expobj){
                parse_error(request.responseText);
            },
            success: function(data){
                var parsed = parse_response(data);
                update_widget_inputs(parsed[0], true, true);
                //handle_alternatives(parsed);
            }
        });

        function parse_error(error_string)
        {
            indicator.hide();
            revindicator.hide();
            geodata_btn.show();
            revgeodata_btn.show();
            enable_tabs();

            status_box.html(error_string);
        }

        function parse_response(data)
        {
            status_box.html('');

            var results = [];
            $('position',data).each(function(idx) {
                var rel_this = $(this);

                results[idx] = {
                    latitude: rel_this.find("latitude").text().replace(/,/,'.'),
                    longitude: rel_this.find("longitude").text().replace(/,/,'.'),
                    distance: {
                        meters: rel_this.find("distance").find("meters").text(),
                        bearing: rel_this.find("distance").find("bearing").text()
                    },
                    city: rel_this.find("city").text(),
                    region: rel_this.find("region").text(),
                    country: rel_this.find("country").text(),
                    alternate_names: rel_this.find("alternate_names").text(),
                    accuracy: rel_this.find("accuracy").text()
                };
            });

            return results;
        }
    }

    function refresh_geodata()
    {
        disable_tabs();
        clear_alternative_markers();

        var opts_str = '?';
        $.each(options, function(key, value){
            opts_str += 'options[' + key + ']=' + value + '&';
        });

        opts_str = opts_str.substr(0,opts_str.length-1);

        var get_params = {
            service: backend_service
        };

        $('.position_widget_input',widget).each(function(i, o){
            var jqo = $(o),
            key = get_key_name(jqo.attr('name'));
            input_data[key] = {
                id: jqo.attr('id'),
                value: jqo.val()
            };

            if (input_data[key].value === undefined)
            {
                input_data[key].value = '';
            }
            else
            {
                get_params[key] = input_data[key].value;
            }
        });

        $.ajax({
            type: "GET",
            url: backend_url + opts_str,
            data: get_params,
            dataType: "xml",
            error: function(request, type, expobj){
                parse_error(request.responseText);
            },
            success: function(data){
                var parsed = parse_response(data);
                update_widget(parsed[0]);
                //handle_alternatives(parsed);
            }
        });

        function parse_error(error_string)
        {
            indicator.hide();
            revindicator.hide();
            geodata_btn.show();
            revgeodata_btn.show();
            enable_tabs();

            status_box.html(error_string);
        }

        function parse_response(data)
        {
            status_box.html('');

            var results = [];
            $('position', data).each(function(idx) {
                var rel_this = $(this);

                results[idx] = {
                    latitude: rel_this.find("latitude").text().replace(/,/, '.'),
                    longitude: rel_this.find("longitude").text().replace(/,/, '.'),
                    distance: {
                        meters: rel_this.find("distance").find("meters").text(),
                        bearing: rel_this.find("distance").find("bearing").text()
                    },
                    accuracy: rel_this.find("accuracy").text(),
                    city: rel_this.find("city").text(),
                    region: rel_this.find("region").text(),
                    country: rel_this.find("country").text(),
                    alternate_names: rel_this.find("alternate_names").text(),
                    postalcode: rel_this.find("postalcode").text()
                };
            });

            return results;
        }
    }

    function update_widget(location_data)
    {
        update_widget_inputs(location_data);

        current_pos = new LatLonPoint(parseFloat(location_data.latitude), parseFloat(location_data.longitude));

        var info = location_data.city + ", " + location_data.country + ", " + location_data.postalcode,
        label = 'Current position';
        if (input_data.description)
        {
            label = input_data.description;
        }
        else if (location_data.description)
        {
            label = location_data.description;
        }

        set_marker(label, info);
    }

    function update_widget_inputs(location_data, skip_lat_lon, no_override)
    {
        if (typeof skip_lat_lon == 'undefined')
        {
            var skip_lat_lon = false;
        }
        if (typeof no_override == 'undefined')
        {
            var no_override = false;
        }

        enable_tabs();
        indicator.hide();
        revindicator.hide();
        geodata_btn.show();
        revgeodata_btn.show();

        var skip_keys = {};
        if (skip_lat_lon) {
            skip_keys.latitude = true;
            skip_keys.longitude = true;
        }

        $.each(location_data, function(key, value)
        {
            if (input_data[key])
            {
                if (   typeof skip_keys[key] !== 'undefined'
                    && skip_keys[key])
                {
                    return;
                }

                if (   key === 'latitude'
                    || key === 'longitude')
                {
                    if (   no_override
                        && $('#' + input_data[key].id).val() == '0')
                    {
                        $('#' + input_data[key].id).val(value);
                    }

                    if (! no_override)
                    {
                        if (value != '0')
                        {
                            $('#' + input_data[key].id).val(value);
                        }
                    }
                }

                if (   no_override
                    && (   $('#' + input_data[key].id).val() == ''
                        || typeof $('#' + input_data[key].id).val() == 'undefined'))
                {
                    $('#' + input_data[key].id).val(value);
                }
                else
                {
                    if (value != '')
                    {
                        var parent = $('#' + input_data[key].id).parent();
                        $("span.proposal", parent).html(' (' + value + ')');
                    }
                }

                if (! no_override)
                {
                    if (value != '') {
                        $('#' + input_data[key].id).val(value);
                    }
                }
            }
        });
    }

    function get_key_name(key)
    {
        // expect something like "location_position_widget_input[city]" and return "city"
        var i = key.indexOf('[');
        return key.slice(i+1, -1);
    }

    function handle_alternatives(items)
    {
        var total = items.length;
        for (var i = 1; i < total; i++)
        {
            var point = new LatLonPoint(parseFloat(items[i].latitude), parseFloat(items[i].longitude)),
            info = items[i].city + ", " + items[i].country + ", " + items[i].postalcode;
            set_alternative_marker('Alternative position', info, point);
        }
    }

    function init_current_pos(lat, lon)
    {
        current_pos = new LatLonPoint(lat,lon);
        set_marker('Current position', '');
    }

    function set_marker(label, info)
    {
        if (position_marker != null)
        {
             mapstraction.removeMarker(position_marker);
        }

        position_marker = new Marker(current_pos);
        position_marker.setIcon(current_pos_icon_url);

        //position_marker.draggable = true;
        //position_marker.draggable_end_event = function(marker){var p = marker.getPoint(); alert(p.lat);};
        //position_marker.addEventListener('dragend', function(){alert('drop');});
        // $.extend(this, function(){
        //             var point = position_marker.getPoint();
        //             dm2_pw_new_position(point);
        //         });

        if (label !== undefined)
        {
            position_marker.setLabel(label);
        }

        if (   info !== undefined
            && info !== '')
        {
            position_marker.setInfoBubble(info);
        }

        mapstraction.addMarker(position_marker);

        // if (info != undefined)
        // {
        //     position_marker[widget_id].openBubble();
        // }
    }

    function set_alternative_marker(label, info, pos)
    {
        if (! alternative_markers)
        {
            alternative_markers = [];
        }

        var last_key = alternative_markers.push( new Marker(pos) );

        if (label !== undefined)
        {
            alternative_markers[last_key - 1].setLabel(label);
        }

        if (   info !== undefined
            && info !== '')
        {
            alternative_markers[last_key - 1].setInfoBubble(info);
        }

        alternative_markers[last_key - 1].setIcon(MIDCOM_STATIC_URL + '/org.routamc.positioning/pin-regular.png');

        mapstraction.addMarker(alternative_markers[last_key-1]);

        // if (info != undefined)
        // {
        //     alternative_markers[widget_id][last_key-1].openBubble();
        // }
    }

    function clear_alternative_markers()
    {
        if (   alternative_markers
            && alternative_markers.length > 0)
        {
            var length = alternative_markers.length;
            for (var i = 0; i < length; i++)
            {
                mapstraction.removeMarker(alternative_markers[i]);
            }
        }
    }

    function toggle_alternative_markers()
    {
        alternative_markers_visible = !!alternative_markers_visible;

        if (   alternative_markers
            && alternative_markers.length > 0)
        {
            var length = alternative_markers.length;
            for (var i = 0; i < length; i++)
            {
                alternative_markers[i].toggle(alternative_markers_visible);
            }
        }
    }

    function position_map_to_current()
    {
        if (current_pos !== null)
        {
            mapstraction.resizeTo(400, 280);
            mapstraction.setCenterAndZoom(current_pos, 13);
            mapstraction.resizeTo(420, 300);
        }
    }

};

$.midcom_helper_datamanager2_widget_position.defaults = {
    maxRows: 20,
    radius: 5
};