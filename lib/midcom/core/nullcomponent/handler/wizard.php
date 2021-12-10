<?php
/**
 * @package midcom.core.nullcomponent
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is a URL handler class for midcom.core.nullcomponent
 *
 * @package midcom.core.nullcomponent
 */
class midcom_core_nullcomponent_handler_wizard extends midcom_baseclasses_components_handler
{
    public function _handler_index(Request $request, array &$data)
    {
        if (midcom::get()->config->get('midcom_root_topic_guid')) {
            $data['message'] = 'Root folder is misconfigured';
        } else {
            $data['message'] = 'Root folder is not configured.';
        }
        midcom::get()->auth->require_admin_user($data['message'] . ' Please log in as administrator');

        $controller = $this->get_controller();
        if ($controller->handle($request) === controller::SAVE) {
            if ($response = $this->write_config($controller)) {
                return $response;
            }
        }
        $data['controller'] = $controller;

        return $this->show('wizard');
    }

    private function get_controller() : controller
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_wizard'));
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', 0);
        $qb->add_constraint('component', '<>', '');
        if ($qb->count() == 0) {
            $schemadb->get_first()->get_field('existing')['hidden'] = true;
        }

        $dm = new datamanager($schemadb);
        return $dm->get_controller();
    }

    /**
     * Write config file
     */
    private function write_config(controller $controller)
    {
        $values = $controller->get_form_values();
        if (!empty($values['existing'])) {
            $guid = $values['existing'];
        } elseif (!empty($values['title'])) {
            $topic = new midcom_db_topic;
            $topic->title = $values['title'];
            $topic->component = $values['component'];
            if (!$topic->create()) {
                throw new midcom_error(midcom_connection::get_error_string());
            }
            $guid = $topic->guid;
        } else {
            return;
        }
        $conf = '<?php' . "\n";
        $conf .= "//AUTO-GENERATED on " . date('r') . "\n";
        $conf .= '$GLOBALS[\'midcom_config_local\'][\'midcom_root_topic_guid\'] = "' . $guid . '";' . "\n";

        $project_dir = dirname(__DIR__, 5);
        if (str_contains($project_dir, '/vendor/')) {
            $project_dir = dirname($project_dir, 3);
        }

        if (!@file_put_contents($project_dir . '/config.inc.php', $conf)) {
            $this->_request_data['project_dir'] = $project_dir;
            $this->_request_data['conf'] = $conf;
            return $this->show('wizard-save-config');
        }
        return new midcom_response_relocate('');
    }
}
