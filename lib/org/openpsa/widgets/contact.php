<?php
/**
 * Class for rendering person records
 *
 * Uses the hCard microformat for output.
 *
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link http://www.microformats.org/wiki/hcard hCard microformat documentation
 * @package org.openpsa.widgets
 */

/**
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_contact extends midcom_baseclasses_components_purecode
{
    /**
     * Do we have our contact data ?
     */
    private $_data_read_ok = false;

    /**
     * Contact information of the person being displayed
     */
    public $contact_details = [
        'guid' => '',
        'id' => '',
        'firstname' => '',
        'lastname' => ''
    ];

    /**
     * Optional URI to person details
     *
     * @var string
     */
    public $link;

    /**
     * Optional HTML to be placed into the card
     *
     * @var string
     */
    public $extra_html;

    /**
     * Optional HTML to be placed into the card (before any other output in the DIV)
     *
     * @var string
     */
    public $prefix_html;

    /**
     * Whether to show person's groups in a list
     *
     * @var boolean
     */
    public $show_groups = true;

    /**
     * Whether to generate links to the groups using NAP
     *
     * @var boolean
     */
    var $link_contacts = true;

    /**
     * Default org.openpsa.contacts URL to be used for linking to groups. Will be autoprobed if not supplied.
     *
     * @var string
     */
    private static $_contacts_url;

    private $person;

    /**
     * Initializes the class and stores the selected person to be shown
     * The argument should be a MidgardPerson object.
     *
     * @param mixed $person Person to display either as MidgardPerson
     */
    public function __construct($person = null)
    {
        parent::__construct();

        if (null === self::$_contacts_url) {
            $siteconfig = org_openpsa_core_siteconfig::get_instance();
            self::$_contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
        }

        $this->person = $person;
        // Read properties of provided person object
        // TODO: Handle groups as well
        $this->_data_read_ok = $this->read_object($person);
    }

    public static function add_head_elements()
    {
        static $added = false;
        if (!$added) {
            midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.widgets/hcard.css");
            midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');
            $added = true;
        }
    }

    /**
     * Retrieve an object, uses in-request caching
     *
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return org_openpsa_widgets_contact
     */
    public static function get($src) : self
    {
        static $cache = [];

        if (isset($cache[$src])) {
            return $cache[$src];
        }

        try {
            $person = org_openpsa_contacts_person_dba::get_cached($src);
        } catch (midcom_error $e) {
            $widget = new self();
            $cache[$src] = $widget;
            return $widget;
        }

        $widget = new self($person);

        $cache[$person->guid] = $widget;
        $cache[$person->id] = $cache[$person->guid];
        return $cache[$person->guid];
    }

    /**
     * Read properties of a person object and populate local fields accordingly
     */
    private function read_object($person) : bool
    {
        if (   !is_object($person)
            || !$person instanceof midcom_db_person) {
            // Given $person is not one
            return false;
        }
        // Database identifiers
        $this->contact_details['guid'] = $person->guid;
        $this->contact_details['id'] = $person->id;

        if ($person->guid == "") {
            $this->contact_details['lastname'] = $this->_l10n->get('no person');
        } elseif ($person->firstname == '' && $person->lastname == '') {
            $this->contact_details['lastname'] = "Person #{$person->id}";
        } else {
            $this->contact_details['firstname'] = $person->firstname;
            $this->contact_details['lastname'] = $person->lastname;
        }

        foreach (['handphone', 'workphone', 'homephone', 'email', 'homepage'] as $property) {
            if ($person->$property) {
                $this->contact_details[$property] = $person->$property;
            }
        }

        if (   $this->_config->get('jabber_enable_presence')
            && $person->get_parameter('org.openpsa.jabber', 'jid')) {
            $this->contact_details['jid'] = $person->get_parameter('org.openpsa.jabber', 'jid');
        }

        if (   $this->_config->get('skype_enable_presence')
            && $person->get_parameter('org.openpsa.skype', 'name')) {
            $this->contact_details['skype'] = $person->get_parameter('org.openpsa.skype', 'name');
        }

        return true;
    }

    private function _render_name($fallback_image) : string
    {
        $name = "<span class=\"given-name\">{$this->contact_details['firstname']}</span> <span class=\"family-name\">{$this->contact_details['lastname']}</span>";

        $url = false;

        if ($this->link) {
            $url = $this->link;
        } elseif ($this->link_contacts && !empty($this->contact_details['guid'])) {
            if (!self::$_contacts_url) {
                $this->link_contacts = false;
            } else {
                $url = self::$_contacts_url . 'person/' . $this->contact_details['guid'] . '/';
            }
        }

        $name = $this->get_image('thumbnail', $fallback_image) . $name;

        if ($url) {
            $name = '<a href="' . $url . '">' . $name . '</a>';
        }

        return $name;
    }

    public function get_image($type, $fallback) : string
    {
        $attachments = org_openpsa_helpers::get_dm2_attachments($this->person, 'photo');
        if (!empty($attachments[$type])) {
            return '<img class="photo" src="' . midcom_db_attachment::get_url($attachments[$type]) . '">';
        }
        if (   $this->_config->get('gravatar_enable')
            && !empty($this->contact_details['email'])) {
            $size = ($type == 'view') ? 500 : 32;
            $gravatar_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->contact_details['email']))) . "?s=" . $size . '&d=identicon';
            return "<img src=\"{$gravatar_url}\" class=\"photo\" />\n";
        }
        return '<i class="fa fa-' . $fallback . '"></i>';
    }

    /**
     * Show selected person object inline. Outputs hCard XHTML.
     */
    public function show_inline() : string
    {
        if (!$this->_data_read_ok) {
            return '';
        }
        self::add_head_elements();
        $inline_string = '';

        // Start the vCard
        $inline_string .= "<span class=\"vcard\">";

        if (!empty($this->contact_details['guid'])) {
            // Identifier
            $inline_string .= "<span class=\"uid\" style=\"display: none;\">{$this->contact_details['guid']}</span>";
        }

        // The name sequence
        $inline_string .= "<span class=\"n\">";
        $inline_string .= $this->_render_name('address-card-o');
        $inline_string .= "</span>";

        $inline_string .= "</span>";

        return $inline_string;
    }

    /**
     * Show the selected person. Outputs hCard XHTML.
     */
    public function show()
    {
        if (!$this->_data_read_ok) {
            return false;
        }
        self::add_head_elements();
        // Start the vCard
        echo "<div class=\"vcard\" id=\"org_openpsa_widgets_contact-{$this->contact_details['guid']}\">\n";
        if ($this->prefix_html) {
            echo $this->prefix_html;
        }

        if (!empty($this->contact_details['guid'])) {
            // Identifier
            echo "<span class=\"uid\" style=\"display: none;\">{$this->contact_details['guid']}</span>";
        }

        // The Name sequence
        echo "<div class=\"n\">\n";
        echo $this->_render_name('user-o');
        echo "</div>\n";

        // Contact information sequence
        echo "<ul class=\"contact_information\">\n";
        if ($this->extra_html) {
            echo $this->extra_html;
        }

        $this->_show_groups();

        $this->_show_phone_number('handphone', 'mobile');
        $this->_show_phone_number('workphone', 'phone');
        $this->_show_phone_number('homephone', 'home');

        if (!empty($this->contact_details['email'])) {
            echo "<li><a title=\"{$this->contact_details['email']}\" href=\"mailto:{$this->contact_details['email']}\"><i class=\"fa fa-envelope-o\"></i>{$this->contact_details['email']}</a></li>\n";
        }

        if (!empty($this->contact_details['skype'])) {
            echo "<li>";
            echo "<a href=\"skype:{$this->contact_details['skype']}?call\"><i class=\"fa fa-skype\"></i>{$this->contact_details['skype']}</a></li>\n";
        }

        // Instant messaging contact information
        if (!empty($this->contact_details['jid'])) {
            echo "<li>";
            echo "<a href=\"xmpp:{$this->contact_details['jid']}\"";
            $edgar_url = $this->_config->get('jabber_edgar_url');
            if (!empty($edgar_url)) {
                echo " style=\"background-repeat: no-repeat;background-image: url('{$edgar_url}?jid={$this->contact_details['jid']}&type=image');\"";
            }
            echo "><i class=\"fa fa-comment-o\"></i>{$this->contact_details['jid']}</a></li>\n";
        }

        if (!empty($this->contact_details['homepage'])) {
            echo "<li><a title=\"{$this->contact_details['homepage']}\" href=\"{$this->contact_details['homepage']}\"><i class=\"fa fa-globe\"></i>{$this->contact_details['homepage']}</a></li>\n";
        }

        echo "</ul>\n";
        echo "</div>\n";
    }

    private function _show_phone_number($field, $type)
    {
        if (!empty($this->contact_details[$field])) {
            echo "<li><a title=\"Dial {$this->contact_details[$field]}\" href=\"tel:{$this->contact_details[$field]}\"><i class=\"fa fa-{$type}\"></i>{$this->contact_details[$field]}</a></li>\n";
        }
    }

    private function _show_groups()
    {
        if (   !$this->show_groups
            || empty($this->contact_details['id'])) {
            return;
        }
        $link_contacts = $this->link_contacts && self::$_contacts_url;

        $mc = org_openpsa_contacts_member_dba::new_collector('uid', $this->contact_details['id']);
        $mc->add_constraint('gid.orgOpenpsaObtype', '>=', org_openpsa_contacts_group_dba::ORGANIZATION);
        $memberships = $mc->get_rows(['gid', 'extra']);

        foreach ($memberships as $data) {
            try {
                $group = org_openpsa_contacts_group_dba::get_cached($data['gid']);
            } catch (midcom_error $e) {
                $e->log();
                continue;
            }

            echo "<li>";

            if ($data['extra']) {
                echo "<span class=\"title\">" . htmlspecialchars($data['extra']) . "</span>, ";
            }

            $group_label = htmlspecialchars($group->get_label());
            if ($link_contacts) {
                $group_label = "<a href=\"" . self::$_contacts_url . "group/{$group->guid}/\"><i class=\"fa fa-users\"></i>" . $group_label . '</a>';
            }

            echo "<span class=\"organization-name\">{$group_label}</span>";
            echo "</li>\n";
        }
    }

    /**
     * Renderer for organization address cards
     */
    public static function show_address_card($customer, $cards)
    {
        $cards_to_show = [];
        $multiple_addresses = false;
        $inherited_cards_only = true;
        $default_shown = false;
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');

        foreach ($cards as $cardname) {
            if ($cardname == 'visiting') {
                if ($customer->street) {
                    $default_shown = true;
                    $cards_to_show[] = $cardname;
                }
                continue;
            }

            $property = $cardname . 'Street';

            if (empty($cards_to_show)) {
                if ($customer->$property) {
                    $inherited_cards_only = false;
                    $cards_to_show[] = $cardname;
                } elseif (!$default_shown && $customer->street) {
                    $default_shown = true;
                    $cards_to_show[] = $cardname;
                }
            } elseif (    $customer->$property
                      || ($customer->street && !$inherited_cards_only && !$default_shown)) {
                $inherited_cards_only = false;
                $multiple_addresses = true;
                $cards_to_show[] = $cardname;
            }
        }

        if (empty($cards_to_show)) {
            return;
        }

        $root_group = org_openpsa_contacts_interface::find_root_group();
        $parent = $customer->get_parent();
        $parent_name = false;
        if ($parent->id != $root_group->id) {
            $parent_name = $parent->get_label();
        }

        foreach ($cards_to_show as $cardname) {
            echo '<div class="vcard">';
            if (   $multiple_addresses
                || (   $cardname != 'visiting'
                    && !$inherited_cards_only)) {
                echo '<div style="text-align:center"><em>' . midcom::get()->i18n->get_string($cardname . ' address', 'org.openpsa.contacts') . "</em></div>\n";
            }
            echo "<strong>\n";
            if ($parent_name) {
                echo '<a href="' . $contacts_url . 'group/' . $parent->guid . '/">' . $parent_name . "</a><br />\n";
            }

            $label = $customer->get_label();

            if ($cardname != 'visiting') {
                $label_property = $cardname . '_label';
                $label = $customer->$label_property;
            }

            echo $label . "\n";
            echo "</strong>\n";

            $property_street = 'street';
            $property_postcode = 'postcode';
            $property_city = 'city';

            if ($cardname != 'visiting') {
                $property_street = $cardname . 'Street';
                $property_postcode = $cardname . 'Postcode';
                $property_city = $cardname . 'City';
            }
            if ($customer->$property_street) {
                echo "<p>{$customer->$property_street}<br />\n";
                echo "{$customer->$property_postcode} {$customer->$property_city}</p>\n";
            } elseif ($customer->street) {
                echo "<p>{$customer->street}<br />\n";
                echo "{$customer->postcode} {$customer->city}</p>\n";
            }
            echo "</div>\n";
        }
    }
}
