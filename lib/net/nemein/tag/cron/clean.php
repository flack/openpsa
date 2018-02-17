<?php
/**
 * @package net.nemein.tag
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for periodical tag cleanup
 *
 * @package net.nemein.tag
 */
class net_nemein_tag_cron_clean extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find all old temporary reports and clear them.
     */
    public function execute()
    {
        midcom::get()->auth->request_sudo('net.nemein.tag');
        $qb_tags = net_nemein_tag_tag_dba::new_query_builder();
        $tags = $qb_tags->execute_unchecked();

        foreach ($tags as $tag) {
            debug_add("Processing tag #{$tag->id} ('{$tag->tag}')");
            $qb = net_nemein_tag_link_dba::new_query_builder();
            $qb->add_constraint('tag', '=', $tag->id);

            if ($qb->count_unchecked() > 0) {
                // Tag has links, skip
                debug_add("Tag has links to it, do not clean");
                continue;
            }
            debug_add("Cleaning dangling tag #{$tag->id} ('{$tag->tag}')", MIDCOM_LOG_INFO);
            if (!$tag->delete()) {
                debug_add("Could not delete dangling tag #{$tag->id} ('{$tag->tag}'), errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        midcom::get()->auth->drop_sudo();
    }
}
