<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\routing;

use openpsa_testcase;
use midcom;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class resolverTest extends openpsa_testcase
{
    /**
     * Handlers that still declare array $args must get the route parameters in URL order
     */
    public function testLegacyArgs()
    {
        $person = self::create_user(true);
        $request = Request::createFromGlobals();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');
        $this->run_handler('org.openpsa.directmarketing', ['campaign', 'unsubscribe_all_future', $person->guid, 'test'], $request);
        midcom::get()->auth->drop_sudo();

        $legacy_handler = function (array $args) {
        };
        $arguments = (new ArgumentResolver)->getArguments($request, $legacy_handler);

        $this->assertSame([[$person->guid, 'test']], $arguments);
    }
}
