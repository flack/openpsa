<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * On-site toolbar service.
 *
 * This service manages the toolbars used for the on-site administration system.
 * For each context, it provides the following set of toolbars:
 *
 * 1. The <i>Node</i> toolbar is applicable to the current
 *    node, which is usually a topic. MidCOM places the topic management operations
 *    into this toolbar, where applicable.
 *
 * 2. The <i>View</i> toolbar is applicable to the specific view ("url"). Usually
 *    this maps to a single displayed object (see also the bind_to_object() member
 *    function). MidCOM places the object-specific management operations (like
 *    Metadata controls) into this toolbar, if it is bound to an object. Otherwise,
 *    this toolbar is not touched by MidCOM.
 *
 * It is important to understand, that these default toolbars made available through this
 * service are completely specific to a given request context. If you have a dynamic_load
 * running on a given site, it will have its own set of toolbars for each instance.
 *
 * In addition, components my retrieve a third kind of toolbars, which are not under
 * the general control of MidCOM, the <i>Object</i> toolbars. They apply to a single
 * database object (like a bound <i>View</i> toolbar). The usage of this kind of
 * toolbars is completely component-specific: It is recommended to use them only for
 * cases where multiple objects are displayed simultaneously. For example, the
 * index page of a Newsticker or Image Gallery might provide them.
 *
 *
 *
 * <b>Implementation notes</b>
 *
 * It has yet to prove if the toolbar system is yet needed for a dynamic_load environments.
 * The main reason for this is that dl'ed stuff is often quite tight in space and thus cannot
 * display any toolbars in a sane way. Usually, the administrative tasks will be bound to the
 * main request.
 *
 * This could be different for portal applications, which display several components on the
 * welcome page, each with its own management options.
 *
 * <b>Configuration</b>
 * See midcom_config.php for configuration options.
 *
 * @package midcom.services
 */
class midcom_services_toolbars
{
    /**
     * The toolbars currently available.
     *
     * This array is indexed by context id; each value consists of a flat array
     * of two toolbars, the first object being the Node toolbar, the second
     * View toolbar. The toolbars are created on-demand.
     *
     * @var array
     */
    private $_toolbars = array();

    /**
     * midcom.services.toolbars has two modes, it can either display one centralized toolbar
     * for authenticated users, or the node and view toolbars separately for others. This
     * property controls whether centralized mode is enabled.
     *
     * @var boolean
     */
    private $_enable_centralized = false;

    /**
     * Whether we're in centralized mode, i.e. centralized toolbar has been shown
     *
     * @var boolean
     */
    private $_centralized_mode = false;

    /**
     * Label for the "Page" toolbar
     *
     * @var string
     */
    private $_view_toolbar_label = '';

    /**
     * Simple constructor, calls base class.
     */
    public function __construct()
    {
        // Default label for the "Page" toolbar
        $this->_view_toolbar_label = $_MIDCOM->i18n->get_string('page', 'midcom');

        // FIXME: initialize() should be called by the callee, not here in constructor
        $this->initialize();
    }

    /**
     * Initialize centralized toolbar if required
     */
    function initialize()
    {
        static $still_initializing = null;
        if (is_null($still_initializing))
        {
            $still_initializing = true;
        }
        else if ($still_initializing)
        {
            // This is auth service looping because it instantiates classes for magick privileges!
            return;
        }

        if (!$_MIDCOM->auth->user)
        {
            // Centralized toolbar is only for registered users
            $still_initializing = false;
            return;
        }

        if (   !$GLOBALS['midcom_config']['toolbars_enable_centralized']
            || !$_MIDCOM->auth->can_user_do('midcom:centralized_toolbar', null, $this))
        {
            $still_initializing = false;
            return;
        }

        if ($_MIDCOM->auth->can_user_do('midcom:ajax', null, $this))
        {
            $_MIDCOM->enable_jquery();
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.timers.src.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
            $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');

            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.toolbars/jquery.midcom_services_toolbars.js');


            $_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.services.toolbars/fancy.css', 'screen');

            $this->type = $GLOBALS['midcom_config']['toolbars_type'];

            if ($this->type == 'menu')
            {
                $script = "jQuery('body div.midcom_services_toolbars_fancy').midcom_services_toolbar({type: 'menu'});";
            }
            else
            {
                $script = "jQuery('body div.midcom_services_toolbars_fancy').midcom_services_toolbar({});";
            }


            $_MIDCOM->add_jquery_state_script($script);
        }
        else
        {
            $_MIDCOM->add_stylesheet($GLOBALS['midcom_config']['toolbars_simple_css_path'], 'screen');

            $this->type = 'normal';
        }

        $still_initializing = false;
        // We've included CSS and JS, path is clear for centralized mode
        $this->_enable_centralized = true;
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
     * Returns a reference to the host toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     */
    function get_host_toolbar ($context_id = null)
    {
        if ($context_id === null)
        {
            $context_id = $_MIDCOM->get_current_context();
        }

        if (! array_key_exists($context_id, $this->_toolbars))
        {
            $this->_create_toolbars($context_id);
        }

        return $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HOST];
    }

    /**
     * Returns a reference to the node toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     */
    function get_node_toolbar ($context_id = null)
    {
        if ($context_id === null)
        {
            $context_id = $_MIDCOM->get_current_context();
        }

        if (! array_key_exists($context_id, $this->_toolbars))
        {
            $this->_create_toolbars($context_id);
        }

        return $this->_toolbars[$context_id][MIDCOM_TOOLBAR_NODE];
    }

    /**
     * Returns a reference to the view toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the view toolbar for, this
     *     defaults to the current context.
     */
    function get_view_toolbar ($context_id = null)
    {
        if ($context_id === null)
        {
            $context_id = $_MIDCOM->get_current_context();
        }

        if (! array_key_exists($context_id, $this->_toolbars))
        {
            $this->_create_toolbars($context_id);
        }

        return $this->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW];
    }

    /**
     * Returns a reference to the help toolbar of the specified context. The toolbars
     * will be created if this is the first request.
     *
     * @param int $context_id The context to retrieve the help toolbar for, this
     *     defaults to the current context.
     */
    function get_help_toolbar ($context_id = null)
    {
        if ($context_id === null)
        {
            $context_id = $_MIDCOM->get_current_context();
        }

        if (! array_key_exists($context_id, $this->_toolbars))
        {
            $this->_create_toolbars($context_id);
        }

        return $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HELP];
    }

    /**
     * Creates the node and view toolbars for a given context ID.
     *
     * @param int $context_id The context ID for whicht the toolbars should be created.
     */
    function _create_toolbars ($context_id)
    {
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_NODE] =
            new midcom_helper_toolbar
            (
                $GLOBALS['midcom_config']['toolbars_node_style_class'],
                $GLOBALS['midcom_config']['toolbars_node_style_id']
            );
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW] =
            new midcom_helper_toolbar
            (
                $GLOBALS['midcom_config']['toolbars_view_style_class'],
                $GLOBALS['midcom_config']['toolbars_view_style_id']
            );

        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HOST] =
            new midcom_helper_toolbar
            (
                $GLOBALS['midcom_config']['toolbars_host_style_class'],
                $GLOBALS['midcom_config']['toolbars_host_style_id']
            );
        $this->_toolbars[$context_id][MIDCOM_TOOLBAR_HELP] =
            new midcom_helper_toolbar
            (
                $GLOBALS['midcom_config']['toolbars_help_style_class'],
                $GLOBALS['midcom_config']['toolbars_help_style_id']
            );
        $this->add_topic_management_commands($this->_toolbars[$context_id][MIDCOM_TOOLBAR_NODE], $context_id);
        $this->add_host_management_commands($this->_toolbars[$context_id][MIDCOM_TOOLBAR_HOST], $context_id);
        $this->add_help_management_commands($this->_toolbars[$context_id][MIDCOM_TOOLBAR_HELP], $context_id);
    }

    /**
     * Adds the topic management commands to the specified toolbar.
     *
     * Repeated calls to the same toolbar are intercepted accordingly.
     *
     * @param midcom_helper_toolbar &$toolbar A reference to the toolbar to use.
     * @param int $context_id The context to use (the topic is drawn from there). This defaults
     *     to the currently active context.
     */
    function add_topic_management_commands(&$toolbar, $context_id = null)
    {
        if (array_key_exists('midcom_services_toolbars_bound_to_topic', $toolbar->customdata))
        {
            // We already processed this toolbar, skipping further adds.
            return;
        }
        else
        {
            $toolbar->customdata['midcom_services_toolbars_bound_to_topic'] = true;
        }

        if ($context_id === null)
        {
            $topic = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC);
        }
        else
        {
            $topic = $_MIDCOM->get_context_data($context_id, MIDCOM_CONTEXT_CONTENTTOPIC);
        }

        // Bullet-proof
        if (   !$topic
            || !$topic->guid)
        {
            return false;
        }

        $urltopic = end($_MIDCOM->get_context_data(MIDCOM_CONTEXT_URLTOPICS));
        if (!$urltopic)
        {
            $urltopic = $topic;
        }

        if (   $topic->can_do('midgard:update')
            && $topic->can_do('midcom.admin.folder:topic_management'))
        {
            $toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/edit/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit folder', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'g',
                )
            );

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/metadata/{$urltopic->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit folder metadata', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/metadata.png',
                )
            );
        }

        if (   $urltopic->can_do('midgard:update')
            && $urltopic->can_do('midcom.admin.folder:topic_management'))
        {
            // Allow to move other than root folder
            if ($urltopic->guid !== $GLOBALS['midcom_config']['midcom_root_topic_guid'])
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/move/{$urltopic->guid}/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('move', 'midcom.admin.folder'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
                    )
                );
            }
        }

        if (   $topic->can_do('midgard:update')
            && $topic->can_do('midcom.admin.folder:topic_management'))
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/order/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('order navigation', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/topic-score.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'o',
                )
            );

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/object/open/{$topic->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('manage object', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin', 'midgard.admin.asgard') && $_MIDCOM->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'),
                )
            );
        }

        if (   $GLOBALS['midcom_config']['metadata_approval']
            && $topic->can_do('midcom:approve'))
        {
            $metadata = midcom_helper_metadata::retrieve($topic);
            if ($metadata->is_approved())
            {
                $icon = 'stock-icons/16x16/page-approved.png';
                if (   !$GLOBALS['midcom_config']['show_hidden_objects']
                    && !$metadata->is_visible())
                {
                    // Take scheduling into account
                    $icon = 'stock-icons/16x16/page-approved-notpublished.png';
                }
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/unapprove/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('unapprove topic', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => $_MIDCOM->i18n->get_string('approved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => $icon,
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $topic->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                    )
                );
            }
            else
            {
                $icon = 'stock-icons/16x16/page-notapproved.png';
                if (   !$GLOBALS['midcom_config']['show_hidden_objects']
                    && !$metadata->is_visible())
                {
                    // Take scheduling into account
                    $icon = 'stock-icons/16x16/page-notapproved-notpublished.png';
                }
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/approve/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('approve topic', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => $_MIDCOM->i18n->get_string('unapproved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => $icon,
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $topic->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                    )
                );
            }
        }

        if (   $topic->can_do('midcom.admin.folder:template_management')
            && $_MIDCOM->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'))
        {
            $enabled = false;
            $styleeditor_url = '';
            if ($topic->style != '')
            {
                $style_id = $_MIDCOM->style->get_style_id_from_path($topic->style);
                if ($style_id)
                {
                    $style = midcom_db_style::get_cached($style_id);
                    if (   $style
                        && $style->guid)
                    {
                        $styleeditor_url = midcom_connection::get_url('self') . "__mfa/asgard/object/view/{$style->guid}/";
                        $enabled = true;
                    }
                }
            }

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $styleeditor_url,
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit layout template', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/text-x-generic-template.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 't',
                    MIDCOM_TOOLBAR_ENABLED => $enabled,
                )
            );
        }

        if (   $topic->can_do('midgard:create')
            && $topic->can_do('midcom.admin.folder:topic_management'))
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/create/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create subfolder', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'f',
                )
            );
            if (   $GLOBALS['midcom_config']['symlinks']
                && $topic->can_do('midcom.admin.folder:symlinks'))
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/createlink/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('create folder link', 'midcom.admin.folder'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'f',
                    )
                );
            }
        }
        if (   $urltopic->guid !== $GLOBALS['midcom_config']['midcom_root_topic_guid']
            && $urltopic->can_do('midgard:delete')
            && $urltopic->can_do('midcom.admin.folder:topic_management'))
        {
            $toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/delete/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('delete folder', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    // for terminate d is used by everyone to go to the location bar
                )
            );
        }
    }

    /**
     * Adds the Host management commands to the specified toolbar.
     *
     * Repeated calls to the same toolbar are intercepted accordingly.
     *
     * @param midcom_helper_toolbar &$toolbar A reference to the toolbar to use.
     * @param int $context_id The context to use (the topic is drawn from there). This defaults
     *     to the currently active context.
     */
    function add_host_management_commands(&$toolbar, $context_id = null)
    {
        if (array_key_exists('midcom_services_toolbars_bound_to_host', $toolbar->customdata))
        {
            // We already processed this toolbar, skipping further adds.
            return;
        }
        else
        {
            $toolbar->customdata['midcom_services_toolbars_bound_to_host'] = true;
        }

        if ($_MIDCOM->auth->user)
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-logout-",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('logout', 'midcom'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/exit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'l',
                )
            );
        }

        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('midgard.admin.asgard', 'midgard.admin.asgard'),
                MIDCOM_TOOLBAR_ICON => 'midgard.admin.asgard/asgard2-16.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'a',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin', 'midgard.admin.asgard'),
            )
        );

        if (midcom_connection::is_admin())
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-cache-invalidate",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('invalidate cache', 'midcom'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
                )
            );

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-exec-midcom/config-test.php",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('test settings', 'midcom'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/start-here.png',
                )
            );
        }
    }

    /**
     * Adds the Help management commands to the specified toolbar.
     *
     * Repeated calls to the same toolbar are intercepted accordingly.
     *
     * @param midcom_helper_toolbar &$toolbar A reference to the toolbar to use.
     * @param int $context_id The context to use (the topic is drawn from there). This defaults
     *     to the currently active context.
     */
    function add_help_management_commands(&$toolbar, $context_id = null)
    {
        if (array_key_exists('midcom_services_toolbars_bound_to_help', $toolbar->customdata))
        {
            // We already processed this toolbar, skipping further adds.
            return;
        }
        else
        {
            $toolbar->customdata['midcom_services_toolbars_bound_to_help'] = true;
        }
        $calling_componentname = $_MIDCOM->get_context_data($context_id, MIDCOM_CONTEXT_COMPONENT);

        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__ais/help/{$calling_componentname}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('component help', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'h',
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_blank'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_help-agent.png',
             )
        );
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "http://www.midgard-project.org/documentation/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('online documentation', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_blank'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_internet.png',
            )
        );
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "http://www.midgard-project.org/discussion/user-forum/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('user forum', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_blank'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock-discussion.png',
            )
        );
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "http://trac.midgard-project.org/roadmap",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('issue tracker', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_blank'),
                MIDCOM_TOOLBAR_ICON => 'midcom.admin.help/applications-development.png',
            )
        );
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "midcom-exec-midcom/about.php",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('about midgard', 'midcom.admin.help'),
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_blank'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/logos/midgard-16x16.png',
            )
        );
    }

    /**
     * Binds the a toolbar to a DBA object. This will append a number of globally available
     * toolbar options. For example, expect Metadata- and Version Control-related options
     * to be added.
     *
     * This call is available through convenience functions throughout the framework: The
     * toolbar main class has a mapping for it (midcom_helper_toolbar::bind_to($object))
     * and object toolbars created by this service will automatically be bound to the
     * specified object.
     *
     * Repeated bind calls are intercepted, you can only bind a toolbar to a single object.
     *
     * @see midcom_helper_toolbar::bind_to()
     * @see create_object_toolbar()
     * @param &$toolbar
     */
    function bind_toolbar_to_object(&$toolbar, &$object)
    {
        if (array_key_exists('midcom_services_toolbars_bound_to_object', $toolbar->customdata))
        {
            // We already processed this toolbar, skipping further adds.
            return;
        }
        else
        {
            $toolbar->customdata['midcom_services_toolbars_bound_to_object'] = true;
        }

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        if (!$prefix)
        {
            debug_add("Toolbar for object {$object->guid} was called before topic prefix was available, skipping global items.", MIDCOM_LOG_WARN);
            return;
        }

        $reflector = new midcom_helper_reflector($object);
        $this->_view_toolbar_label = $reflector->get_class_label();

        if (   $GLOBALS['midcom_config']['metadata_approval']
            && $object->can_do('midcom:approve'))
        {
            $metadata = midcom_helper_metadata::retrieve($object);
            if (   $metadata
                && $metadata->is_approved())
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "{$prefix}__ais/folder/unapprove/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('unapprove', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => $_MIDCOM->i18n->get_string('approved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/page-approved.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $object->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                        MIDCOM_TOOLBAR_ACCESSKEY => 'u',
                    )
                );
            }
            else
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "{$prefix}__ais/folder/approve/",
                        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('approve', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => $_MIDCOM->i18n->get_string('unapproved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/page-notapproved.png',
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $object->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                        MIDCOM_TOOLBAR_ACCESSKEY => 'a',
                    )
                );
            }
        }

        if ($object->can_do('midgard:update'))
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__ais/folder/metadata/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit metadata', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/metadata.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'm',
                )
            );
        }

        if ($object->can_do('midgard:update'))
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__ais/folder/move/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('move', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
                    MIDCOM_TOOLBAR_ENABLED => is_a($object, 'midcom_db_article')
                )
            );
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/object/open/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('manage object', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin', 'midgard.admin.asgard') && $_MIDCOM->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'),
                )
            );
        }

        if (   $GLOBALS['midcom_config']['midcom_services_rcs_enable']
            && $object->can_do('midgard:update')
            && $object->_use_rcs)
        {
            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__ais/rcs/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('show history', 'no.bergfald.rcs'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/history.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'v',
                )
            );
        }
    }

    /**
     * Renders the specified toolbar for the indicated context.
     *
     * If the toolbar is undefined, an empty string is returned. If you want to
     * show the toolbar directly, look for the _show_toolbar method.
     *
     * @param int $toolbar_identifier The toolbar identifier constant (one of
     *     MIDCOM_TOOLBAR_NODE or MIDCOM_TOOLBAR_VIEW etc.)
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function _render_toolbar($toolbar_identifier, $context_id = null)
    {
        if ($context_id === null)
        {
            $context_id = $_MIDCOM->get_current_context();
        }

        if (! array_key_exists($context_id, $this->_toolbars))
        {
            return '';
        }

        return $this->_toolbars[$context_id][$toolbar_identifier]->render();
    }

    /**
     * Renders the node toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function render_node_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_NODE, $context_id);
    }

    /**
     * Renders the view toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function render_view_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_VIEW, $context_id);
    }

    /**
     * Renders the host toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function render_host_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_HOST, $context_id);
    }

    /**
     * Renders the help toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned. If you want to show the toolbar directly, look for
     * the show_xxx_toolbar methods.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @return string The rendered toolbar
     * @see midcom_helper_toolbar::render()
     */
    function render_help_toolbar($context_id = null)
    {
        return $this->_render_toolbar(MIDCOM_TOOLBAR_HELP, $context_id);
    }

    /**
     * Displays the node toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_node_toolbar($context_id = null)
    {
        if ($this->_centralized_mode)
        {
            return;
        }
        echo $this->render_node_toolbar();
    }

    /**
     * Displays the host toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_host_toolbar($context_id = null)
    {
        if ($this->_centralized_mode)
        {
            return;
        }
        echo $this->render_host_toolbar();
    }

    /**
     * Displays the view toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_view_toolbar($context_id = null)
    {
        if ($this->_centralized_mode)
        {
            return;
        }
        echo $this->render_view_toolbar();
    }

    /**
     * Displays the help toolbar for the indicated context. If the toolbar is undefined,
     * an empty string is returned.
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show_help_toolbar($context_id = null)
    {
        if ($this->_centralized_mode)
        {
            return;
        }
        echo $this->render_help_toolbar();
    }

    /**
     * Displays the combined MidCOM toolbar system
     *
     * @param int $context_id The context to retrieve the node toolbar for, this
     *     defaults to the current context.
     * @see midcom_helper_toolbar::render()
     */
    function show($context_id = null)
    {
        if (!$this->_enable_centralized)
        {
            return;
        }

        $this->_centralized_mode = true;

        $enable_drag = false;
        $toolbar_style = "";
        $toolbar_class = "midcom_services_toolbars_simple";

        if ($_MIDCOM->auth->can_user_do('midcom:ajax', null, 'midcom_services_toolbars'))
        {
            $enable_drag = true;
            $toolbar_class = "midcom_services_toolbars_fancy";
            $toolbar_style = "display: none;";
        }

        echo "<div class=\"{$toolbar_class} type_{$this->type}\" style=\"{$toolbar_style}\">\n";
        echo "    <div class=\"logos\">\n";
        echo "        <a href=\"" . $_MIDCOM->get_page_prefix() . "midcom-exec-midcom/about.php\">\n";
        echo "            <img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/logos/midgard-16x16.png\" width=\"16\" height=\"16\" alt=\"Midgard\" />\n";
        echo "        </a>\n";
        echo "    </div>\n";
        echo "    <div class=\"items\">\n";

        if (count($this->_toolbars[$_MIDCOM->get_current_context()][MIDCOM_TOOLBAR_VIEW]->items) > 0)
        {
            echo "        <div id=\"midcom_services_toolbars_topic-page\" class=\"item\">\n";
            echo "            <span class=\"midcom_services_toolbars_topic_title page\">{$this->_view_toolbar_label}</span>\n";
            echo $this->render_view_toolbar();
            echo "        </div>\n";
        }

        echo "        <div id=\"midcom_services_toolbars_topic-folder\" class=\"item\">\n";
        echo "            <span class=\"midcom_services_toolbars_topic_title folder\">". $_MIDCOM->i18n->get_string('folder', 'midcom') . "</span>\n";
        echo $this->render_node_toolbar();
        echo "        </div>\n";
        echo "        <div id=\"midcom_services_toolbars_topic-host\" class=\"item\">\n";
        echo "            <span class=\"midcom_services_toolbars_topic_title host\">". $_MIDCOM->i18n->get_string('host', 'midcom') . "</span>\n";
        echo $this->render_host_toolbar();
        echo "        </div>\n";
        echo "        <div id=\"midcom_services_toolbars_topic-help\" class=\"item\">\n";
        echo "            <span class=\"midcom_services_toolbars_topic_title help\">". $_MIDCOM->i18n->get_string('help', 'midcom.admin.help') . "</span>\n";
        echo $this->render_help_toolbar();
        echo "        </div>\n";
        echo "    </div>\n";
        if ($enable_drag)
        {
            echo "     <div class=\"dragbar\"></div>\n";
        }
        echo "</div>\n";
    }
}
?>
