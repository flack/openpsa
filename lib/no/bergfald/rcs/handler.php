<?php
/**
 * @author tarjei huse
 * @package no.bergfald.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * This handler can be added to your module by some simple steps. Add this to your
 * request_switch array in the main handlerclass:
 *
 * <code>
 *      $rcs_array =  no_bergfald_rcs_handler::get_plugin_handlers();
 *      foreach ($rcs_array as $key => $switch) {
 *            $this->_request_switch[] = $switch;
 *      }
 * </code>
 *
 * If you want to have the handler do a callback to your class to add toolbars or other stuff,
 *
 *
 * Links and urls
 * Linking is done with the format rcs/rcs_action/handler_name/object_guid/<more params>
 * Where handler name is the component using nemein rcs.
 * The handler uses the component name to run a callback so the original handler
 * may control other aspects of the operation.
 *
 * @todo add support for schemas.
 *
 * @package no.bergfald.rcs
 */
class no_bergfald_rcs_handler extends midcom_baseclasses_components_handler
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

    /**
     * Get the localized strings
     *
     * @access private
     */
    function _l10n_get($string_id)
    {
        return $_MIDCOM->i18n->get_string($string_id, 'no.bergfald.rcs');
    }

    /**
     * Static function, returns the request array for the rcs functions.
     *
     * @return array of request params
     * @static
     */
    function get_plugin_handlers()
    {
        $request_switch = array();

        $request_switch[] =  Array
        (
            'handler' => array('no_bergfald_rcs_handler','history'),
            'variable_args' => 1,
        );

        $request_switch[] =  Array
        (
            'fixed_args' => array('preview'),
            'handler' => array('no_bergfald_rcs_handler','preview'),
            'variable_args' => 2,
        );
        $request_switch[] =  Array
        (
            'fixed_args' => array('diff'),
            'handler' => array('no_bergfald_rcs_handler','diff'),
            'variable_args' => 3,
        );
        $request_switch[] =  Array
        (
            'fixed_args' => array('restore'),
            'handler' => array('no_bergfald_rcs_handler','restore'),
            'variable_args' => 2,
        );

        return $request_switch;
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
            default:
                return true;
        }
    }

    /**
     * Load the text_diff libaries needed to show diffs.
     */
    function _on_initialize()
    {
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL."/no.bergfald.rcs/rcs.css",
            )
        );
    }

    /**
     * Load the object and the rcs backend
     *
     */
    function _load_object()
    {
        $this->_object = $_MIDCOM->dbfactory->get_object_by_guid($this->_guid);

        // Get RCS handler from core
        $rcs = $_MIDCOM->get_service('rcs');
        $this->_backend = $rcs->load_handler($this->_object);

        if (get_class($this->_object) != 'midcom_db_topic')
        {
            $_MIDCOM->bind_view_to_object($this->_object);
        }
    }

    function _prepare_breadcrumb()
    {
        $tmp = Array();
        if (!is_a($this->_object, 'midcom_db_topic'))
        {
            $tmp[] = Array
            (
                MIDCOM_NAV_URL => $_MIDCOM->permalinks->create_permalink($this->_object->guid),
                MIDCOM_NAV_NAME => $this->_resolve_object_title(),
            );
        }
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__ais/rcs/{$this->_object->guid}/",
            MIDCOM_NAV_NAME => $_MIDCOM->i18n->get_string('show history', 'no.bergfald.rcs'),
        );
        return $tmp;
    }

    /**
     * Call this after loading an object
     */
    function _prepare_toolbars($revision = '', $diff_view = false)
    {
        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => $_MIDCOM->permalinks->create_permalink($this->_guid),
                MIDCOM_TOOLBAR_LABEL => sprintf($_MIDCOM->i18n->get_string('back to %s', 'no.bergfald.rcs'), $this->_resolve_object_title()),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_up.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

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
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/rcs/diff/{$this->_guid}/{$first}/{$second}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('view %s differences with previous (%s)'), $second, $first),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__ais/rcs/preview/{$this->_guid}/{$revision}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('view this revision (%s)'), $revision),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/search.png',
                MIDCOM_TOOLBAR_ENABLED => true,
            )
        );

        // Display restore and next buttons only if we're not in latest revision
        if ($after != '')
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/rcs/restore/{$this->_guid}/{$revision}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_get('restore this revision (%s)'), $revision),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editpaste.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
                )
            );

            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/rcs/diff/{$this->_guid}/{$revision}/{$after}/",
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
        $this->_guid = $args[0];
        $this->_load_object();
        $this->_prepare_toolbars();

        // Disable the "Show history" button when we're at its view
        $this->_view_toolbar->hide_item("__ais/rcs/{$this->_guid}/");

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('no.bergfald.rcs');

        $tmp = $this->_prepare_breadcrumb();
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        $this->_request_data['view_title'] = sprintf($_MIDCOM->i18n->get_string('revision history of %s', 'no.bergfald.rcs'), $this->_resolve_object_title());
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        return true;
    }

    function _show_history()
    {
        $this->_request_data['history'] = $this->_backend->list_history();
        $this->_request_data['guid']    = $this->_guid;
        midcom_show_style('bergfald-rcs-history');
    }

    function _resolve_object_title()
    {
        if (method_exists($this->_object, 'get_label'))
        {
            return $this->_object->get_label();
        }

        $vars = get_object_vars($this->_object->__object);
        if ( array_key_exists('title', $vars))
        {
            return $this->_object->title;
        }
        else if ( array_key_exists('name', $vars))
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
     */
    function _handler_diff($handler_id, $args, &$data)
    {
        $this->_guid = $args[0];
        $this->_load_object();

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('no.bergfald.rcs');

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
        $this->_request_data['earlier_revision'] = $this->_backend->get_prev_version($args[1]);
        $this->_request_data['previous_revision'] = $args[1];
        $this->_request_data['latest_revision'] = $args[2];
        $this->_request_data['next_revision']  = $this->_backend->get_next_version($args[2]);

        $this->_request_data['guid'] = $args[0];

        $this->_request_data['view_title'] = sprintf($_MIDCOM->i18n->get_string('changes done in revision %s to %s', 'no.bergfald.rcs'), $this->_request_data['latest_revision'], $this->_resolve_object_title());
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        $tmp = $this->_prepare_breadcrumb();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__ais/rcs/preview/{$this->_guid}/{$data['latest_revision']}/",
            MIDCOM_NAV_NAME => sprintf($_MIDCOM->i18n->get_string('version %s', 'no.bergfald.rcs'), $this->_request_data['latest_revision']),
        );
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__ais/rcs/diff/{$this->_guid}/{$data['previous_revision']}/{$data['latest_revision']}/",
            MIDCOM_NAV_NAME => sprintf($_MIDCOM->i18n->get_string('changes from version %s', 'no.bergfald.rcs'), $this->_request_data['previous_revision']),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     * Show the differences between the versions
     */
    function _show_diff()
    {
        if (!$this->_request_data['libs_ok'])
        {
            $this->_request_data['error'] = "You are missing the PEAR library Text_Diff that is needed to show diffs.";
            midcom_show_style('bergfald-rcs-error');
            return;
        }
        midcom_show_style('bergfald-rcs-diff');
    }

    /**
     * View previews
     */
    function _handler_preview($handler_id, $args, &$data)
    {
        $this->_guid = $args[0];
        $this->_args = $args;

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('no.bergfald.rcs');

        $revision = $args[1];

        $this->_load_object();
        $this->_prepare_toolbars($revision);
        $this->_request_data['preview'] = $this->_backend->get_revision($revision);

        $this->_view_toolbar->hide_item("__ais/rcs/preview/{$this->_guid}/{$revision}/");

        $this->_request_data['view_title'] = sprintf($_MIDCOM->i18n->get_string('viewing version %s of %s', 'no.bergfald.rcs'), $revision, $this->_resolve_object_title());
        $_MIDCOM->set_pagetitle($this->_request_data['view_title']);

        $tmp = $this->_prepare_breadcrumb();
        $tmp[] = array
        (
            MIDCOM_NAV_URL => "__ais/rcs/preview/{$this->_guid}/{$revision}/",
            MIDCOM_NAV_NAME => sprintf($_MIDCOM->i18n->get_string('version %s', 'no.bergfald.rcs'), $revision),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        // Set the version numbers
        $this->_request_data['previous_revision'] = $this->_backend->get_prev_version($args[1]);
        $this->_request_data['latest_revision'] = $args[1];
        $this->_request_data['next_revision']  = $this->_backend->get_next_version($args[1]);
        $this->_request_data['guid'] = $args[0];
        return true;
    }

    function _show_preview()
    {
        midcom_show_style('bergfald-rcs-preview');
    }

    /**
     * Restore to diff
     */
    function _handler_restore($handler_id, $args, &$data)
    {
        $this->_guid = $args[0];
        $this->_args = $args;
        $this->_load_object();

        $this->_object->require_do('midgard:update');
        // TODO: set another privilege for restoring?

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('no.bergfald.rcs');

        $this->_prepare_toolbars($args[1]);

        if (   $this->_backend->version_exists($args[1])
            && $this->_backend->restore_to_revision($args[1]))
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('no.bergfald.rcs', 'no.bergfald.rcs'), sprintf($_MIDCOM->i18n->get_string('restore to version %s successful', 'no.bergfald.rcs'), $args[1]), 'ok');
            $_MIDCOM->relocate($_MIDCOM->permalinks->create_permalink($this->_object->guid));
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, sprintf($_MIDCOM->i18n->get_string('restore to version %s failed, reason %s', 'no.bergfald.rcs'), $args[1], $this->_backend->get_error()));
        }
    }
}
?>