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
    public function _on_handle($handler, $args)
    {
        // Pass topic to handlers
        $this->_request_data['directory'] = new org_openpsa_documents_directory($this->_topic->id);
        $this->_request_data['enable_versioning'] = $this->_config->get('enable_versioning');

        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        org_openpsa_core_grid_widget::add_head_elements();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.cookie.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.documents/dynatree/jquery.dynatree.min.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL."/org.openpsa.documents/dynatree/skin/ui.dynatree.css");
    }
}
?>