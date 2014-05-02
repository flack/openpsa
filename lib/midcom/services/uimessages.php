<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * User interface messaging service
 *
 * This service is used for passing messages from applications to the MidCOM
 * user.
 *
 * <b>Displaying UI messages on site:</b>
 *
 * If you want the UI messages to be shown in your site, you must place
 * the following call inside the HTML BODY tags of your style:
 *
 * <code>
 * midcom::get('uimessages')->show();
 * </code>
 *
 * <b>Adding UI messages to show:</b>
 *
 * Any MidCOM component can add its own UI messages to be displayed. The
 * messages also carry across a relocate() call so you can tell a document
 * has been saved before relocating user into its view.
 *
 * UI messages can be specified into the following types: <i>info</i>,
 * <i>ok</i>, <i>warning</i> and <i>error</i>.
 *
 * To add a UI message, call the following:
 *
 * <code>
 * midcom::get('uimessages')->add($title, $message, $type);
 * </code>
 *
 * For example:
 *
 * <code>
 * midcom::get('uimessages')->add($this->_l10n->get('net.nemein.wiki'), sprintf($this->_l10n->get('page "%s" added'), $this->_wikiword), 'ok');
 * </code>
 *
 * <b>Configuration:</b>
 *
 * See midcom_config.php for configuration options.
 *
 * @package midcom.services
 */
class midcom_services_uimessages
{
    /**
     * The current message stack
     *
     * @var Array
     */
    private $_message_stack = array();

    /**
     * List of allowed message types
     *
     * @var Array
     */
    private $_allowed_types = array('info', 'ok', 'warning', 'error', 'debug');

    /**
     * List of messages retrieved from session to avoid storing them again
     *
     * @var Array
     */
    private $_messages_from_session = array();

    /**
     * ID of the latest UI message added so we can auto-increment
     *
     * @var integer
     */
    private $_latest_message_id = 0;

    /**
     * DOM path of the UI message holder object
     *
     * @var String
     */
    public $uimessage_holder = 'body';

    /**
     * Initialize the message stack on service start-up. Reads older unshown
     * messages from user session.
     */
    function initialize()
    {
        if (midcom::get('auth')->can_user_do('midcom:ajax', null, 'midcom_services_uimessages'))
        {
            midcom::get('head')->enable_jquery();
            midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.uimessages/jquery.midcom_services_uimessages.js');
            midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.timers.src.js');
            midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.effect.min.js');
            midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.effect-pulsate.min.js');

            midcom::get('head')->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.services.uimessages/growl.css', 'screen');
        }
        else
        {
            midcom::get('head')->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.services.uimessages/simple.css', 'screen');
        }

        // Read messages from session
        $session = new midcom_services_session('midcom_services_uimessages');
        if ($session->exists('midcom_services_uimessages_stack'))
        {
            // We've got old messages in the session
            $stored_messages = $session->get('midcom_services_uimessages_stack');
            $session->remove('midcom_services_uimessages_stack');
            if (!is_array($stored_messages))
            {
                return false;
            }

            foreach ($stored_messages as $message)
            {
                $id = $this->add($message['title'], $message['message'], $message['type']);
                $this->_messages_from_session[$id] = true;
            }
        }
    }

    function get_class_magic_default_privileges()
    {
        return array
        (
            'EVERYONE' => array(),
            'ANONYMOUS' => array(),
            'USERS' => array()
        );
    }

    /**
     * Store unshown UI messages from the stack to user session.
     */
    function store()
    {
        if (count($this->_message_stack) == 0)
        {
            // No unshown messages
            return true;
        }

        // We have to be careful what messages to store to session to prevent them
        // from accumulating
        $messages_to_store = array_diff_key($this->_message_stack, $this->_messages_from_session);

        if (count($messages_to_store) == 0)
        {
            // We have only messages coming from earlier sessions, and we ditch those
            return true;
        }

        $session = new midcom_services_session('midcom_services_uimessages');

        // Check if some other request has added stuff to session as well
        if ($session->exists('midcom_services_uimessages_stack'))
        {
            $old_stack = $session->get('midcom_services_uimessages_stack');
            $messages_to_store = array_merge($old_stack, $messages_to_store);
        }
        $session->set('midcom_services_uimessages_stack', $messages_to_store);
        $this->_message_stack = array();
    }

    /**
     * Add a message to be shown to the user.
     *
     * @param string $title Message title
     * @param string $message Message contents, may contain HTML
     * @param string $type Type of the message
     */
    function add($title, $message, $type = 'info')
    {
        // Make sure the given class is allowed
        if (!in_array($type, $this->_allowed_types))
        {
            // Message class not in allowed list
            debug_add("Message type {$type} is not allowed");
            return false;
        }

        // Properly escape the title and message contents
        $title = str_replace("'", '\\\'', $title);
        $message = str_replace("'", '\\\'', $message);

        $this->_latest_message_id++;

        // Append to message stack
        $this->_message_stack[$this->_latest_message_id] = array
        (
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
        );
        return $this->_latest_message_id;
    }

    /**
     * Show the message stack via javascript calls or simple html
     *
     * @param boolean $show_simple Show simple HTML
     */
    function show($show_simple = false)
    {
        if (   $show_simple
            || !midcom::get('auth')->can_user_do('midcom:ajax', null, 'midcom_services_uimessages'))
        {
            $this->show_simple();
            return;
        }

        echo "<script type=\"text/javascript\">\n";
        echo "    // <!--\n";
        echo "        jQuery(document).ready(function()\n";
        echo "        {\n";
        echo "            if (jQuery('#midcom_services_uimessages_wrapper').length == 0)\n";
        echo "            {\n";
        echo "                jQuery('<div id=\"midcom_services_uimessages_wrapper\"></div>')\n";
        echo "                    .appendTo('{$this->uimessage_holder}');\n";
        echo "            }\n";

        while ($message = array_shift($this->_message_stack))
        {
            echo "            jQuery('#midcom_services_uimessages_wrapper').midcom_services_uimessage(" . json_encode($message) . ")\n";
        }

        echo "        })\n";
        echo "    // -->\n";

        echo "</script>\n";
    }

    /**
     * Show the message stack via simple html only
     */
    function show_simple($prefer_fancy = false)
    {
        if (   $prefer_fancy
            && midcom::get('auth')->can_user_do('midcom:ajax', null, 'midcom_services_uimessages'))
        {
            return $this->show();
        }

        if (count($this->_message_stack) > 0)
        {
            echo "<div id=\"midcom_services_uimessages_wrapper\">\n";

            while ($message = array_shift($this->_message_stack))
            {
                $this->_render_message($message);
            }

            echo "</div>\n";
        }
    }

    /**
     * Render the message
     */
    function _render_message($message)
    {
        echo "<div class=\"midcom_services_uimessages_message msu_{$message['type']}\">";

        echo "    <div class=\"midcom_services_uimessages_message_type\">{$message['type']}</div>";
        echo "    <div class=\"midcom_services_uimessages_message_title\">{$message['title']}</div>";
        echo "    <div class=\"midcom_services_uimessages_message_msg\">{$message['message']}</div>";

        echo "</div>\n";
    }
}
?>