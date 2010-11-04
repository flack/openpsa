<?php

/**
 * Update script to convert all legacy topic information into data compatible
 * with the midgard_topic MgdSchema class. 
 * 
 * The script requires admin privileges to execute properly.
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_admin_user();

@ini_set('max_execution_time', 0);

$qb = midcom_db_topic::new_query_builder();
$qb->begin_group('AND');
    $qb->add_constraint('parameter.domain', '=', 'midcom');
    $qb->add_constraint('parameter.name', '=', 'component');
    $qb->add_constraint('parameter.value', '<>', '');
$qb->end_group();
//$qb->add_constraint('component', '=', '');
$midcom_topics = $qb->execute();
 
echo "<pre>\n";

foreach ($midcom_topics as $topic)
{
    if ($topic->sitegroup != $_MIDGARD['sitegroup'])
    {
        continue;
    }
    
    $component = $topic->parameter('midcom', 'component');
    echo "Converting {$component} topic {$topic->name} (#{$topic->id})... ";
    $topic->component = $component;
    $topic->style = $topic->parameter('midcom', 'style');
    
    if ($topic->parameter('midcom', 'style_inherit'))
    {
        $topic->styleInherit = true;
    }
    
    if ($topic->parameter('midcom.helper.metadata', 'nav_noentry'))
    {
        $topic->metadata->navnoentry = true;
    }
    
    $topic->update();
    echo midcom_application::get_error_string() . "\n";
}

$_MIDCOM->cache->invalidate_all();
echo "</pre>\n";
?>