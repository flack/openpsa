<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\workflow;

use PHPUnit\Framework\TestCase;
use midcom\workflow\datamanager;
use Symfony\Component\HttpFoundation\Response;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class dialogTest extends TestCase
{
    public function test_js_response()
    {
        $dialog = new datamanager();
        $response = $dialog->js_response('test');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('midcom.workflow/dialog.js', $response->getContent());
    }
}
