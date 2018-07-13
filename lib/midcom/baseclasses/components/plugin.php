<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Base class for plugins
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_plugin extends midcom_baseclasses_components_handler
{
    public function initialize(midcom_baseclasses_components_request $master)
    {
        $this->_request_data =& $master->_request_data;
        $this->_topic = $master->_topic;
        $this->_request_data['l10n'] = $this->_l10n;

        $this->_on_initialize();
    }
}
