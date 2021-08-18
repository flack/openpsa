<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom;

use PHPUnit\Framework\TestCase;
use Monolog\Logger;
use midcom_debug;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class debugTest extends TestCase
{
    /**
     * @dataProvider provider_get_caller
     */
    public function test_get_caller(array $bt, string $expected)
    {
        $debug = new midcom_debug(new Logger('test'));
        $caller = $debug->get_caller(['extra' => []], $bt);

        $this->assertEquals(['extra' => [
            'caller' => $expected
        ]], $caller);
    }

    public function provider_get_caller() : array
    {
        return [
            // midcom_error::log in handler
            1 => [
                [[
                    'file' => 'lib/org/openpsa/expenses/handler/index.php',
                    'line' => 47,
                    'function' => 'log',
                    'class' => 'midcom_error',
                    'type' => '->'
                ], [
                    'file' => 'vendor/symfony/http-kernel/HttpKernel.php',
                    'line' => 157,
                    'function' => '_handler_index',
                    'class' => 'org_openpsa_expenses_handler_index',
                    'type' => '->'
                ], [
                    'file' => 'vendor/symfony/http-kernel/HttpKernel.php',
                    'line' => 79,
                    'function' => 'handleRaw',
                    'class' => 'Symfony\\Component\\HttpKernel\\HttpKernel',
                    'type' => '->'
                ]],
                'lib/org/openpsa/expenses/handler/index.php:47'
            ],
            // debug_add in style
            2 => [
                [[
                    'file' => 'lib/midcom/helper/style.php(138) : eval()\'d code',
                    'line' => 3,
                    'function' => 'debug_add',
                ], [
                    'file' => '/home/flack/git/openpsa/lib/midcom/helper/style.php',
                    'line' => 138,
                    'function' => 'eval'
                ], [
                    'file' => '/home/flack/git/openpsa/lib/midcom/helper/style.php',
                    'line' => 111,
                    'function' => 'render',
                    'class' => 'midcom_helper_style', 'type' => '->'
                ], [
                    'file' => '/home/flack/git/openpsa/lib/compat/ragnaroek.php',
                    'line' => 25,
                    'function' => 'show',
                    'class' => 'midcom_helper_style',
                    'type' => '->'
                ]],
                'lib/midcom/helper/style.php(138) : eval()\'d code:3'
            ],
            // debug_print_r in handler
            3 => [
                [[
                    'file' => 'lib/compat/ragnaroek.php',
                    'line' => 45,
                    'function' => 'print_r',
                    'class' => 'midcom_debug',
                    'type' => '->',
                ], [
                    'file' => 'lib/org/openpsa/expenses/handler/index.php',
                    'line' => 47,
                    'function' => 'debug_print_r',
                ], [
                    'file' => 'vendor/symfony/http-kernel/HttpKernel.php',
                    'line' => 157,
                    'function' => '_handler_index',
                    'class' => 'org_openpsa_expenses_handler_index',
                    'type' => '->',
                ], [
                    'file' => 'symfony/http-kernel/HttpKernel.php',
                    'line' => 79,
                    'function' => 'handleRaw',
                    'class' => 'Symfony\\Component\\HttpKernel\\HttpKernel',
                    'type' => '->',
                ]],
                'lib/org/openpsa/expenses/handler/index.php:47'
            ]
        ];
    }
}