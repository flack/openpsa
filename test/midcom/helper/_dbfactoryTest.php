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
        $test_properties = array
        (
            'guid',
            'name',
            'title',
        );

        foreach ($test_properties as $property)
        {
            $this->assertTrue($dbfactory->property_exists($input, $property));
        }
    }

    public function provider_property_exists()
    {
        return array
        (
            array
            (
                new midgard_article
            ),
            array
            (
                new midcom_db_article,
            ),
            array
            (
                'midgard_article',
            ),
            array
            (
                'midcom_db_article',
            ),
            array
            (
                new midgard_topic,
            ),
            array
            (
                new midcom_db_topic,
            ),
            array
            (
                'midgard_topic',
            ),
            array
            (
                'midcom_db_topic',
            )
        );
    }
}
$test_instances = array
(
);

foreach ($test_instances as $instance)
{
    if (is_object($instance))
    {
        $instance_hr = '&lt;object of class ' . get_class($instance) . '&gt;';
    }
    else
    {
        $instance_hr = "'{$instance}'";
    }
    foreach ($test_properties as $property)
    {
        $stat = (int)midcom::get('dbfactory')->property_exists($instance, $property);
        echo "(int)\midcom::get('dbfactory')->property_exists({$instance_hr}, '{$property}') returned {$stat}<br>\n";
        $stat = (int)property_exists($instance, $property);
        echo "(int)property_exists({$instance_hr}, '{$property}') returned {$stat}<br>\n";
    }
}
