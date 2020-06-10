<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use midcom\datamanager\extension\transformer\multipleTransformer;
use PHPUnit\Framework\TestCase;

class multipleTransformerTest extends TestCase
{
    /**
     * @return multipleTransformer
     */
    private function get_transformer($method)
    {
        $config = [
            'type_config' => [
                'multiple_storagemode' => $method
            ]
        ];
        return new multipleTransformer($config);
    }

    /**
     * @dataProvider provider_transform
     */
    public function test_transform($method, $input, $expected)
    {
        $transformer = $this->get_transformer($method);
        $this->assertEquals($expected, $transformer->transform($input));
    }

    /**
     * @dataProvider provider_transform
     */
    public function test_reverseTransform($method, $expected, $input)
    {
        $transformer = $this->get_transformer($method);
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    public function provider_transform()
    {
        return [
            ['serialized', null, []],
            ['serialized', 'a:2:{i:0;s:1:"a";i:1;s:1:"b";}', ['a', 'b']],
            ['imploded_wrapped', null, []],
            ['imploded_wrapped', '|a|b|', ['a', 'b']]
        ];
    }
}
