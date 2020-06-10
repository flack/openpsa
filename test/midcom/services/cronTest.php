<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_cronTest extends TestCase
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
        return [
            [
                MIDCOM_CRON_MINUTE,
                [],
                []
            ],
            [
                MIDCOM_CRON_MINUTE,
                [
                    'midcom.services.at' => [
                        [
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ],
                        [
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        ]
                    ]
                ],
                [
                    [
                        'component' => 'midcom.services.at',
                        'handler' => 'midcom_services_at_cron_check',
                        'recurrence' => MIDCOM_CRON_MINUTE,
                    ]
                ]
            ],
            [
                MIDCOM_CRON_HOUR,
                [
                    'midcom.services.at' => [
                        [
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ],
                        [
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        ]
                    ]
                ],
                []
            ],
            [
                MIDCOM_CRON_DAY,
                [
                    'midcom.services.at' => [
                        [
                            'handler' => 'midcom_services_at_cron_check',
                            'recurrence' => MIDCOM_CRON_MINUTE,
                        ],
                        [
                            'handler' => 'midcom_services_at_cron_clean',
                            'recurrence' => MIDCOM_CRON_DAY,
                        ]
                    ]
                ],
                [
                    [
                        'component' => 'midcom.services.at',
                        'handler' => 'midcom_services_at_cron_clean',
                        'recurrence' => MIDCOM_CRON_DAY,
                    ]
                ]
            ]
        ];
    }
}
