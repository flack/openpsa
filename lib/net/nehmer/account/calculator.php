<?php
/**
 * @package net.nehmer.account
 * @author Henri Bergius, http://bergie.iki.fi 
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
 
/**
 * Karma calculator
 * 
 * @package net.nehmer.account
 */
class net_nehmer_account_calculator extends midcom_baseclasses_components_purecode
{
    public function __construct()
    {
        $this->_component = 'net.nehmer.account';
        parent::__construct();
        
        //Disable limits
        // TODO: Could this be done more safely somehow
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
    }
    
    private function count_comments($guid)
    {
        if (!$_MIDCOM->componentloader->load_graceful('net.nehmer.comments'))
        {
            return 0;
        }
        
        $qb = net_nehmer_comments_comment::new_query_builder();
        $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
        $qb->add_constraint('content', '<>', '');
        return round(sqrt($qb->count_unchecked()));
    }
    
    private function count_favourites($guid)
    {
        if (!$_MIDCOM->componentloader->load_graceful('net.nemein.favourites'))
        {
            return 0;
        }
        $qb = net_nemein_favourites_favourite_dba::new_query_builder();
        $qb->add_constraint('metadata.creator', '=', $guid);
        $qb->add_constraint('objectType', '<>', 'org_maemo_packages_package_instance');
        $qb->add_constraint('bury', '=', false);
        return round(sqrt($qb->count_unchecked()));      
    }
    
    private function count_buries($guid)
    {
        if (!$_MIDCOM->componentloader->load_graceful('net.nemein.favourites'))
        {
            return 0;
        }
        
        $qb = net_nemein_favourites_favourite_dba::new_query_builder();
        $qb->add_constraint('metadata.creator', '=', $guid);
        $qb->add_constraint('objectType', '<>', 'org_maemo_packages_package_instance');
        $qb->add_constraint('bury', '=', true);
        return round(sqrt($qb->count_unchecked()));
    }

    private function count_packagetesting($guid)
    {
        if (   !$_MIDCOM->componentloader->load_graceful('net.nemein.favourites')
            || !$_MIDCOM->componentloader->load_graceful('org.maemo.packages'))
        {
            return 0;
        }
        
        $qb = net_nemein_favourites_favourite_dba::new_query_builder();
        $qb->add_constraint('metadata.creator', '=', $guid);
        $qb->add_constraint('objectType', '=', 'org_maemo_packages_package_instance');
        return $qb->count_unchecked();
    }

    private function count_wikicreates($guid)
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('metadata.creator', '=', $guid);
        $qb->add_constraint('topic.component', '=', 'net.nemein.wiki');
        return $qb->count_unchecked();
    }

    private function count_wikiedits($guid)
    {
        $edits = 0;
        $rcs = $_MIDCOM->get_service('rcs');    
        $qb = midcom_db_article::new_query_builder();
        // TODO: Add this when wiki inserts all contributors to authors array
        // $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
        $qb->add_constraint('topic.component', '=', 'net.nemein.wiki');
        $pages = $qb->execute_unchecked();
        foreach ($pages as $page)
        {
            $object_rcs = $rcs->load_handler($page);
            $history = $object_rcs->list_history();
            foreach ($history as $data) 
            {
                if ($data['user'] == "user:{$guid}")
                {
                    // TODO: At some point we may consider line counts here
                    $edits++;
                }
            }
        }
        
        return $edits;
    }

    private function count_blogs($guid)
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
        $qb->add_constraint('topic.component', '=', 'net.nehmer.blog');
        
        if (!$this->_config->get('karma_socialnews_enable'))
        {
            // We're not valuating blog posts, just return their number
            return $qb->count();
        }
        
        if (!$_MIDCOM->componentloader->load_graceful('net.nemein.favourites'))
        {
            return $qb->count();
        }
        $blog_karma = 0;
                
        $blogs = $qb->execute_unchecked();

        foreach ($blogs as $blog)
        {
            $favourites = net_nemein_favourites_favourite_dba::count_by_objectguid($blog->guid);
            $buries = net_nemein_favourites_favourite_dba::count_buries_by_objectguid($blog->guid);
            $karma = 1 + $favourites - $buries;
            if ($karma < 1)
            {
                $karma = 1;
            }
            if ($karma > 10)
            {
                $karma = 10;
            }
            $blog_karma += $karma;
        }
        
        return round($blog_karma);
    }

    private function count_products($guid)
    {
        if (!$_MIDCOM->componentloader->load_graceful('org.openpsa.products'))
        {
            return 0;
        }
    
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
        
        if (!$this->_config->get('karma_productratings_enable'))
        {
            // We're not valuating products, just return their number
            return $qb->count();
        }
               
        $product_karma = 0;
                
        $products = $qb->execute_unchecked();

        foreach ($products as $product)
        {   
            $product_karma = $product_karma + 1 + $product->price;
        }
        
        return round($product_karma);
    }
    
    private function count_discussion($id)
    {
        if (!$_MIDCOM->componentloader->load_graceful('net.nemein.discussion'))
        {
            return 0;
        }
    
        $qb = net_nemein_discussion_post_dba::new_query_builder();
        $qb->add_constraint('sender', '=', $id);
        return round(sqrt($qb->count_unchecked()));
    }

    private function count_brainstorm($guid)
    {
        if (!$_MIDCOM->componentloader->load_graceful('org.maemo.brainstorm'))
        {
            return 0;
        }
    
        $qb = org_maemo_brainstorm_idea_dba::new_query_builder();
        $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
   
        $idea_karma = 0;
        $ideas = $qb->execute_unchecked();
        foreach ($ideas as $idea)
        {
            // Base karma for an idea based on status
            switch ($idea->status)
            {
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_UNDER_VOTE:
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_IN_DEVELOPMENT:
                    $karma_from_idea = 5;
                    break;
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_IMPLEMENTED:
                    $karma_from_idea = 10;
                    break;
                default:
                    $karma_from_idea = 0;
            }
            
            // Add score 
            $idea_karma += $karma_from_idea;
        }

        $qb = org_maemo_brainstorm_idea_solution_dba::new_query_builder();
        $qb->add_constraint('metadata.authors', 'LIKE', "%|{$guid}|%");
        $solution_karma = 0;
        $solutions = $qb->execute_unchecked();
        foreach ($solutions as $solution)
        {
            $idea = org_maemo_brainstorm_idea_dba::get_cached($solution->idea);
            // Base karma for an idea based on status
            switch ($idea->status)
            {
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_UNDER_VOTE:
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_IN_DEVELOPMENT:
                    $karma_from_solution = 2;
                    break;
                case ORG_MAEMO_BRAINSTORM_IDEA_STATUS_IMPLEMENTED:
                    // Check that solution is selected
                    $qb = org_maemo_brainstorm_idea_member_dba::new_query_builder();
                    $qb->add_constraint('idea', '=', $idea->id);
                    $qb->add_constraint('solution', '=', $solution->id);
                    if ($qb->count_unchecked() > 0)
                    {
                        $karma_from_solution = 10;
                    }
                    else
                    {
                        $karma_from_solution = 0;
                    }
                    break;
                default:
                    $karma_from_solution = 0;
            }
            
            // Add score 
            $solution_karma += $karma_from_solution;
        }

        return round($idea_karma + $solution_karma);
    }

    private function count_groups($id)
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $id);
        return $qb->count_unchecked();
    }

    private function calculate($object)
    {
        // Here we apply the special sauce
        $karma = array
        (
            'karma' => 0,
        );
        
        if ($this->_config->get('karma_comments_enable'))
        {
            $karma['comments'] = $this->count_comments($object->guid) * $this->_config->get('karma_comments_modifier');
            $karma['karma'] += $karma['comments'];
        }
        
        if ($this->_config->get('karma_favourites_enable'))
        {
            $karma['favourites'] = $this->count_favourites($object->guid) * $this->_config->get('karma_favourites_modifier');
            $karma['karma'] += $karma['favourites'];
        }
        
        if ($this->_config->get('karma_buries_enable'))
        {
            $karma['buries'] = $this->count_buries($object->guid) * $this->_config->get('karma_buries_modifier');
            $karma['karma'] += $karma['buries'];
        }
        
        if ($this->_config->get('karma_wikicreates_enable'))
        {
            $karma['wikicreates'] = $this->count_wikicreates($object->guid) * $this->_config->get('karma_wikicreates_modifier');
            $karma['karma'] += $karma['wikicreates'];
        }

        if ($this->_config->get('karma_wikiedits_enable'))
        {
            $karma['wikiedits'] = $this->count_wikiedits($object->guid) * $this->_config->get('karma_wikiedits_modifier');
            $karma['karma'] += $karma['wikiedits'];
        }
        
        if ($this->_config->get('karma_blogs_enable'))
        {
            $karma['blogs'] = $this->count_blogs($object->guid) * $this->_config->get('karma_blogs_modifier');
            $karma['karma'] += $karma['blogs'];
        }

        if ($this->_config->get('karma_products_enable'))
        {
            $karma['products'] = $this->count_products($object->guid) * $this->_config->get('karma_products_modifier');
            $karma['karma'] += $karma['products'];
        }

        if ($this->_config->get('karma_brainstorm_enable'))
        {
            $karma['brainstorm'] = $this->count_brainstorm($object->guid) * $this->_config->get('karma_brainstorm_modifier');
            $karma['karma'] += $karma['brainstorm'];
        }

        if ($this->_config->get('karma_packagetesting_enable'))
        {
            $karma['packagetesting'] = $this->count_packagetesting($object->guid) * $this->_config->get('karma_packagetesting_modifier');
            $karma['karma'] += $karma['packagetesting'];
        }

        if ($this->_config->get('karma_discussion_enable'))
        {
            $karma['discussion'] = $this->count_discussion($object->id) * $this->_config->get('karma_discussion_modifier');
            $karma['karma'] += $karma['discussion'];
        }
        
        if ($this->_config->get('karma_groups_enable'))
        {
            $karma['groups'] = $this->count_groups($object->id) * $this->_config->get('karma_groups_modifier');
            $karma['karma'] += $karma['groups'];
        }
        
        $plugins = $this->_config->get('karma_plugins');
        foreach ($plugins as $plugin => $plugin_config)
        {
            if (!is_callable($plugin_config['function']))
            {
                midcom_helper_misc::include_snippet_php($plugin_config['snippet']);
            }
            
            if (!is_callable($plugin_config['function']))
            {
                // Skip
                continue;
            }
            
            $value = @call_user_func($plugin_config['function'], $object);
            if ($value === true)
            {
                $value = 0;
            }
            $karma[$plugin] = (int) $value;
            $karma['karma'] += $karma[$plugin];
        }
       
        return $karma;
    }
    
    public function calculate_person($person, $cache = false)
    {
        $person_karma = $this->calculate($person);

        if ($cache)
        {
            foreach ($person_karma as $source => $karma)
            {
                if ($source == 'karma')
                {
                    if ($person->metadata->score != (int) $karma)
                    {
                        // Total karma is cached to metadata score for easy retrieval
                        $person->_use_activitystream = false;
                        $person->_use_rcs = false;
                        $person->metadata->set('score', (int) $karma);
                    }
                    continue;
                }
                
                // Karma per source goes to its own DBA class
                $qb = net_nehmer_account_karma_dba::new_query_builder();
                $qb->add_constraint('person', '=', $person->id);
                $qb->add_constraint('module', '=', $source);
                $karmas = $qb->execute();
                if (count($karmas) == 0)
                {
                    $karma_item = new net_nehmer_account_karma_dba();
                    $karma_item->person = $person->id;
                    $karma_item->module = $source;
                    $karma_item->create();
                }
                else
                {
                    $karma_item = $karmas[0];
                }
                
                if ($karma_item->karma != $karma)
                {
                    $karma_item->karma = $karma;
                    $karma_item->update();
                }
            }
            
            $person->set_parameter('net.nehmer.account', 'karma_calculated', gmdate('Y-m-d H:i:s'));
            $person->update();
        }
        
        return $person_karma;
    }
}
?>
