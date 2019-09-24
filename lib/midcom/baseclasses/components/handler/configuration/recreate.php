<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Batch image recreation
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_handler_configuration_recreate extends midcom_baseclasses_components_handler_configuration
{
    /**
     * Must return an array of datamanager objects indexed by
     * DBA class name.
     *
     * @return midcom\datamanager\datamanager[]
     */
    abstract public function _load_datamanagers();

    /**
     * Must return an array of DBA objects.
     *
     * @return midcom_core_dbaobject[]
     */
    abstract public function _load_objects();

    /**
     * Handler for regenerating all derived images used in the folder.
     */
    public function _handler_recreate(Request $request, array &$data)
    {
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        if (!$request->request->has('midcom_baseclasses_components_handler_configuration_recreateok')) {
            return new midcom_response_relocate('config/');
        }

        $data['datamanagers'] = $this->_load_datamanagers();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('recreate images for folder %s'), $data['topic']->extra));
        $workflow = $this->get_workflow('viewer');
        return $workflow->run($request);
    }

    /**
     * Show the recreation screen
     *
     * @param string $handler_id    Name of the handler
     * @param array  $data          Miscellaneous output data
     */
    public function _show_recreate($handler_id, array &$data)
    {
        midcom::get()->disable_limits();

        midcom::get()->style->data['objects'] = $this->_load_objects();
        midcom::get()->style->data['datamanagers'] = $data['datamanagers'];

        midcom::get()->style->show_midcom('dm2_config_recreate');
    }
}
