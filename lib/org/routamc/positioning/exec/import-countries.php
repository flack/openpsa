<?php
midcom::get()->auth->require_admin_user();

$http_request = new org_openpsa_httplib();

$xml = $http_request->get('http://ws.geonames.org/countryInfo?lang=' . midcom::get()->i18n->get_current_language());
$simplexml = simplexml_load_string($xml);

foreach ($simplexml->country as $id => $countryinfo) {
    echo "<br />Importing {$countryinfo->countryName}...\n";
    $country = new org_routamc_positioning_country_dba();
    $country->code = (string) $countryinfo->countryCode;
    $country->name = (string) $countryinfo->countryName;
    $country->codenumeric = (string) $countryinfo->isoNumeric;
    $country->code3 = (string) $countryinfo->isoAlpha3;
    $country->fips = (string) $countryinfo->fipsCode;
    $country->continent = (string) $countryinfo->continent;
    $country->area = (float) $countryinfo->areaInSqKm;
    $country->population = (int) $countryinfo->population;
    $country->currency = (string) $countryinfo->currencyCode;
    $country->bboxwest = (float) $countryinfo->bBoxWest;
    $country->bboxnorth = (float) $countryinfo->bBoxNorth;
    $country->bboxeast = (float) $countryinfo->bBoxEast;
    $country->bboxsouth = (float) $countryinfo->bBoxSouth;

    if ($capital = org_routamc_positioning_city_dba::get_by_name((string) $countryinfo->capital)) {
        $country->capital = $capital->id;
    }

    $country->create();
    echo midcom_connection::get_error_string();
}