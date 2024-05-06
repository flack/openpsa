<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\indexer;

use PHPUnit\Framework\TestCase;
use midcom_services_indexer_document;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class documentTest extends TestCase
{
    /**
     * @dataProvider provider_html2text
     */
    public function test_html2text($in, $out)
    {
        $document = new midcom_services_indexer_document;

        $this->assertEquals($out, $document->html2text($in));
    }

    public static function provider_html2text()
    {
        return [
            ['some string', 'some string'],
            ['<strong>some</strong> string', 'some string'],
            ["<strong>some\n</strong> string", 'some string'],
            ['<strong id="dummy">some</strong> string', 'some string'],
            ['<strong id="dummy">some</strong><img src="test.jpg" />string', 'some string'],
            ['<!-- pls ignore --><strong id="dummy">some</strong><img src="test" />string', 'some string'],
            ['1 + 2 > -5', '1 + 2 > -5'],
            ['1 + 2 &gt; -5', '1 + 2 > -5'],
        ];
    }
}
