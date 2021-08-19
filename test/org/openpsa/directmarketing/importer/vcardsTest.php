<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\importer;

use midcom\datamanager\schemadb;
use PHPUnit\Framework\TestCase;
use org_openpsa_directmarketing_importer_vcards;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class vcardsTest extends TestCase
{
    public function testHandler_index()
    {
        $schemadbs = [
            'person' => schemadb::from_path('file:/org/openpsa/contacts/config/schemadb_default_person.inc'),
            'campaign_member' => schemadb::from_path('file:/org/openpsa/directmarketing/config/schemadb_default_campaign_member.inc'),
            'organization' => schemadb::from_path('file:/org/openpsa/contacts/config/schemadb_default_organization.inc'),
            'organization_member' => schemadb::from_path('file:/org/openpsa/contacts/config/schemadb_default_member.inc'),
        ];

        $importer = new org_openpsa_directmarketing_importer_vcards($schemadbs);
        $result = $importer->parse(__DIR__ . '/__files/test.vcf');

        $expected = [
            [
                'person' => [
                    'lastname' => 'Lastname',
                    'firstname' => 'Firstname',
                    'workphone' => '01010101',
                    'homephone' => '+002 55 555',
                    'handphone' => '10101 2222',
                    'email' => 'test@openpsa2.org',
                    'external-uid' => '1NGFoYl9tK'
                ],
                'organization' => [
                    'official' => 'Test Organization'
                ],
                'organization_member' => []
            ],
            [
                'person' => [
                    'lastname' => 'Empty',
                    'firstname' => 'Almost',
                    'external-uid' => 'CHii6JizRB'
                ],
                'organization' => [],
                'organization_member' => []
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
