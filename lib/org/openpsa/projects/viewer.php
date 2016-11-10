<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects site interface class.
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler, array $args)
    {
        $this->_request_data['view'] = 'default';

        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.projects/projects.css");
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param org_openpsa_projects_task_dba $task
     * @param mixed $handler The current handler
     */
    public static function add_breadcrumb_path($task, $handler)
    {
        $tmp = array();
        while ($task) {
            if (is_a($task, 'org_openpsa_projects_project')) {
                $tmp["project/{$task->guid}/"] = $task->title;
            } else {
                $tmp["task/{$task->guid}/"] = $task->title;
            }
            $task = $task->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $url => $title) {
            $handler->add_breadcrumb($url, $title);
        }
    }
}
