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
class midcom_services_cronTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_load_jobs
     */
    public function test_load_jobs($recurrence, $jobs, $expected)
    {
        $cron = new midcom_services_cron($recurrence);

        $actual = $cron->load_jobs($jobs);

        $this->assertEquals($expected, $actual);
    }

    public function provider_load_jobs()
    {
        return array(
            array(
                MIDCOM_CRON_MINUTE,
                array(),
                array()
            ),
            array(
                MIDCOM_CRON_MINUTE,
                array(
                    'midcom.services.at' => array(
                        array(
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ),
                        array(
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        )
                    )
                ),
                array(
                    array(
                        'component' => 'midcom.services.at',
                        'handler' => 'midcom_services_at_cron_check',
                        'recurrence' => MIDCOM_CRON_MINUTE,
                    )
                )
            ),
            array(
                MIDCOM_CRON_HOUR,
                array(
                    'midcom.services.at' => array(
                        array(
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ),
                        array(
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        )
                    )
                ),
                array()
            ),
            array(
                MIDCOM_CRON_DAY,
                array(
                    'midcom.services.at' => array(
                        array(
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ),
                        array(
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        )
                    )
                ),
                array(
                    array(
                        'component' => 'midcom.services.at',
                        'handler' => 'midcom_services_at_cron_clean',
                        'recurrence' => MIDCOM_CRON_DAY,
                    )
                )
            )
        );
    }
}
