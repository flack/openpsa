<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: sort.php 26544 2010-07-11 14:42:47Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Collection of helper functions for array sorting
 *  
 * @package org.openpsa.sales
 */
class org_openpsa_sales_sort
{
    /**
     *
     */
    static function link($mode, $label, $type, $element = 'th')
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'list/' . $type . '/';

        $static_base = MIDCOM_STATIC_URL . '/';
        if (!isset($_REQUEST['org_openpsa_sales_sort_by']))
        {
            $_REQUEST['org_openpsa_sales_sort_by'] = false;
        }
        if (!isset($_REQUEST['org_openpsa_sales_sort_order']))
        {
            $_REQUEST['org_openpsa_sales_sort_order'] = false;
        }
        switch (strtolower($_REQUEST['org_openpsa_sales_sort_by']))
        {
            case $mode:
                switch (strtolower($_REQUEST['org_openpsa_sales_sort_order']))
                {
                    default:
                    case 'desc':
                        $switch_loc = 'switch sort order';
                        return "<{$element} class=\"sortable desc\"><a href=\"{$prefix}?org_openpsa_sales_sort_by={$mode}&org_openpsa_sales_sort_order=asc\" class=\"sort\">{$label}&nbsp;<img src=\"{$static_base}stock-icons/16x16/down.png\" title=\"{$switch_loc}\" alt=\"{$switch_loc}\" /></a></{$element}>";
                        break;
                    case 'asc':
                        $switch_loc = 'switch sort order';
                        return "<{$element} class=\"sortable desc\"><a href=\"{$prefix}?org_openpsa_sales_sort_by={$mode}&org_openpsa_sales_sort_order=desc\" class=\"sort\">{$label}&nbsp;<img src=\"{$static_base}stock-icons/16x16/up.png\" title=\"{$switch_loc}\" alt=\"{$switch_loc}\" /></a></{$element}>";
                        break;
                }
                break;
            default:
                $switch_loc = 'sort by this column';
                return "<{$element} class=\"sortable\"><a href=\"{$prefix}?org_openpsa_sales_sort_by={$mode}\" class=\"sortable\">{$label}</a></{$element}>";
                break;
        }
        return false;
    }
    
    static function by_title($a, $b)
    {
        return strnatcasecmp($a->title, $b->title);
    }
    
    static function by_customer($a, $b)
    {
        // Convert to strings
        if ($a->customer)
        {
            $astring = $GLOBALS['org_openpsa_sales_customer_cache'][$a->customer]->official;
        }
        if ($b->customer)
        {
            $bstring = $GLOBALS['org_openpsa_sales_customer_cache'][$b->customer]->official;
        }
        if (   $a->customer
            && $b->customer)
        {
            // Both have customer set, compare
            return strnatcasecmp($astring, $bstring);
        }
        if (   $a->customer
            && !$b->customer)
        {
            // Only A has customer set, it is therefore greater
            return 1;
        }
        // Only be has customer set, it is therefore greater (no customer is always lower than customer)
        return -1;
    }
    
    static function by_owner($a, $b)
    {
        // Convert to strings
        if ($a->owner)
        {
            $astring =& $GLOBALS['org_openpsa_sales_owner_cache'][$a->owner]->rname;
        }
        if ($b->owner)
        {
            $bstring =& $GLOBALS['org_openpsa_sales_owner_cache'][$b->owner]->rname;
        }
        if (   $a->owner
            && $b->owner)
        {
            // Both have customer set, compare
            return strnatcasecmp($astring, $bstring);
        }
        if (   $a->owner
            && !$b->owner)
        {
            // Only A has owner set, it is therefore greater
            return 1;
        }
        // Only be has owner set, it is therefore greater (no customer is always lower than customer)
        return -1;
    }
    
    
    static function by_probability($a, $b)
    {
        $a = (float)$a->probability;
        $b = (float)$b->probability;
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_close_est($a, $b)
    {
        $a = $a->closeEst;
        $b = $b->closeEst;

        if ($a == 0)
        {
            return 1;
        }
        if ($b == 0)
        {
            return -1;
        }
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_value($a, $b)
    {
        $a = (float)$a->value;
        $b = (float)$b->value;
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_profit($a, $b)
    {
        $a = (float)$a->profit;
        $b = (float)$b->profit;
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_weighted_value($a, $b)
    {
        $a = (float)$a->value / 100 * $a->probability;
        $b = (float)$b->value / 100 * $b->probability;
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_next_action($a, $b)
    {
        $aproject = $GLOBALS['org_openpsa_sales_project_cache'][$GLOBALS['org_openpsa_sales_project_map'][$a->id]];
        $bproject = $GLOBALS['org_openpsa_sales_project_cache'][$GLOBALS['org_openpsa_sales_project_map'][$b->id]];
        $a_action = $aproject->next_action;
        $b_action = $bproject->next_action;
    
        $a = (int)$a_action['time'];
        $b = (int)$b_action['time'];
        if ($a == 0)
        {
            return 1;
        }
        if ($b == 0)
        {
            return -1;
        }
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }
    
    static function by_prev_action($a, $b)
    {
        $aproject = $GLOBALS['org_openpsa_sales_project_cache'][$GLOBALS['org_openpsa_sales_project_map'][$a->id]];
        $bproject = $GLOBALS['org_openpsa_sales_project_cache'][$GLOBALS['org_openpsa_sales_project_map'][$b->id]];
        $a_action = $aproject->prev_action;
        $b_action = $bproject->prev_action;
    
        $a = (int)$a_action['time'];
        $b = (int)$b_action['time'];
        if ($a == 0)
        {
            return 1;
        }
        if ($b == 0)
        {
            return -1;
        }
        if ($a > $b)
        {
            return 1;
        }
        if ($b > $a)
        {
            return -1;
        }
        return 0;
    }

}
?>