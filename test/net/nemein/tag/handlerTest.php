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
        return array(
            array(
                'context: tagname =value',
                'tagname'
            ),
            array(
                'context:"Tag Name"',
                '"Tag Name"'
            ),
            array(
                'tagname=val',
                'tagname'
            ),
            array(
                'tagname',
                'tagname'
            ),
        );
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
        return array(
            array(
                'context:tagname=value ',
                'value'
            ),
            array(
                'context:tagname="Tag Name"',
                '"Tag Name"'
            ),
            array(
                'tagname= val',
                'val'
            ),
            array(
                'tagname',
                ''
            ),
        );
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
        return array(
            array(
                'context :tagname=value',
                'context'
            ),
            array(
                'context:"Tag Name"',
                'context'
            ),
            array(
                'tagname=val',
                ''
            ),
            array(
                'tagname',
                ''
            ),
        );
    }

    public function test_get_objects_with_tags()
   {
       $person = $this->create_object('midcom_db_person');
       $tag = $this->create_object('net_nemein_tag_tag_dba', array('tag' => uniqid()));
       $this->create_object('net_nemein_tag_link_dba', array(
           'tag' => $tag->id,
           'fromGuid' => $person->guid,
           'fromClass' => 'midcom_db_person'
       ));
       $result = net_nemein_tag_handler::get_objects_with_tags(array($tag->tag), array('midcom_db_person'));
       $this->assertCount(1, $result);
       $this->assertEquals($person->guid, $result[0]->guid);
   }
}
