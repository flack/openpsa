<?php

/**
 * @package midcom.helper.search
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: navigation.php 20859 2009-03-05 20:10:51Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Indexer Front-End, NAP interface Class
 * 
 * Nothing special in here, we stick to the defaults as we don't have any
 * leaves right now anyway.
 * 
 * @package midcom.helper.search
 */
class midcom_helper_search_navigation extends midcom_baseclasses_components_navigation
{
    function get_node()
    {
        return parent::get_node();
    }
}