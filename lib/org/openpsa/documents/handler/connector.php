<?php
/**
 * @package org.openpsa.documents
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents search handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_connector extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_connector($handler_id, array $args, array &$data)
    {
        $options = array
        (
            'roots' => array
            (
                array
                (
                    'driver' => 'Openpsa',
                    'path' => $this->_topic->guid
                )
            )
        );

        $connector = new elFinderConnector(new elFinder($options));
        $connector->run();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_goto($handler_id, array $args, array &$data)
    {
        $parts = explode('_', $args[0]);
        $guid = base64_decode($parts[1]);
        $url = midcom::get()->permalinks->resolve_permalink($guid);
        if (!$url)
        {
            throw new midcom_error_notfound('Could not resolve URL for ' . $guid);
        }
        return new midcom_response_relocate($url);
    }
}