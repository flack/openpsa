<?php
/**
 * @package midcom.helper.search
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Indexer Front-End, Viewer Class
 *
 * @package midcom.helper.search
 */
class midcom_helper_search_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, $args)
    {
        midcom::get('head')->add_link_head
        (
            array
            (
                'rel'   => 'search',
                'type'  => 'application/opensearchdescription+xml',
                'title' => $this->_topic->extra,
                'href'  => $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'opensearch.xml',
            )
        );
    }
}
?>