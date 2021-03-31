<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property integer $person
 * @property integer $campaign
 * @property integer $suspended
 * @property integer $orgOpenpsaObtype
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_member_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_campaign_member';

    public $_use_rcs = false;

    const NORMAL = 9000;
    const TESTER = 9001;
    const UNSUBSCRIBED = 9002;
    const BOUNCED = 9003;
    const INTERVIEWED = 9004;
    const LOCKED = 9005;

    public function __construct($id = null)
    {
        parent::__construct($id);
        if (!$this->orgOpenpsaObtype) {
            $this->orgOpenpsaObtype = self::NORMAL;
        }
    }

    /**
     * Human-readable label for cases like Asgard navigation
     */
    public function get_label() : string
    {
        if ($this->person) {
            $person = new org_openpsa_contacts_person_dba($this->person);
            return $person->name;
        }
        return "member #{$this->id}";
    }

    /**
     * Checks for duplicate memberships returns true for NO duplicate memberships
     */
    public function check_duplicate_membership() : bool
    {
        $qb = new midgard_query_builder('org_openpsa_campaign_member');
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('campaign', '=', $this->campaign);
        //For tester membership check only other tester memberships for duplicates, for other memberships check all BUT testers
        if ($this->orgOpenpsaObtype == self::TESTER) {
            $qb->add_constraint('orgOpenpsaObtype', '=', self::TESTER);
        } else {
            $qb->add_constraint('orgOpenpsaObtype', '<>', self::TESTER);
        }
        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }

        return $qb->count() == 0;
    }

    public function _on_creating()
    {
        return $this->check_duplicate_membership();
    }

    public function _on_updating()
    {
        return $this->check_duplicate_membership();
    }

    /**
     * Substitutes magic strings in content with values from the membership
     * and/or the person.
     */
    public function personalize_message(string $content, int $message_type, org_openpsa_contacts_person_dba $person) : string
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());

        $sep_start = '<';
        $sep_end = '>';

        if ($message_type == org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML) {
            $sep_start = '&lt;';
            $sep_end = '&gt;';
        }

        $account = new midcom_core_account($person);

        $replace_map = [
            $sep_start . 'UNSUBSCRIBE_URL' . $sep_end => $this->get_unsubscribe_url($node),
            $sep_start . 'UNSUBSCRIBE_ALL_URL' . $sep_end => "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe_all/{$person->guid}/",
            $sep_start . 'UNSUBSCRIBE_ALL_FUTURE_URL' . $sep_end => "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe_all_future/{$person->guid}/all.html",
            $sep_start . 'MEMBER_GUID' . $sep_end => $this->guid,
            $sep_start . 'PERSON_GUID' . $sep_end => $person->guid,
            $sep_start . 'EMAIL' . $sep_end => $person->email,
            $sep_start . 'FNAME' . $sep_end => $person->firstname,
            $sep_start . 'LNAME' . $sep_end => $person->lastname,
            $sep_start . 'UNAME' . $sep_end => $account->get_username(),
        ];
        $content = str_replace(array_keys($replace_map), $replace_map, $content);

        if (preg_match_all('/' . $sep_start . 'CALLBACK:(.*?)' . $sep_end . '/', $content, $callback_matches)) {
            foreach ($callback_matches[0] as $k => $search) {
                $callback_func = $callback_matches[1][$k];
                if (is_callable($callback_func)) {
                    $replace = $callback_func($person, $this);
                    $content = str_replace($search, $replace, $content);
                }
            }
        }

        return $content;
    }

    public function get_unsubscribe_url(array $node = null) : string
    {
        if (!$node) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($nap->get_current_node());
        }

        return "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe/{$this->guid}/";
    }

    /**
     * Creates a message receipt of type.
     */
    public function create_receipt(int $message_id, int $type, string $token, array $parameters)
    {
        $receipt = new org_openpsa_directmarketing_campaign_messagereceipt_dba();
        $receipt->orgOpenpsaObtype = $type;
        $receipt->person = $this->person;
        $receipt->message = $message_id;
        $receipt->token = $token;

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        if (!$receipt->create()) {
            debug_add('Failed to create, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return;
        }
        foreach ($parameters as $param_data) {
            if (   empty($param_data['domain'])
                || empty($param_data['name'])
                || empty($param_data['value'])) {
                // TODO: Log warning
                continue;
            }
            $receipt->set_parameter($param_data['domain'], $param_data['name'], $param_data['value']);
        }

        midcom::get()->auth->drop_sudo();
    }
}
