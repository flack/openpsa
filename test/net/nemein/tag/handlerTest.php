<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nemein\tag;

use openpsa_testcase;
use net_nemein_tag_handler;
use midcom_db_person;
use net_nemein_tag_tag_dba;
use net_nemein_tag_link_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class handlerTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_resolve_tagname
     */
    public function test_resolve_tagname($input, $expected)
    {
        $this->assertEquals($expected, net_nemein_tag_handler::resolve_tagname($input));
    }

    public function provider_resolve_tagname()
    {
        return [
            [
                'context: tagname =value',
                'tagname'
            ],
            [
                'context:"Tag Name"',
                '"Tag Name"'
            ],
            [
                'tagname=val',
                'tagname'
            ],
            [
                'tagname',
                'tagname'
            ],
        ];
    }

    /**
     * @dataProvider provider_resolve_value
     */
    public function test_resolve_value($input, $expected)
    {
        $this->assertEquals($expected, net_nemein_tag_handler::resolve_value($input));
    }

    public function provider_resolve_value()
    {
        return [
            [
                'context:tagname=value ',
                'value'
            ],
            [
                'context:tagname="Tag Name"',
                '"Tag Name"'
            ],
            [
                'tagname= val',
                'val'
            ],
            [
                'tagname',
                ''
            ],
        ];
    }

    /**
     * @dataProvider provider_resolve_context
     */
    public function test_resolve_context($input, $expected)
    {
        $this->assertEquals($expected, net_nemein_tag_handler::resolve_context($input));
    }

    public function provider_resolve_context()
    {
        return [
            [
                'context :tagname=value',
                'context'
            ],
            [
                'context:"Tag Name"',
                'context'
            ],
            [
                'tagname=val',
                ''
            ],
            [
                'tagname',
                ''
            ],
        ];
    }

    /**
     * @dataProvider provider_string2tag_array
     */
    public function test_string2tag_array($input, $expected)
    {
        $this->assertEquals($expected, net_nemein_tag_handler::string2tag_array($input));
    }

    public function provider_string2tag_array()
    {
        return [
            [
                '',
                []
            ],
            [
                'dummy',
                ['dummy' => '']
            ],
            [
                '"dummy value"',
                ['dummy value' => '']
            ],
            [
                'tag1 "dummy value" tag2',
                ['tag1' => '', 'dummy value' => '', 'tag2' => '']
            ],
        ];
    }

    public function test_get_objects_with_tags()
    {
        $person = $this->create_object(midcom_db_person::class);
        $tag = $this->create_object(net_nemein_tag_tag_dba::class, ['tag' => uniqid('tag')]);
        $this->create_object(net_nemein_tag_link_dba::class, [
            'tag' => $tag->id,
            'fromGuid' => $person->guid,
            'fromClass' => 'midcom_db_person'
        ]);
        $result = net_nemein_tag_handler::get_objects_with_tags([$tag->tag], ['midcom_db_person']);
        $this->assertCount(1, $result);
        $this->assertEquals($person->guid, $result[0]->guid);
    }
}
