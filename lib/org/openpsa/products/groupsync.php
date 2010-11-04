<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
/**
 * Helper class to manage a topic tree identical to the product groups -tree
 *
 * @see http://trac.midgard-project.org/ticket/1149
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_groupsync extends midcom_baseclasses_components_purecode
{
    /**
     * Instance of the midcom_db_topic defined as root
     */
    var $root_topic = null;
    /**
     * Instance of the org_openpsa_products_product_group_dba defined as root
     */
    var $root_group = null;
     /**
      * Whether to be verbose, ie echo all output or not
      */
    var $verbose = false;
    /**
     * Local copy of $this->_config->get('groupsync_topic_name_from')
     */
    var $name_from = null;

    /**
     * Local copy of $this->_config->get('groupsync_topic_title_from')
     */
    var $title_from = null;


    /**
     * Basic constructor, initialized the sync helper
     */
    function __construct()
    {
        $this->_component = 'org.openpsa.products';
        parent::__construct();
        $this->initialize();
    }

    /**
     * Load the required objects, will raise midcom level errors on trouble
     */
    function initialize()
    {
    
        // Load root topic
        $root_topic_guid = $this->_config->get('groupsync_root_topic');
        if (empty($root_topic_guid))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'GroupSync root topic not defined');
            // This will exit
        }
        $this->root_topic = new midcom_db_topic($root_topic_guid);
        if (   !$this->root_topic
            || !isset($this->root_topic->guid)
            || empty($this->root_topic->guid))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "GroupSync could not load topic '{$root_topic_guid}'");
            // This will exit
        }

        // Load root-group, from topic if possible and fall back to global config
        $root_group_guid = $this->root_topic->get_parameter('org.openpsa.products', 'root_group');
        if (empty($root_group_guid))
        {
            $root_group_guid = $this->_config->get('groupsync_root_group');
        }
        if (empty($root_group_guid))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'GroupSync root group not defined');
            // This will exit
        }
        $this->root_group = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
        if (   !$this->root_group
            || !isset($this->root_group->guid)
            || empty($this->root_group->guid))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "GroupSync could not load group '{$root_group_guid}'");
            // This will exit
        }

        // Sanity check groupsync_topic_name_from
        $this->name_from = $this->_config->get('groupsync_topic_name_from');
        if (!$_MIDCOM->dbfactory->property_exists($this->root_group, $this->name_from))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "GroupSync: product_group has no property '{$this->name_from}'");
            // This will exit
        }
        // Sanity check groupsync_topic_title_from
        $this->title_from = $this->_config->get('groupsync_topic_title_from');
        if (!$_MIDCOM->dbfactory->property_exists($this->root_group, $this->title_from))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "GroupSync: product_group has no property '{$this->title_from}'");
            // This will exit
        }

        $_MIDCOM->componentloader->load('midcom.helper.reflector');
    }

    /**
     * Handles newly created product groups
     *
     * @param object $object org_openpsa_products_product_group_dba instance (refreshed)
     */
    function on_created(&$group)
    {
        $parent_group = $group->get_parent();
        if (!$parent_group)
        {
            $this->log_message("Could not resolve parent group for #{$group->id}, falling back to full sync", false, MIDCOM_LOG_INFO);
            $this->full_sync();
            return true;
        }
        $parent_topic = $this->get_group_topic($parent_group);
        if (!$parent_topic)
        {
            $this->log_message("Could not resolve corresponding topic for group #{$parent_group->id}, falling back to full sync", false, MIDCOM_LOG_INFO);
            $this->full_sync();
            return true;
        }
        $subtopic = $this->create_subtopic_from_group($parent_topic, $parent_group);
        if (!is_object($subtopic))
        {
            return false;
        }
        return true;
    }

    /**
     * Handles updated product groups
     *
     * NOTE: Cannot handle moves, you need to call full_sync to catch those properly
     *
     * @param object $object org_openpsa_products_product_group_dba instance (refreshed)
     */
    function on_updated(&$group)
    {
        $topic = $this->get_group_topic($group);
        if (!$topic)
        {
            $this->log_message("Could not resolve corresponding topic for group #{$group->id}, falling back to full sync", false, MIDCOM_LOG_INFO);
            $this->full_sync();
            return true;
        }
        $topic->name = midcom_generate_urlname_from_string($group->{$this->name_from});
        $topic->title = $group->{$this->title_from};
        $topic->extra = $topic->title;
        if (!$topic->update())
        {
            $this->log_message("Failed to update topic #{$topic->id}, last Midgard error:" . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }

    /**
     * Gets the topic (INTREEd to our root) which has given group as root
     *
     * @param object $group reference to org_openpsa_products_product_group_dba object
     * @return midcom_db_topic object or boolean false
     */
    function get_group_topic(&$group)
    {
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', 'INTREE', $this->root_topic->id);
        $qb->add_constraint('component', '=', 'org.openpsa.products');
        // This is not exactly correct but close enough
        $qb->add_constraint('parameter.name', '=', 'root_group');
        $qb->add_constraint('parameter.value', '=', $group->guid);
        $results = $qb->execute();
        unset($qb);
        if (!is_array($results))
        {
            $this->log_message('QB failed fatally, last Midgard error:' . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            return false;
        }
        if (count($results) > 1)
        {
            $this->log_message('Got more than one result, this should not happen', false, MIDCOM_LOG_ERROR);
            return false;
        }
        if (empty($results))
        {
            return false;
        }
        $ret = $results[0];
        unset($results);
        return $ret;
    }

    /**
     * Handles deleted
     *
     * @param object $object org_openpsa_products_product_group_dba instance (copy from before deletion)
     */
    function on_deleted(&$group)
    {
        $topic = $this->get_group_topic($group);
        if (!$topic)
        {
            $this->log_message("Could not find corresponding topic for group #{$group->id}, doing nothing", false, MIDCOM_LOG_INFO);
            return false;
        }
        return $this->delete_topic($topic);
    }

    function full_sync()
    {
        $this->_full_sync_recursive($this->root_topic, $this->root_group, true);
    }

    /**
     * Gets subtopics of given subtopic via QB
     *
     * @param object $topic reference to midcom_db_topic
     * @return array of QB results rekeyed by url-name
     */
    function _get_subtopics(&$topic)
    {
        //$this->log_message("Called for #{$topic->id} (" . midcom_helper_reflector_tree::resolve_path($topic)  . ")", false, MIDCOM_LOG_DEBUG);
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $topic->id);
        $qb->add_constraint('component', '=', 'org.openpsa.products');
        $results = $qb->execute();
        unset($qb);
        $ret = array();
        if (!is_array($results))
        {
            unset($results);
            $this->log_message('QB failed fatally, last Midgard error:' . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            // Return the empty array anyways
            return $ret;
        }
        foreach ($results as $k => $subtopic)
        {
            $ret[$subtopic->name] = $subtopic;
        }
        unset($results, $k, $subtopic);
        return $ret;
    }

    /**
     * Gets subgroups of given subtopic via QB
     *
     * @param object $topic reference to org_openpsa_products_product_group_dba
     * @return array of QB rekeyed by url-name (to be used for topic, so to make array comparisons easy)
     */
    function _get_subgroups(&$group)
    {
        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $group->id);
        $results = $qb->execute();
        unset($qb);
        $ret = array();
        if (!is_array($results))
        {
            unset($results);
            $this->log_message('QB failed fatally, last Midgard error:' . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            // Return the empty array anyways
            return $ret;
        }
        foreach ($results as $k => $subgroup)
        {
            $urlname = midcom_generate_urlname_from_string($subgroup->{$this->name_from});
            $ret[$urlname] = $subgroup;
        }
        unset($results, $k, $subgroup, $urlname);
        return $ret;
    }

    function _full_sync_recursive(&$topic, &$group, $root_level = false)
    {
        /**
         * Disabled forcing these in case the users need to do something weird
         *
        $topic->set_parameter('org.openpsa.products', 'display_navigation', '0');
        $topic->set_parameter('org.openpsa.products', 'disable_subgroups_on_frontpage', '1');
         */

        $subtopics = $this->_get_subtopics($topic);
        $subgroups = $this->_get_subgroups($group);

        /*
        echo "DEBUG: \$subtopics <pre>\n";
        var_dump($subtopics);
        echo "<pre>\n";
        echo "DEBUG: \$subgroups <pre>\n";
        var_dump($subgroups);
        echo "<pre>\n";
        */


        // This is array of *topics*
        $delete_topics_names = array_diff(array_keys($subtopics), array_keys($subgroups));
        $delete_topics = array();
        foreach ($delete_topics_names as $name)
        {
            $delete_topics[$name] =& $subtopics[$name];
        }

        // This is array of *groups*
        $create_topics_from_groups_names = array_diff(array_keys($subgroups), array_keys($subtopics));
        $create_topics_from_groups = array();
        foreach ($create_topics_from_groups_names as $name)
        {
            $create_topics_from_groups[$name] =& $subgroups[$name];
        }

        /*
        echo "DEBUG: \$delete_topics <pre>\n";
        var_dump($delete_topics);
        echo "<pre>\n";
        echo "DEBUG: \$create_topics_from_groups <pre>\n";
        var_dump($create_topics_from_groups);
        echo "<pre>\n";
        */

        // Handle deletions, this is easy...
        foreach ($delete_topics as $name => $subtopic)
        {
            $this->log_message("Deleting topic #{$subtopic->id} (" .  midcom_helper_reflector_tree::resolve_path($subtopic) . ")", false, MIDCOM_LOG_INFO);
            $this->delete_topic($subtopic);
            unset($subtopics[$name],  $subtopic);
        }
        unset($delete_topics, $name, $subtopic);

        // Handle Creations/moves 
        foreach ($create_topics_from_groups as $name => $subgroup)
        {
            // We have subtopic in the tree for this product group already, move it instead of creating new one
            $move_topic = $this->get_group_topic($subgroup);
            if ($move_topic)
            {
                $this->log_message("Found topic #{$move_topic->id} (" .  midcom_helper_reflector_tree::resolve_path($move_topic) . ") for group #{$subgroup->id}, moving instead of creating", false, MIDCOM_LOG_INFO);
                $move_topic->up = $topic->id;
                $move_topic->name = $name;
                if (!$move_topic->update())
                {
                    $this->log_message("Could not move #{$move_topic->id}, last Midgard error:" . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
                    unset($move_topic);
                    continue;
                }
                $subtopics[$name] = $move_topic;
                unset($move_topic);
                continue;
            }
            unset($move_topic);
            
            // No existing topic, create new one
            $subtopic = $this->create_subtopic_from_group($topic, $subgroup, $name);
            if (!is_object($subtopic))
            {
                unset($subtopic);
                continue;
            }
            $subtopics[$name] = $subtopic;
            unset($subtopic);
        }
        unset($create_topics_from_groups, $name, $subgroup);

        // Recurse
        foreach($subtopics as $name => $subtopic)
        {
            if (!isset($subgroups[$name]))
            {
                $this->log_message("Could not find corresponding subgroup for subtopic {$name}, skipping from recursion", false, MIDCOM_LOG_WARN);
                continue;
            }
            $subgroup =& $subgroups[$name];
            $this->log_message("Recursing name {$name} (topic #{$subtopic->id}, group #{$subgroup->id})", false, MIDCOM_LOG_DEBUG);
            $this->_full_sync_recursive($subtopic, $subgroup);
            unset($subgroup);
        }
        unset($subtopic, $name);

    }

    /**
     * Helper to create a subtopic based on group data
     *
     * @param object $parent_topic reference to topic to create under
     * @param object $group reference to group to get the data from
     * @param string $name name to use, leave out for autogeneration
     * @return object or false on error
     */
    function create_subtopic_from_group(&$parent_topic, &$group, $name=null)
    {
        if (!$name)
        {
            $name = midcom_generate_urlname_from_string($group->{$this->name_from});
        }
        $subtopic = new midcom_db_topic();
        $subtopic->name = $name;
        $subtopic->title = $group->{$this->title_from};
        $subtopic->extra = $subtopic->title;
        $subtopic->up = $parent_topic->id;
        $subtopic->component = 'org.openpsa.products';
        if (!$subtopic->create())
        {
            $this->log_message("Could not create new subtopic {$subtopic->name} under topic #{$parent_topic->id}, last Midgard error:" . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            return false;
        }
        $this->log_message("Created new subtopic #{$subtopic->id} ({$subtopic->name}) under topic #{$parent_topic->id}", false, MIDCOM_LOG_INFO);
        $subtopic->set_parameter('org.openpsa.products', 'root_group', $group->guid);
        $subtopic->set_parameter('org.openpsa.products', 'display_navigation', '0');
        $subtopic->set_parameter('org.openpsa.products', 'disable_subgroups_on_frontpage', '1');

        return $subtopic;
    }

    /**
     * Deletes given topic and subtopics if only children are o.o.products -topics
     *
     * Otherwise removes the root_group -parameter from the topic
     *
     * @param object $topic instance of midcom_db_topic
     */
    function delete_topic(&$topic)
    {
        $can_delete = true;
        $children = midcom_helper_reflector_tree::get_child_objects($topic);
        if (!is_array($children))
        {
            $this->log_message('midcom_helper_reflector_tree::get_child_objects() failed fatally, aborting', false, MIDCOM_LOG_ERROR);
            return false;
        }
        foreach ($children as $class => $class_children)
        {
            $class_children =& $children[$class];
            foreach ($class_children as $k => $child)
            {
                $child =& $class_children[$k];
                if (   !$_MIDCOM->dbfactory->is_a($child, 'midgard_topic')
                    || $child->component !== 'org.openpsa.products')
                {
                    unset($class_children[$k], $child);
                    $can_delete = false;
                    continue;
                }
                $this->delete_topic($child);
                unset($class_children[$k], $child);
            }
            unset($children[$class]);
        }
        unset($children);
        if (!$can_delete)
        {
            $this->log_message("Topic #{$topic->id} has dependents we cannot remove, only removing root_group parameter", false, MIDCOM_LOG_INFO);
            return $topic->delete_parameter('org.openpsa.products', 'root_group');
        }
        if (!$topic->delete())
        {
            $this->log_message("Could not delete #{$topic->id}, last Midgard error:" . midcom_application::get_error_string(), false, MIDCOM_LOG_ERROR);
            return false;
        }
        $this->log_message("Deleted #{$topic->id}", false, MIDCOM_LOG_DEBUG);
        return true;
    }

    /**
     * Logs a message midcom log, if $this->verbose is set, will also echo the message.
     */
    function log_message($message, $echo = false, $level = MIDCOM_LOG_DEBUG)
    {
        // Push the previous class/method as message originator
        $bt = debug_backtrace();
        debug_push_class($bt[1]['class'], $bt[1]['function']);
        unset($bt);
        debug_add($message, $level);
        debug_pop();

        if (   $echo
            || $this->verbose)
        {
            $this->echo_message($message);
        }
    }

    /**
     * Outputs the given message with timestamp
     */
    function echo_message($message)
    {
        echo date('Y-m-d H:i:s') . ": {$message}<br/>\n";
        flush();
    }
}
?>