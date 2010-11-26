<?php
/**
 * @author tarjei huse
 * @package midgard.admin.asgard
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * This handler can be added to your module by some simple steps. Add this to your
 * request_switch array in the main handlerclass:
 *
 * <pre>
 *      $rcs_array =  no_bergfald_rcs_handler::get_plugin_handlers();
 *      foreach ($rcs_array as $key => $switch) {
 *            $this->_request_switch[] = $switch;
 *      }
 * </pre>
 *
 * If you want to have the handler do a callback to your class to add toolbars or other stuff,
 *
 *
 * Links and urls
 * Linking is done with the format rcs/rcs_action/handler_name/object_guid/<more params>
 * Where handler name is the component using nemein rcs.
 * The handler uses the component name to run a callback so the original handler
 * may control other aspects of the operation
 *
 * @todo add support for schemas.
 * @package midgard.admin.asgard
 */

class midgard_admin_asgard_handler_object_rcs extends midcom_baseclasses_components_handler
{

    /**
     * Current object Guid.
     * @var string
     * @access private
     */
    var $_guid = null;

    /**
     * RCS backend
     * @access private
     */
    var $_backend = null;

    /**
     * Pointer to midgard object
     * @var midcom_db_object
     * @access private
     */
    var $_object = null;

    function __construct()
    {
        $this->_component = 'midgard.admin.asgard';
    }

    /**
     * Get the localized strings
     *
     * @access private
     */
    function _l10n_get($string_id)
    {
        return $this->_l10n->get($string_id);
    }

    /**
     * Static method for determining if we should display a particular field
     * in the diff or preview states
     */
    function is_field_showable($field)
    {
        switch ($field)
        {
            case '_use_rcs':
            case '_topic':
            case 'realm':
            case 'guid':
            case 'id':
            case 'sitegroup':
            case 'action':
            case 'errno':
            case 'errstr':
            case 'revised':
            case 'revisor':
            case 'revision':
            case 'created':
            case 'creator':
            case 'approved':
            case 'approver':
            case 'locked':
            case 'locker':
            case 'lang':
            case 'sid':
                return false;
            case 'password':
                return $_MIDCOM->auth->admin;
            default:
                return true;
        }
    }

    /**
     * Load the text_diff libaries needed to show diffs.
     */
    function _on_initialize()
    {
        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midgard.admin.asgard');
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->add_link_head(array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL."/midgard.admin.asgard/rcs.css",
            )
        );

        $this->_l10n = $_MIDCOM->i18n->get_l10n('midgard.admin.asgard');
        $this->_request_data['l10n'] =& $this->_l10n;

        // Load the helper class
        $_MIDCOM->componentloader->load('midcom.helper.xml');
    }

    /**
     * Load the object and the rcs backend
     */
    function _load_object()
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($this->_guid);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The GUID '{$this->_guid}' was not found.");
            // This will exit
        }

        if (   !$GLOBALS['midcom_config']['midcom_services_rcs_enable']
            || !$this->_object->_use_rcs)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "Revision control not supported for " . get_class($this->_object) . ".");
            // This will exit
        }

        // Load RCS service from core.
        $rcs = $_MIDCOM->get_service('rcs');
        $this->_backend = $rcs->load_handler($this->_object);

        if (get_class($this->_object) != 'midcom_db_topic')
        {
            $_MIDCOM->bind_view_to_object($this->_object);
        }
    }

    function _prepare_request_data($handler_id)
    {
        $this->_request_data['asgard_toolbar'] = new midcom_helper_toolbar();
        midgard_admin_asgard_plugin::bind_to_object($this->_object, $handler_id, $this->_request_data);
        midgard_admin_asgard_plugin::get_common_toolbar($this->_request_data);
    }

    /**
     * Prepare version control toolbar
     *
     * @access private
     */
    function _rcs_toolbar($args = null)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $keys = array_keys($this->_backend->list_history());

        if (isset($keys[0]))
        {
            $first = end($keys);
            $last = $keys[0];
        }

        $this->_request_data['rcs_toolbar'] = new midcom_helper_toolbar();
        $this->_request_data['rcs_toolbar_2'] = new midcom_helper_toolbar();

        if (isset($first))
        {
            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/preview/{$this->_guid}/{$first}",
                    MIDCOM_TOOLBAR_LABEL => $first,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/start.png',
                )
            );
        }

        if (isset($this->_request_data['args'][1]))
        {
            $previous = $this->_backend->get_prev_version($this->_request_data['args'][1]);

            if (!$previous)
            {
                $previous = $this->_request_data['args'][1];
            }

            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/preview/{$this->_guid}/{$previous}",
                    MIDCOM_TOOLBAR_LABEL => $previous,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/previous.png',
                    MIDCOM_TOOLBAR_ENABLED => ($this->_request_data['args'][1] !== $first) ? true : false,
                )
            );

            // Previous
            $previous = $this->_backend->get_prev_version($this->_request_data['args'][1]);

            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/diff/{$this->_guid}/{$previous}/{$this->_request_data['args'][1]}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show differences'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/diff-previous.png',
                    MIDCOM_TOOLBAR_ENABLED => ($this->_request_data['args'][1] !== $first) ? true : false,
                )
            );

            if (isset($this->_request_data['args'][2]))
            {
                $current = $this->_request_data['args'][2];
                $next = $this->_backend->get_next_version($this->_request_data['args'][2]);
            }
            else
            {
                $current = $this->_request_data['args'][1];
                $next = $this->_backend->get_next_version($this->_request_data['args'][1]);
            }

            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/preview/{$current}/{$current}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('version %s'), $current),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/document.png',
                    MIDCOM_TOOLBAR_ENABLED => false,
                )
            );

            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/diff/{$this->_guid}/{$current}/{$next}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show differences'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/diff-next.png',
                    MIDCOM_TOOLBAR_ENABLED => ($next !== $last) ? true : false,
                )
            );

            if (isset($this->_request_data['args'][2]))
            {
                $next = $this->_backend->get_next_version($this->_request_data['args'][2]);
            }
            else
            {
                $next = $this->_backend->get_next_version($this->_request_data['args'][1]);
            }

            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/preview/{$this->_guid}/{$next}",
                    MIDCOM_TOOLBAR_LABEL => $next,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/forward.png',
                    MIDCOM_TOOLBAR_ENABLED => ($this->_request_data['args'][1] !== $last) ? true : false,
                )
            );
        }

        if (isset($last))
        {
            $this->_request_data['rcs_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/preview/{$this->_guid}/{$last}",
                    MIDCOM_TOOLBAR_LABEL => $last,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/finish.png',
                )
            );
        }

        // RCS functional toolbar
        $this->_request_data['rcs_toolbar_2']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/{$this->_guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show history'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/history.png',
            )
        );

        if (isset($current))
        {
            $this->_request_data['rcs_toolbar_2']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "{$prefix}__mfa/asgard/object/rcs/restore/{$this->_guid}/{$current}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('restore version %s'), $current),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    MIDCOM_TOOLBAR_ENABLED => ($current !== $last) ? true : false,
                )
            );
        }
    }

    /**
     * Call this after loading an object
     */
    function _prepare_toolbars($revision = '', $diff_view = false)
    {
        if ($revision == '')
        {
            return;
        }

        $before = $this->_backend->get_prev_version($revision);
        $before2 = $this->_backend->get_prev_version($before);
        $after  = $this->_backend->get_next_version($revision);

        $show_previous = false;
        if ($diff_view)
        {
            if (   $before != ''
                && $before2 != '')
            {
                // When browsing diffs we want to display buttons to previous instead of current
                $first = $before2;
                $second = $before;
                $show_previous = true;
            }
        }
        else
        {
            if ($before != '')
            {
                $first = $before;
                $second = $revision;
                $show_previous = true;
            }
        }

        if ($show_previous)
        {

            $this->_view_toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/rcs/diff/{$this->_guid}/{$first}/{$second}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('view %s differences with previous (%s)'), $second, $first),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }

        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/rcs/preview/{$this->_guid}/{$revision}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('view this revision (%s)'), $revision),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/search.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        // Display restore and next buttons only if we're not in latest revision
        if ($after != '')
        {
            $this->_view_toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/rcs/restore/{$this->_guid}/{$revision}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('restore this revision (%s)'), $revision),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editpaste.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
                )
            );

            $this->_view_toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/rcs/diff/{$this->_guid}/{$revision}/{$after}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('view %s differences with next (%s)'), $revision, $after),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_right.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }

        $_MIDCOM->bind_view_to_object($this->_object);
    }
    /**
     * Show the changes done to the object
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_history($handler_id, $args, &$data)
    {
        $data['args'] = $args;
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');

        // Check if the comparison request is valid
        if (isset($_REQUEST['compare']))
        {
            if (count($_REQUEST['compare']) !== 2)
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('midgard.admin.asgard'), $this->_l10n->get('select exactly two choices'));
            }
            else
            {
                if (version_compare($_REQUEST['compare'][0], '<', $_REQUEST['compare'][1]))
                {
                    $first = $_REQUEST['compare'][0];
                    $last = $_REQUEST['compare'][1];
                }
                else
                {
                    $first = $_REQUEST['compare'][1];
                    $last = $_REQUEST['compare'][0];
                }

                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate("{$prefix}__mfa/asgard/object/rcs/diff/{$args[0]}/{$first}/{$last}/");
                // This will exit
            }
        }

        $this->_guid = $args[0];
        $this->_load_object();
        $this->_prepare_toolbars();
        $this->_prepare_request_data($handler_id);

        // Store the arguments for later use
        $data['args'] = $args;

        // Disable the "Show history" button when we're at its view
        $this->_view_toolbar->hide_item("__mfa/asgard/object/rcs/{$this->_guid}/");

        // Load the toolbars
        $this->_rcs_toolbar();
        midgard_admin_asgard_plugin::get_common_toolbar($data);

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midgard.admin.asgard/rcs.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        $_MIDCOM->add_jscript("jQuery(document).ready(function()
        {
            jQuery('#midgard_admin_asgard_rcs_version_compare table').tablesorter({
                headers:
                {
                    0: {sorter: false},
                    4: {sorter: false},
                    5: {sorter: false}
                },
                sortList: [[1,1]]
            });
        });
        ");

        return true;
    }

    function _show_history()
    {
        midgard_admin_asgard_plugin::asgard_header();
        $this->_request_data['history'] = $this->_backend->list_history();
        $this->_request_data['guid']    = $this->_guid;
        midcom_show_style('midgard_admin_asgard_rcs_history');
        midgard_admin_asgard_plugin::asgard_footer();
    }

    function _resolve_object_title()
    {
        $vars = get_object_vars($this->_object);

        if ( array_key_exists('title', $vars))
        {
            return $this->_object->title;
        }
        elseif ( array_key_exists('name', $vars))
        {
            return $this->_object->name;
        }
        else
        {
            return "#{$this->_object->id}";
        }
    }

    /**
     * Show a diff between two versions
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_diff($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        $this->_guid = $args[0];
        $this->_load_object();

        // Store the arguments for later use
        $data['args'] = $args;

        if (   !$this->_backend->version_exists($args[1])
            || !$this->_backend->version_exists($args[2]) )
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "One of the revisions {$args[1]} or {$args[2]} does not exist.");
        }

        if (!class_exists('Text_Diff'))
        {
            @include_once 'Text/Diff.php';
            @include_once 'Text/Diff/Renderer.php';
            @include_once 'Text/Diff/Renderer/unified.php';
            @include_once 'Text/Diff/Renderer/inline.php';

            if (!class_exists('Text_Diff'))
            {
                debug_add("Failed to load tet_diff libraries! These are needed for this handler. " , MIDCOM_LOG_CRIT);
                $this->_request_data['libs_ok'] = false;
                $this->_prepare_toolbars($args[2]);
                return true;
            }
            else
            {
                $this->_request_data['libs_ok'] = true;
            }
        }
        else
        {
            $this->_request_data['libs_ok'] = true;
        }

        $this->_prepare_toolbars($args[2], true);
        $this->_request_data['diff'] = $this->_backend->get_diff($args[1], $args[2]);

        $this->_request_data['comment'] = $this->_backend->get_comment($args[2]);

        // Set the version numbers
        $this->_request_data['latest_revision'] = $args[2];

        $this->_request_data['guid'] = $args[0];

        $this->_request_data['view_title'] = sprintf($this->_l10n->get('changes done in revision %s to %s'), $this->_request_data['latest_revision'], $this->_resolve_object_title());
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        $this->_prepare_request_data($handler_id);

        // Load the toolbars
        $this->_rcs_toolbar();
        return true;

    }

    /**
     * Show the differences between the versions
     */
    function _show_diff()
    {
        midgard_admin_asgard_plugin::asgard_header();
        if (!$this->_request_data['libs_ok'])
        {
            $this->_request_data['error'] = "You are missing the PEAR library Text_Diff that is needed to show diffs.";
            midcom_show_style('midgard_admin_asgard_rcs_error');
            return;
        }
        midcom_show_style('midgard_admin_asgard_rcs_diff');
        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * View previews
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_preview($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        $this->_guid = $args[0];
        $data['args'] = $args;

        $revision = $args[1];

        $this->_load_object();
        $this->_prepare_toolbars($revision);
        $this->_request_data['preview'] = $this->_backend->get_revision($revision);

        $this->_view_toolbar->hide_item("__mfa/asgard/object/rcs/preview/{$this->_guid}/{$revision}/");

        $this->_request_data['view_title'] = sprintf($this->_l10n->get('viewing version %s of %s'), $revision, $this->_resolve_object_title());
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        $this->_prepare_request_data($handler_id);

        // Set the version numbers
        $this->_request_data['latest_revision'] = $args[1];
        $this->_request_data['guid'] = $args[0];

        // Load the toolbars
        $this->_rcs_toolbar();

        return true;
    }

    function _show_preview()
    {
        midgard_admin_asgard_plugin::asgard_header();
        midcom_show_style('midgard_admin_asgard_rcs_preview');
        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Restore to diff
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_restore($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin');
        $this->_guid = $args[0];
        $data['args'] = $args;
        $this->_load_object();

        // Store the arguments for later use
        $data['args'] = $args;

        $this->_object->require_do('midgard:update');
        // TODO: set another privilege for restoring?

        $this->_prepare_toolbars($args[1]);

        if (   $this->_backend->version_exists($args[1])
            && $this->_backend->restore_to_revision($args[1]))
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('no.bergfald.rcs'), sprintf($this->_l10n->get('restore to version %s successful'), $args[1]));
            $_MIDCOM->relocate("__mfa/asgard/object/view/{$this->_guid}/");
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, sprintf($this->_l10n->get('restore to version %s failed, reason %s'), $args[1], $this->_backend->get_error()));
        }

        // Load the toolbars
        $this->_rcs_toolbar();
    }
}
?>
