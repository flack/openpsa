<?php
/**
 * @package midcom.workflow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\workflow;

use midcom_response_styled;
use midcom_core_context;
use midcom;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package midcom.workflow
 */
class viewer extends dialog
{
    public function get_button_config() : array
    {
        return [
            MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('view', 'midcom'),
            MIDCOM_TOOLBAR_OPTIONS => [
                'data-dialog' => 'dialog',
            ]
        ];
    }

    public function run(Request $request) : Response
    {
        $context = midcom_core_context::get();
        $this->add_dialog_js();
        midcom::get()->style->append_styledir(__DIR__ . '/style');
        return new midcom_response_styled($context, 'POPUP');
    }
}
