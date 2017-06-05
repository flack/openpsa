<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp\workflow;

use Midgard\CreatePHP\WorkflowInterface;

/**
 * Delete workflow implementation
 *
 * @package openpsa.createphp
 */
class delete implements WorkflowInterface
{
    public function getToolbarConfig($object)
    {
        if (    $object->can_do('midgard:delete')
             && !empty($object->up)) {
            // show delete for all collection children
            return [
                'name' => "delete",
                'label' => \midcom::get()->i18n->get_l10n('midcom')->get('delete'),
                'action' => [
                    'type' => "backbone_destroy"
                ],
                'type' => "button"
            ];
        }
        return null;
    }

    public function run($object)
    {
        if (!$object->delete()) {
            throw new \midcom_error("failed to delete " . get_class($object) . " #" . $object->id . ': ' . \midcom_connection::get_error_string());
        }
        return [];
    }
}
