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
class midcom_helper__dbfactoryTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_property_exists
     */
    public function test_property_exists($input)
    {
        $dbfactory = new midcom_helper__dbfactory;
        $test_properties = array(
            'guid',
            'name',
            'title',
        );

        foreach ($test_properties as $property) {
            $this->assertTrue($dbfactory->property_exists($input, $property), 'Property ' . $property . ' not found');
        }
    }

    public function provider_property_exists()
    {
        return array(
            array(
                new midgard_article
            ),
            array(
                new midcom_db_article,
            ),
            array(
                'midgard_article',
            ),
            array(
                'midcom_db_article',
            ),
            array(
                new midgard_topic,
            ),
            array(
                new midcom_db_topic,
            ),
            array(
                'midgard_topic',
            ),
            array(
                'midcom_db_topic',
            )
        );
    }
}
