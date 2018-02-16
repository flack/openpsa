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
class net_nemein_tag_handlerTest extends openpsa_testcase
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
