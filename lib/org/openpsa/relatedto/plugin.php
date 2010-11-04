<?php
/**
 * @package org.openpsa.relatedto
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Class for handling "related to" information
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_plugin extends midcom_baseclasses_components_purecode
{

    function __construct()
    {
        $this->_component = 'org.openpsa.relatedto';
        parent::__construct();
    }

    /**
     * Shorthand for creating a relatedto object. 
     * 
     * The <i>from</i> object is something that is related to the <em>to</em> 
     * object.
     * For example, if a task is created under a sales project, that task is 
     * the from object, and the sales project the to object.
     * 
     * @param object &$from_obj The from object
     * @param string $from_component The from component name
     * @param object &$to_obj The to object
     * @param string $to_component The to component name
     * @param int $status The status of the relation
     * @param array $extra Array with the possible extra-properties
     * @return mixed The newly-created relatedto object or false on failure
     */
    static function create(&$from_obj, $from_component, &$to_obj, $to_component, $status = false , $extra = false)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if (   !is_object($from_obj)
            || !is_object($to_obj))
        {
            debug_pop();
            return false;
        }

        $_MIDCOM->componentloader->load('org.openpsa.relatedto');

        if (!$status)
        {
            $status = ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED;
        }

        $rel = new org_openpsa_relatedto_dba();
        $rel->fromClass = get_class($from_obj);
        $rel->toClass = get_class($to_obj);
        $rel->fromGuid = $from_obj->guid;
        $rel->toGuid = $to_obj->guid;
        $rel->fromComponent = $from_component;
        $rel->toComponent = $to_component;
        $rel->status = $status;
        
        if ($id = $rel->check_db())
        {
            $rel = new org_openpsa_relatedto_dba($id);
            debug_add("A relation from {$rel->fromClass} #{$rel->fromGuid} to {$rel->toClass} #{$rel->toGuid} already exists, returning this one instead");
            debug_pop();
            return $rel;           
        }
        
        if (!empty($extra))
        {
            foreach ($extra as $extra_key => $extra_value)
            {
                $rel->$extra_key = $extra_value;
            }
        }

        $stat = $rel->create();
        if (!$stat)
        {
            debug_add("failed to create link from {$rel->fromClass} #{$rel->fromGuid} to {$rel->toClass} #{$from_object->guid}, errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            debug_pop();
            return $stat;
        }
        debug_pop();
        return $rel;
    }

    /**
     * Parses relatedto information from request, returning either
     * existing matching relatedtos or prefilled new ones for creation
     */
    static function get2relatedto()
    {
        $ret = array();
        if (!array_key_exists('org_openpsa_relatedto', $_REQUEST))
        {
            return $ret;
        }
        foreach($_REQUEST['org_openpsa_relatedto'] as $rel_array)
        {
            $rel = new org_openpsa_relatedto_dba();
            foreach($rel_array as $k => $v)
            {
                $rel->$k = $v;
            }
            if ($id = $rel->check_db())
            {
                $rel = new org_openpsa_relatedto_dba($id);
            }
            $ret[]  = $rel;
        }
        return $ret;
    }

    /**
     * Used to convert our GET parameters into session data
     *
     * For use with DM2 or any other form that loses the GET parameters
     * when POSTing
     */
    function get2session()
    {
        $arr = self::get2relatedto();
        if (count($arr) > 0)
        {
            $session = new midcom_service_session('org.openpsa.relatedto');
            $session->set('relatedto2get_array', $arr);
        }
    }

    /**
     * Clean up after get2session() (in case we cancel or something)
     *
     * To be used in case we do not get to call on_created_handle_relatedto()
     * or some other method that reads and saves the data (and while at it cleans
     * up after itself)
     */
    function get2session_cleanup()
    {
        $session = new midcom_service_session('org.openpsa.relatedto');
        if ($session->exists('relatedto2get_array'))
        {
            $session->remove('relatedto2get_array');
        }
    }

    /**
     * serializes an array or relatedto objects into GET parameters
     *
     * NOTE: does not prefix the ? for the first parameter in case this needs
     * to be used with some other GET parameters.
     */
    static function relatedto2get($array)
    {
        $ret = '';
        if (!is_array($array))
        {
            return false;
        }
        $i = 0;
        foreach ($array as $rel)
        {
            if (!$_MIDCOM->dbfactory->is_a($rel, 'org_openpsa_relatedto_dba')) //Matches also 'org_openpsa_relatedto'
            {
                //Wrong type of object found in array, cruelly abort the whole procedure
                return false;
            }
            if ($i > 0)
            {
                $ret .= '&amp;';
            }
            //These should be always specified
            $ret .= rawurlencode("org_openpsa_relatedto[{$i}][toGuid]") . '=' . rawurlencode("{$rel->toGuid}");
            $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][toComponent]") . '=' . rawurlencode("{$rel->toComponent}");
            $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][toClass]") . '=' . rawurlencode("{$rel->toClass}");
            //To save GET space we only append these if they have values
            if ($rel->status)
            {
                $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][status]") . '=' . rawurlencode("{$rel->status}");
            }
            if ($rel->fromComponent)
            {
                $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][fromComponent]") . '=' . rawurlencode("{$rel->fromComponent}");
            }
            if ($rel->fromClass)
            {
                $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][fromClass]") . '=' . rawurlencode("{$rel->fromClass}");
            }
            if ($rel->fromGuid)
            {
                $ret .= '&amp;' . rawurlencode("org_openpsa_relatedto[{$i}][fromGuid]") . '=' . rawurlencode("{$rel->fromGuid}");
            }

            $i++;
        }
        return $ret;
    }

    static function get_plugin_handlers()
    {
        $switch = array();

        // Match render/<objguid>/<mode>/<sort>
        $switch['render_sort'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_relatedto', 'render'),
            'fixed_args' => array('render'),
            'variable_args' => 3,
        );

        // Match render/<objguid>/<mode>
        $switch['render'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_relatedto', 'render'),
            'fixed_args' => array('render'),
            'variable_args' => 2,
        );

        // Match delete/<guid>
        $switch['delete'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_relatedto', 'delete'),
            'fixed_args' => array('delete'),
            'variable_args' => 1,
        );

        // Match ajax/<mode>/<objguid>
        $switch['ajax_object'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_relatedto', 'ajax'),
            'fixed_args' => array('ajax'),
            'variable_args' => 2,
        );

        // Match ajax/<mode>
        $switch['ajax'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_relatedto', 'ajax'),
            'fixed_args' => array('ajax'),
            'variable_args' => 1,
        );
        // Match journalentry/list/<mode>
        $switch['journal_entry_list'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_journalentry', 'list'),
            'fixed_args' => array('journalentry' , 'list'),
            'variable_args' => 1,
        );
        // Match journalentry/create/<guid>
        $switch['journal_entry_create'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_journalentry', 'create'),
            'fixed_args' => array('journalentry' , 'create'),
            'variable_args' => 1,
        );
        // Match journalentry/edit/<guid>/
        $switch['journal_entry_edit'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_journalentry', 'edit'),
            'fixed_args' => array('journalentry' , 'edit'),
            'variable_args' => 1,
        );
        // Match journalentry/delete/<guid>/
        $switch['journal_entry_delete'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_journalentry', 'delete'),
            'fixed_args' => array('journalentry' , 'delete'),
            'variable_args' => 1,
        );
        // Match journalentry/<guid>/<mode>
        $switch['journal_entry'] = array
        (
            'handler' => array('org_openpsa_relatedto_handler_journalentry', 'entry'),
            'fixed_args' => array('journalentry'),
            'variable_args' => 2,
        );

        return $switch;
    }


    /**
     * Helper function that adds the necessary JS/CSS to HTML head
     */
    static function add_header_files()
    {
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/ajaxutils.js");
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.relatedto/related_to.js");

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.relatedto/related_to.css",
            )
        );
    }

    static function add_button(&$toolbar, $guid, $mode = 'both')
    {
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/org.openpsa.relatedto/render/{$guid}/{$mode}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('view related information', 'org.openpsa.relatedto'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
    }

    static function common_node_toolbar_buttons_sanitycheck(&$data, &$button_component, &$bind_object, &$calling_component)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$_MIDCOM->componentloader->load_graceful($button_component))
        {
            //For some reason the component is and can not (be) loaded
            debug_add("component {$button_component} could not be loaded", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if (   !array_key_exists('node', $data)
            || empty($data['node']))
        {
            debug_add("data['node'] not given, trying with midcom_helper_find_node_by_component({$button_component})", MIDCOM_LOG_INFO);
            $data['node'] = midcom_helper_find_node_by_component($button_component);
        }
        if (empty($data['node']))
        {
            //Invalid node given/found
            debug_add("data['node'] is invalid", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $related_to = new org_openpsa_relatedto_dba();
        $related_to->toGuid = $bind_object->guid;
        $related_to->toClass = get_class($bind_object);
        $related_to->toComponent = $calling_component;
        $related_to->fromComponent = $button_component;
        $related_to->status = ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED;

        debug_pop();
        return $related_to;
    }

    static function common_toolbar_buttons_defaults()
    {
        $buttons = array
        (
            'event'  => array
            (
                'node'  => false,
            ),
            'task'  => array
            (
                'node'  => false,
            ),
            'wikinote'      => array
            (
                'node'  => false,
                'wikiword'  => false, //Calling component MUST define this key to get a wikinote button
            ),
            'document'      => array
            (
                'node'  => false,
            ),
        );
        return $buttons;
    }

    static function common_node_toolbar_buttons(&$toolbar, &$bind_object, $calling_component, $buttons = 'default')
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        self::add_header_files();
        if ($buttons == 'default')
        {
            $buttons = self::common_toolbar_buttons_defaults();
        }
        if (!is_array($buttons))
        {
            //Invalid buttons given
            return;
        }

        foreach ($buttons as $mode => $data)
        {
            debug_print_r("processing button '{$mode}' with data:", $data);
            if ($data === false)
            {
                //In case somebody didn't unset() a button from the defaults, just marked it as false
                debug_add('data marked as false, skipping (the correct way is to unset() the key)',  MIDCOM_LOG_WARN);
                continue;
            }
            //Remember that switch is also a for statement in PHPs mind, use "continue 2"
            switch ($mode)
            {
                case 'event':
                    $button_component = 'org.openpsa.calendar';
                    $related_to = self::common_node_toolbar_buttons_sanitycheck($data, $button_component, $bind_object, $calling_component);
                    if (   !is_object($related_to)
                        || !$_MIDCOM->dbfactory->is_a($related_to, 'org_openpsa_relatedto_dba'))
                    {
                        debug_add("sanitycheck returned '{$related_to}' (relatedto object expected), skipping", MIDCOM_LOG_WARN);
                        continue 2;
                    }
                    $toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "#",
                            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create event', $button_component),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                            //TODO: Check for privileges somehow
                            MIDCOM_TOOLBAR_ENABLED => true,
                            MIDCOM_TOOLBAR_OPTIONS  => array
                            (
                                'rel' => 'directlink',
                                'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($data['node'], false, $related_to->toGuid, '?' . self::relatedto2get(array($related_to))),
                            ),
                        )
                    );
                    break;
                case 'task':
                    $button_component = 'org.openpsa.projects';
                    $related_to = self::common_node_toolbar_buttons_sanitycheck($data, $button_component, $bind_object, $calling_component);
                    if (   !is_object($related_to)
                        || !$_MIDCOM->dbfactory->is_a($related_to, 'org_openpsa_relatedto_dba'))
                    {
                        debug_add("sanitycheck returned '{$related_to}' (relatedto object expected), skipping", MIDCOM_LOG_WARN);
                        continue 2;
                    }
                    $toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$data['node'][MIDCOM_NAV_FULLURL]}task/new/?" . self::relatedto2get(array($related_to)),
                            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create task', $button_component),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_task_dba'),
                            MIDCOM_TOOLBAR_OPTIONS  => array
                            (
                                //PONDER: Open in new window or not??
                                'target' => 'newtask',
                            ),
                        )
                    );
                    break;
                case 'wikinote':
                    $button_component = 'net.nemein.wiki';
                    if (   !array_key_exists('wikiword', $data)
                        || empty($data['wikiword']))
                    {
                        //Wikiword to use not given
                        debug_add("data['wikiword'] not given, skipping", MIDCOM_LOG_WARN);
                        continue 2;
                    }
                    $related_to = self::common_node_toolbar_buttons_sanitycheck($data, $button_component, $bind_object, $calling_component);
                    if (   !is_object($related_to)
                        || !$_MIDCOM->dbfactory->is_a($related_to, 'org_openpsa_relatedto_dba'))
                    {
                        debug_add("sanitycheck returned '{$related_to}' (relatedto object expected), skipping", MIDCOM_LOG_WARN);
                        continue 2;
                    }

                    if (!net_nemein_wiki_interface::node_wikiword_is_free($data['node'], $data['wikiword']))
                    {
                        //Wikiword is already reserved
                        //PONDER: append number or something and check again ??
                        debug_add("node_wikiword_is_free returned false for '{$data['wikiword']}'", MIDCOM_LOG_WARN);
                        continue 2;
                    }

                    //$data['wikiword_encoded'] = rawurlencode(str_replace('/', '-', $data['wikiword']));
                    $data['wikiword_encoded'] = rawurlencode($data['wikiword']);
                    $toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$data['node'][MIDCOM_NAV_FULLURL]}create/?wikiword={$data['wikiword_encoded']}&amp;" . self::relatedto2get(array($related_to)),
                            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create note', $button_component),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            //TODO: Different icon from new document ?
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                            //TODO: Check privileges somehow ($_MIDCOM->auth->can_do('midgard:create', $data['node'][MIDCOM_NAV_OBJECT] ?)
                            MIDCOM_TOOLBAR_ENABLED => $data['node'][MIDCOM_NAV_OBJECT]->can_do('midgard:create'),
                            MIDCOM_TOOLBAR_OPTIONS  => array
                            (
                                //PONDER: Open in new window or not ??
                                'target' => 'wiki',
                            ),
                        )
                    );
                    break;
                case 'document':
                    $button_component = 'org.openpsa.documents';
                    $related_to = self::common_node_toolbar_buttons_sanitycheck($data, $button_component, $bind_object, $calling_component);
                    if (   !is_object($related_to)
                        || !$_MIDCOM->dbfactory->is_a($related_to, 'org_openpsa_relatedto_dba'))
                    {
                        debug_add("sanitycheck returned '{$related_to}' (relatedto object expected), skipping", MIDCOM_LOG_WARN);
                        continue 2;
                    }
                    $toolbar->add_item
                    (
                        array
                        (
                            MIDCOM_TOOLBAR_URL => "{$data['node'][MIDCOM_NAV_FULLURL]}document/create/choosefolder/?" . self::relatedto2get(array($related_to)),
                            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create document', $button_component),
                            MIDCOM_TOOLBAR_HELPTEXT => null,
                            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                            MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:create', $data['node'][MIDCOM_NAV_OBJECT]),
                            MIDCOM_TOOLBAR_OPTIONS  => array
                            (
                                //PONDER: Open in new window or not ??
                                'target' => 'newdocument',
                            ),
                        )
                    );
                    break;
                default:
                    debug_add("given button '{$mode}' not recognized", MIDCOM_LOG_ERROR);
                    break;
            }
        }
    }
    /**
     * function to add the button for journal_entry to the toolbar
     */
    static function add_journal_entry_button(&$toolbar, $guid, $mode = 'both')
    {
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/org.openpsa.relatedto/journalentry/{$guid}/html/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('view journal entries', 'org.openpsa.relatedto'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );
    }
}

?>