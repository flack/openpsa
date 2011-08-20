<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase helper class
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_campaign_helper
{
    private $_testcase;

    private $_campaign;
    private $_member;
    private $_message;

    public function __construct(openpsa_testcase $testcase)
    {
        $this->_testcase = $testcase;
    }

    public function get_campaign()
    {
        if (!$this->_campaign)
        {
            $topic = $this->_testcase->get_component_node('org.openpsa.directmarketing');

            $this->_campaign = $this->_testcase->create_object('org_openpsa_directmarketing_campaign_dba', array('node' => $topic->id));
        }

        return $this->_campaign;
    }

    public function get_member(midcom_db_person $person)
    {
        if (!$this->_member)
        {
            $campaign = $this->get_campaign();
            $parameters = array
            (
                'campaign' => $campaign->id,
                'person' => $person->id
            );
            $this->_member = $this->_testcase->create_object('org_openpsa_directmarketing_campaign_member_dba', $parameters);
        }
        return $this->_member;
    }

    public function get_message()
    {
        if (!$this->_message)
        {
            $campaign = $this->get_campaign();
            $parameters = array
            (
                'campaign' => $campaign->id,
            );
            $this->_member = $this->_testcase->create_object('org_openpsa_directmarketing_campaign_message_dba', $parameters);
        }
        return $this->_member;
    }
}
?>