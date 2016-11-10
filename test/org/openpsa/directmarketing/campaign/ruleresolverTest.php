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
class org_openpsa_directmarketing_campaign_ruleresolverTest extends openpsa_testcase
{
    /**
     * @dataProvider provider_resolve
     */
    public function test_resolve($rules, $result)
    {
        $resolver = new org_openpsa_directmarketing_campaign_ruleresolver;
        $this->assertSame($result, $resolver->resolve($rules));
    }

    public function provider_resolve()
    {
        return array(
            0 => array(
                array(),
                false
            ),
            1 => array(
                array('classes' => array()),
                false
            ),
            2 => array(
                array('classes' => array(), 'type' => 'AND'),
                true
            ),
            3 => array(
                array(
                    'classes' => array(
                        array(
                            'type' => 'AND',
                            'class' => 'org_openpsa_contacts_person_dba',
                            'rules' => array(
                                array(
                                    'property' => 'email',
                                    'match' => 'LIKE',
                                    'value' => '%fl%'
                                )
                            )
                        ),
                    ),
                    'type' => 'AND'),
                true
            ),
            4 => array(
                array(
                    'classes' => array(
                        array(
                            'type' => 'AND',
                            'class' => 'org_openpsa_directmarketing_link_log_dba',
                            'rules' => array(
                                array(
                                    'property' => 'target',
                                    'match' => '=',
                                    'value' => 'http://openpsa2.org',
                                ),
                                array(
                                    'property' => 'message',
                                    'match' => '=',
                                    'value' => 25,
                                )
                            )
                        ),
                        array(
                            'comment' => 'Not-unsubscribed -limits',
                            'type' => 'AND',
                            'class' => 'org_openpsa_directmarketing_campaign_member_dba',
                            'rules' =>
                            array(
                                array(
                                    'property' => 'orgOpenpsaObtype',
                                    'match' => '<>',
                                    'value' => 9002,
                                ),
                                array(
                                    'property' => 'campaign',
                                    'match' => '=',
                                    'value' => 5,
                                ),
                            ),
                        ),
                    ),
                    'type' => 'AND'),
                true
            ),
            5 => array(
                array(
                    'classes' => array(
                        array(
                            'type' => 'AND',
                            'class' => 'org_openpsa_contacts_group_dba',
                            'rules' =>
                            array(
                                array(
                                    'property' => 'official',
                                    'match' => 'LIKE',
                                    'value' => '%test%',
                                ),
                            ),
                        )
                    ),
                    'type' => 'AND'),
                true
            ),
        );
    }
}
