<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

/**
 * @package midcom.workflow
 */
class chooser extends datamanager
{
    public function handle_save() : string
    {
        $dm = $this->controller->get_datamanager();
        $data = $dm->get_content_raw();
        $object = $dm->get_storage()->get_value();
        $data['guid'] = $object->guid;
        $data['id'] = $object->id;
        $data['pre_selected'] = true;
        return 'add_item(' . json_encode($data) . ');';
    }
}
