<?php
/**
 * @package net.nemein.tag
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: clearold.php,v 1.1 2006/04/19 14:08:46 rambo Exp $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for periodical gallery sync
 * @package net.nemein.tag
 */
class net_nemein_tag_cron_clean extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return true;
    }

    /**
     * Find all old temporary reports and clear them.
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');

        $_MIDCOM->auth->request_sudo('net.nemein.tag');
        $qb_tags = net_nemein_tag_tag_dba::new_query_builder();
        $tags = $qb_tags->execute_unchecked();
        if (!is_array($tags))
        {
            // QB error
            $_MIDCOM->auth->drop_sudo();
            debug_pop();
            return;
        }
        foreach ($tags as $tag)
        {
            debug_add("Processing tag #{$tag->id} ('{$tag->tag}')");
            $qb_links = net_nemein_tag_link_dba::new_query_builder();
            $qb_links->add_constraint('tag', '=', $tag->id);
            $count = $qb_links->count_unchecked();
            if ($count === false)
            {
                // QB error, skip
                debug_add("There was QB level error, skip rest of the checks");
                continue;
            }
            if ($count > 0)
            {
                // Tag has links, skip
                debug_add("Tag has links to it, do not clean");
                continue;
            }
            debug_add("Cleaning dangling tag #{$tag->id} ('{$tag->tag}')", MIDCOM_LOG_INFO);
            if (!$tag->delete())
            {
                debug_add("Could not delete dangling tag #{$tag->id} ('{$tag->tag}'), errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        debug_add('done');
        $_MIDCOM->auth->drop_sudo();
        debug_pop();
        return;
    }
}
?>