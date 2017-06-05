<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_httplib_helpersTest extends openpsa_testcase
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

    public function test_get_anchor_values()
    {
        $html = '<a rel="tag" title="tag title" href="tag-link" class="test">dummy</a>';
        $expected = [
            [
                'title' => 'tag title',
                'href' => 'tag-link',
                'value' => 'dummy',
            ]
        ];

        $ret = org_openpsa_httplib_helpers::get_anchor_values($html, 'tag');
        $this->assertEquals($expected, $ret);
    }
}
