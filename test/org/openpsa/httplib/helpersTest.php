<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\httplib;

use PHPUnit\Framework\TestCase;
use org_openpsa_httplib_helpers;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class helpersTest extends TestCase
{
    public function test_get_link_values()
    {
        $html = '<html><head><link rel="alternate" title="alt title" href="alt-link"></head></html>';
        $expected = [
            [
                'title' => 'alt title',
                'href' => 'alt-link',
                'hreflang' => false,
            ]
        ];
        $ret = org_openpsa_httplib_helpers::get_link_values($html, 'alternate');
        $this->assertEquals($expected, $ret);
    }

    public function test_get_meta_value()
    {
        $html = '<html><head><meta name="icbm" content="1,1"></head></html>';
        $ret = org_openpsa_httplib_helpers::get_meta_value($html, 'icbm');
        $this->assertEquals('1,1', $ret);
    }

    public function test_get_missing_meta_value()
    {
        $html = '<html><head><meta name="not-what-were-looking-for" content="1,1"></head></html>';
        $ret = org_openpsa_httplib_helpers::get_meta_value($html, 'icbm');
        $this->assertEquals(null, $ret);
    }
}
