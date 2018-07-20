<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents site interface class.
 *
 * Document management and WebDAV file share
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler, array $args)
    {
        // Pass topic to handlers
        $this->_request_data['directory'] = new org_openpsa_documents_directory($this->_topic);
        $this->_request_data['enable_versioning'] = $this->_config->get('enable_versioning');

        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();

        org_openpsa_widgets_grid::add_head_elements();
        org_openpsa_widgets_tree::add_head_elements();
    }
}
