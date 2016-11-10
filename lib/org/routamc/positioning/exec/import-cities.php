<?php
midcom::get()->auth->require_admin_user();

// If we import some other database than Geonames this is the place to tune
// Also, the Geonames dump format may change. See http://download.geonames.org/export/dump/readme.txt
// The current state of this, incl. comments reflects the Geonames format as of 2008-12-15
$fields_map = array(
    'geonameid'        => 0, // integer id of record in geonames database
    'name'             => 1, // name of geographical point (utf8) varchar(200)
    'asciiname'        => 2, // name of geographical point in plain ascii characters, varchar(200)
    'alternatenames'   => 3, // alternatenames, comma separated varchar(4000) (varchar(5000) for SQL Server)
    'latitude'         => 4, // latitude in decimal degrees (wgs84)
    'longitude'        => 5, // longitude in decimal degrees (wgs84)
    'featureclass'     => 6, // see http://www.geonames.org/export/codes.html, char(1)
    'featurecode'      => 7, // see http://www.geonames.org/export/codes.html, varchar(10)
    'country'          => 8, // ISO-3166 2-letter country code, 2 characters
    'cc2'              => 9, // alternate country codes, comma separated, ISO-3166 2-letter country code, 60 chars
    'admin1 code'      => 10,
    'admin2 code'      => 11,
    'admin3 code'      => 12,
    'admin4 code'      => 13,
    'population'       => 14, // integer
    'elevation'        => 15, // in meters, integer
    'gtopo30'          => 16, // average elevation of 30'x30' (ca 900mx900m) area in meters, integer
    'timezone'         => 17, // apparently tz database format
    'modification date'=> 18, // date of last modification in yyyy-MM-dd format
);

if (   array_key_exists('cities_file_path', $_POST)
    && file_exists($_POST['cities_file_path']))
{
    $features = explode(',', $_POST['featurecodes_to_import']);

    midcom::get()->disable_limits();
    // this is a potentially very time and resource intensive operation, so let's play nicely:
    proc_nice(10);
    while (@ob_end_flush());

    $imported_cities = array();

    // Read CSV file
    $cities_created = 0;
    $row = 0;
    $handle = fopen($_POST['cities_file_path'], 'r');
    while ($data = fgetcsv($handle, 1000, "\t"))
    {
        $row++;
        //if ($row > 1000) { break; }

        if (   !isset($data[$fields_map['featurecode']])
            || !in_array($data[$fields_map['featurecode']], $features))
        {
            continue;
        }

        if ($data[$fields_map['population']] < $_POST['population_to_import'])
        {
            continue;
        }

        if (strlen($data[$fields_map['country']]) > 2)
        {
            continue;
        }

        $new_city = new org_routamc_positioning_city_dba();
        $new_city->city      = $data[$fields_map['name']];
        $new_city->country   = $data[$fields_map['country']];
        $new_city->latitude  = $data[$fields_map['latitude']];
        $new_city->longitude = $data[$fields_map['longitude']];
        $new_city->population = $data[$fields_map['population']];
        $new_city->altitude = $data[$fields_map['elevation']];

        // Handle possible alternate names
        $alternate_names = explode(',', $data[$fields_map['alternatenames']]);
        if (count($alternate_names) > 0)
        {
            foreach ($alternate_names as $name)
            {
                $new_city->alternatenames .= "|{$name}";
            }
            if (!empty($data[$fields_map['asciiname']]))
            {
                $new_city->alternatenames .= '|' . $data[$fields_map['asciiname']];
            }
            $new_city->alternatenames .= '|';
        }

        if (   array_key_exists("{$new_city->country}:{$data[3]}:{$new_city->city}", $imported_cities)
            || $row == 1)
        {
            // We have city by this name for the country already
            continue;
        }

        echo "{$row}: Adding {$new_city->city}, {$new_city->country}... ";

        if ($new_city->create())
        {
            echo "<span style=\"color: #00cc00;\">Success,</span> ";
            $imported_cities["{$new_city->country}:{$data[3]}:{$new_city->city}"] = true;
            $cities_created++;
        }
        else
        {
            echo "<span style=\"color: #cc0000;\">FAILED</span>, ";
        }
        echo midcom_connection::get_error_string() . "<br />\n";
        flush();
    }

    echo "<p>{$cities_created} cities imported.</p>\n";
}
else
{
    ?>
    <h1>World Cities Database installation</h1>

    <p>
    You can use this script to install a <a href="http://www.geonames.org/export/#dump">Geonames city database</a>.
    <a href="http://download.geonames.org/export/dump/">Download the database ZIP file</a>
    to your server, unzip it and provide its local path in the box below. Ensure that Apache can read it.
    </p>

    <p><strong>Please note that this process will take a long time.</strong> This can be anything between half hour and several hours
    to process the 3 million cities of the full dump, or significantly less for the
    <a href="http://download.geonames.org/export/dump/cities15000.zip">list of cities with over 15,000 inhabitants</a>.</p>

    <form method="post">
        <label><a href="http://www.geonames.org/export/codes.html">Features</a> to import<br /><input type="text" name="featurecodes_to_import" value="PPL,PPLA,PPLC,PPLL,PPLS" /></label><br />
        <label>Minimum population<br /><input type="text" name="population_to_import" value="0" /></label><br />
        <label>File path<br /><input type="text" name="cities_file_path" value="/tmp/cities15000.txt" /></label>
        <input type="submit" value="Install" />
    </form>

    <p>
    If you want to install a custom cities list, it must be in a tab-delimited CSV file having the following fields: (the state of http://download.geonames.org/export/dump/readme.txt as of 2008-12-15)
    </p>

    <pre>
geonameid         : integer id of record in geonames database
name              : name of geographical point (utf8) varchar(200)
asciiname         : name of geographical point in plain ascii characters, varchar(200)
alternatenames    : alternatenames, comma separated varchar(4000) (varchar(5000) for SQL Server)
latitude          : latitude in decimal degrees (wgs84)
longitude         : longitude in decimal degrees (wgs84)
feature class     : see http://www.geonames.org/export/codes.html, char(1)
feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
country code      : ISO-3166 2-letter country code, 2 characters
cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
admin1 code       : fipscode (subject to change to iso code), isocode for the us and ch, see file admin1Codes.txt for display names of this code; varchar(20)
admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
admin3 code       : code for third level administrative division, varchar(20)
admin4 code       : code for fourth level administrative division, varchar(20)
population        : integer
elevation         : in meters, integer
gtopo30           : average elevation of 30'x30' (ca 900mx900m) area in meters, integer
timezone          : the timezone id (see file timeZone.txt)
modification date : date of last modification in yyyy-MM-dd format
    </pre>
    <p>example:</p>
    <pre><?php
        echo "660561\tBorgå\tBorga\tBorga,Borgo,Borgå,PORVOO,Porvo,Porvoo,ПОРВОО\t60.4\t25.6666667\tP\tPPL\tFI\t13\03\t\t\t47192\t\t37\tEurope/Helsinki\t2008-04-04\n";
    ?></pre>
    <?php
}
?>