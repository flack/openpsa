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
class midgard_admin_asgard_schemadbTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_sort
     */
    public function test_sort($object, $first, $second, $expected)
    {
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $config = midcom_baseclasses_components_configuration::get('midgard.admin.asgard', 'config');
        $schemadb = new midgard_admin_asgard_schemadb($object, $config);

        $this->assertEquals($expected, $schemadb->sort_schema_fields($first, $second));

        midcom::get()->auth->drop_sudo();
    }

    public function provider_sort()
    {
        return [
            [
                new midcom_db_article,
                'name',
                'abstract',
                -1
            ],
            [
                new midcom_db_article,
                'abstract',
                'name',
                1
            ],
            [
                new midcom_db_article,
                'title',
                'name',
                1
            ],
            [
                new midcom_db_article,
                'abstract',
                'content',
                -1
            ],
            [
                new midcom_db_article,
                'abstract',
                'extra1',
                -1
            ],
            [
                new midcom_db_article,
                'up',
                'name',
                1
            ],
            [
                new midcom_db_article,
                'up',
                'extra1',
                -1
            ],
            [
                new midcom_db_article,
                'up',
                'topic',
                1
            ],
            [
                new org_openpsa_calendar_event_dba,
                'start',
                'title',
                1
            ],
            [
                new org_openpsa_calendar_event_dba,
                'start',
                'description',
                1
            ],
            [
                new org_openpsa_calendar_event_dba,
                'start',
                'up',
                1
            ],
            [
                new org_openpsa_calendar_event_dba,
                'start',
                'end',
                -1
            ],
            [
                new org_openpsa_calendar_event_dba,
                'start',
                'orgOpenpsaOwnerWg',
                -1
            ],
            [
                new midcom_db_person,
                'handphone',
                'email',
                -1
            ],
            [
                new midcom_db_person,
                'handphone',
                'lastname',
                1
            ],
            [
                new midcom_db_person,
                'handphone',
                'extra',
                1
            ],
            [
                new midcom_db_person,
                'handphone',
                'homephone',
                -1
            ],
            [
                new midcom_db_person,
                'street',
                'firstname',
                1
            ],
            [
                new midcom_db_person,
                'street',
                'extra',
                1
            ],
            [
                new midcom_db_person,
                'street',
                'homephone',
                1
            ],
            [
                new midcom_db_person,
                'street',
                'city',
                -1
            ],
            [
                new midcom_db_person,
                'street',
                'email',
                -1
            ]
        ];
    }
}
