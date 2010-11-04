<?php
/**
 * @package midcom.helper.reflector
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: test_name.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

// FIXME: make generic
//$article = new midcom_db_article('17c751baca9d11dd842c2de934f936993699');
$article = new midcom_db_article();
$article->topic = 5;
$article->title = 'Duplicate name test with (with allow_catenate)';
$article->name = 'gathering-09';
$article->allow_name_catenate = true;

$reflected_name_property = midcom_helper_reflector::get_name_property($article);
$reflected_name = midcom_helper_reflector::get_object_name($article);

echo "Reflector thinks name is '{$reflected_name}' (from property '{$reflected_name_property}')<br>\n";

if (midcom_helper_reflector::name_is_safe($article))
{
    echo "OK: '{$reflected_name}' is considered URL-safe<br>\n";
}
else
{
    echo "ERROR: '{$reflected_name}' is NOT considered URL-safe<br>\n";
}

if (midcom_helper_reflector::name_is_clean($article))
{
    echo "OK: '{$reflected_name}' is considered 'clean'<br>\n";
}
else
{
    echo "WARN: '{$reflected_name}' is NOT considered 'clean'<br>\n";
}

if (midcom_helper_reflector_tree::name_is_unique($article))
{
    echo "OK: '{$reflected_name}' is unique (among siblings)<br>\n";
}
else
{
    echo "ERROR: '{$reflected_name}' is NOT unique (among siblings)<br>\n";
}

$new_name = midcom_helper_reflector_tree::generate_unique_name($article);
echo "midcom_helper_reflector_tree::generate_unique_name(\$article) returned '{$new_name}'<br>\n";

/*
$stat = (int)$article->create();
echo "\$article->create() returned {$stat}, last Midgard error: " . midcom_application::get_error_string() . "<br>\n";
echo "&nbsp;&nbsp;&nbsp;\$article->name is now {$article->name}<br>\n";
*/

/*
$stat = (int)$article->update();
echo "\$article->update() returned {$stat}, last Midgard error: " . midcom_application::get_error_string() . "<br>\n";
echo "&nbsp;&nbsp;&nbsp;\$article->name is now {$article->name}<br>\n";
*/



?>