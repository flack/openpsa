<?php

/**
 * Update script to convert all pre-Ragnaroek navigation scores to reverse the order of
 * navigation items.
 *
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_admin_user();

function reverse_score($topic_id = null)
{
    static $nap = null;
    
    if (!$nap)
    {
        $nap = new midcom_helper_nav();
    }
    
    if (!$topic_id)
    {
        $topic = new midcom_db_topic($GLOBALS['midcom_config']['midcom_root_topic_guid']);
        $topic_id = $topic->id;
    }
    
    $children = $nap->list_child_elements($topic_id);
    
    if (!$children)
    {
        return;
    }
    
    $count = count($children);
    $i = 1;
    
    echo "<ul>\n";
    foreach ($children as $child)
    {
        if ($child[MIDCOM_NAV_TYPE] === 'node')
        {
            $item = $nap->get_node($child[MIDCOM_NAV_ID]);
        }
        else
        {
            $item = $nap->get_leaf($child[MIDCOM_NAV_ID]);
        }
        
        if (   !$item[MIDCOM_NAV_SORTABLE]
            || !$item[MIDCOM_NAV_OBJECT])
        {
            continue;
        }
        
        echo "    <li>\n";
        echo "        {$item[MIDCOM_NAV_NAME]}\n";
        
        if (   $item[MIDCOM_NAV_OBJECT]->id
            && $item[MIDCOM_NAV_OBJECT]->guid)
        {
            $item[MIDCOM_NAV_OBJECT]->metadata->score = $i;
            
            if ($item[MIDCOM_NAV_OBJECT]->update())
            {
                echo " - updated (score: {$i})\n";
            }
            else
            {
                echo " - <span class=\"color: red;\">update FAILED!</span>\n";
            }
            
            $i++;
        }
        
        if ($child[MIDCOM_NAV_TYPE] === 'node')
        {
            reverse_score($child[MIDCOM_NAV_ID]);
        }
        
        echo "    </li>\n";
    }
    echo "</ul>\n";
}

reverse_score();
?>