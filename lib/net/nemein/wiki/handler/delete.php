<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage delete handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_delete extends midcom_baseclasses_components_handler
{
    use net_nemein_wiki_handler;

    /**
     * @param array $args The argument list.
     */
    public function _handler_delete(array $args)
    {
        $page = $this->load_page($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $page]);
        return $workflow->run();
    }
}
