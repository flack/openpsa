<?php
/**
 * Handler for creating shared secret for securing universalchooser searches
 *
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: universalchooser_create_secret.php 26504 2010-07-06 12:19:31Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_valid_user();

$keysize = 256; // Bits

echo "<p>\n";

// Get /sitegroup-config
$qb_sgconfig = midcom_db_snippetdir::new_query_builder();
$qb_sgconfig->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
$qb_sgconfig->add_constraint('up', '=', 0);
$qb_sgconfig->add_constraint('name', '=', 'sitegroup-config');
$results = $qb_sgconfig->execute();
if (empty($results))
{
    echo "Could not find snippetdir SG{$_MIDGARD['sitegroup']}/sitegroup-config, creating<br/>\n";
    $sd_sgconfig = new midcom_db_snippetdir();
    $sd_sgconfig->name = 'sitegroup-config';
    if (!$sd_sgconfig->create())
    {
        echo "Could not create, errstr: " . midcom_application::get_error_string() . "<br/>\n";
        $_MIDCOM->finish();
        _midcom_stop_request();
    }
    echo "Created with ID#{$sd_sgconfig->id}<br/>\n";
}
else
{
    $sd_sgconfig = $results[0];
}

// Get /sitegroup-config/midcom.helper.datamanager2
$qb_dm2config = midcom_db_snippetdir::new_query_builder();
$qb_dm2config->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
$qb_dm2config->add_constraint('up', '=', $sd_sgconfig->id);
$qb_dm2config->add_constraint('name', '=', 'midcom.helper.datamanager2');
$results = $qb_dm2config->execute();
if (empty($results))
{
    echo "Could not find snippetdir SG{$_MIDGARD['sitegroup']}/sitegroup-config/midcom.helper.datamanager2, creating<br/>\n";
    $sd_dm2config = new midcom_db_snippetdir();
    $sd_dm2config->up = $sd_sgconfig->id;
    $sd_dm2config->name = 'midcom.helper.datamanager2';
    if (!$sd_dm2config->create())
    {
        echo "Could not create, errstr: " . midcom_application::get_error_string() . "<br/>\n";
        $_MIDCOM->finish();
        _midcom_stop_request();
    }
    echo "Created with ID#{$sd_dm2config->id}<br/>\n";
}
else
{
    $sd_dm2config = $results[0];
}


// Get /sitegroup-config/midcom.helper.datamanager2/widget_universalchooser_key
$qb_keysnippet = midcom_db_snippet::new_query_builder();
$qb_keysnippet->add_constraint('sitegroup', '=', $_MIDGARD['sitegroup']);
$qb_keysnippet->add_constraint('up', '=', $sd_dm2config->id);
$qb_keysnippet->add_constraint('name', '=', 'widget_universalchooser_key');
$results = $qb_keysnippet->execute();
if (empty($results))
{
    echo "Could not find snippet SG{$_MIDGARD['sitegroup']}/sitegroup-config/midcom.helper.datamanager2/widget_universalchooser_key, creating<br/>\n";
    $sn_key = new midcom_db_snippet();
    $sn_key->up = $sd_dm2config->id;
    $sn_key->name = 'widget_universalchooser_key';
    if (!$sn_key->create())
    {
        echo "Could not create, errstr: " . midcom_application::get_error_string() . "<br/>\n";
        $_MIDCOM->finish();
        _midcom_stop_request();
    }
    echo "Created with ID#{$sn_key->id}<br/>\n";
}
else
{
    $sn_key = $results[0];
}

//Use mt_rand if possible (faster, more random)
if (function_exists('mt_rand'))
{
    $rand = 'mt_rand';
}
else
{
    $rand = 'rand';
}
$key = null;
for ($i = 1; $i < (ceil($keysize/8)); $i++)
{
    $key .= chr($rand(0, 255));
}

$sn_key->doc = $key;
$sn_key->code = '';
if (!$sn_key->update())
{
    echo "Failed to store key, errstr: " . midcom_application::get_error_string() . "<br/>\n";
    $_MIDCOM->finish();
    _midcom_stop_request();
}

echo "New key created.<br/>\n";

echo "</p>";

?>