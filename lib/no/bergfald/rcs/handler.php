<?php
/**
 * @author tarjei huse
 * @package no.bergfald.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Simple styling class to make html out of diffs and get a simple way
 * to provide rcs functionality
 *
 * This handler can be added to your module by some simple steps. Add this to your
 * request_switch array in the main handler class:
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
class no_bergfald_rcs_handler extends midcom_baseclasses_components_plugin
{
    /**
     * Current object Guid.
     *
     * @var string
     */
    private $_guid;

    /**
     * RCS backend
     *
     * @var midcom_services_rcs_backend
     */
    private $_backend;

    /**
     * The object we're working on
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Load the statics & prepend styledir
     */
    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('no.bergfald.rcs');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/no.bergfald.rcs/rcs.css");
    }

    /**
     * Load the object and the rcs backend
     */
    private function _load_object()
    {
        $this->_object = midcom::get()->dbfactory->get_object_by_guid($this->_guid);

        // Get RCS handler from core
        $rcs = midcom::get()->rcs;
        $this->_backend = $rcs->load_handler($this->_object);

        if (get_class($this->_object) != 'midcom_db_topic') {
            $this->bind_view_to_object($this->_object);
        }
    }

    private function _prepare_breadcrumb()
    {
        if (!is_a($this->_object, 'midcom_db_topic')) {
            $this->add_breadcrumb(midcom::get()->permalinks->create_permalink($this->_object->guid), $this->_resolve_object_title());
        }
        $this->add_breadcrumb("__ais/rcs/{$this->_object->guid}/", $this->_l10n->get('show history'));
    }

    /**
     * Call this after loading an object
     */
    private function _prepare_toolbars($revision = '', $diff_view = false)
    {
        $this->_view_toolbar->add_item(
            array
            (
                MIDCOM_TOOLBAR_URL => midcom::get()->permalinks->create_permalink($this->_guid),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('back to %s'), $this->_resolve_object_title()),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_up.png',
            )
        );

        if ($revision == '') {
            return;
        }

        $show_previous = false;
        if ($before = $this->_backend->get_prev_version($revision)) {
            if ($diff_view) {
                if ($before2 = $this->_backend->get_prev_version($before)) {
                    // When browsing diffs we want to display buttons to previous instead of current
                    $first = $before2;
                    $second = $before;
                    $show_previous = true;
                }
            } else {
                $first = $before;
                $second = $revision;
                $show_previous = true;
            }
        }
        $buttons = array();
        if ($show_previous) {
            $buttons[] = array
            (
                MIDCOM_TOOLBAR_URL => "__ais/rcs/diff/{$this->_guid}/{$first}/{$second}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('view %s differences with previous (%s)'), $second, $first),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            );
        }

        $buttons[] = array
        (
            MIDCOM_TOOLBAR_URL => "__ais/rcs/preview/{$this->_guid}/{$revision}/",
            MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('view this revision (%s)'), $revision),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/search.png',
        );

        // Display restore and next buttons only if we're not in latest revision
        if ($after = $this->_backend->get_next_version($revision)) {
            $buttons[] = array
            (
                MIDCOM_TOOLBAR_URL => "__ais/rcs/restore/{$this->_guid}/{$revision}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('restore this revision (%s)'), $revision),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/editpaste.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
            );

            $buttons[] = array
            (
                MIDCOM_TOOLBAR_URL => "__ais/rcs/diff/{$this->_guid}/{$revision}/{$after}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('view %s differences with next (%s)'), $revision, $after),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_right.png',
            );
        }
        $this->_view_toolbar->add_items($buttons);

        $this->bind_view_to_object($this->_object);
    }

    /**
     * Show the changes done to the object
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_history($handler_id, array $args, array &$data)
    {
        $this->_guid = $args[0];
        $this->_load_object();
        $this->_prepare_toolbars();

        // Disable the "Show history" button when we're at its view
        $this->_view_toolbar->hide_item("__ais/rcs/{$this->_guid}/");

        $this->_prepare_breadcrumb();

        $data['view_title'] = sprintf($this->_l10n->get('revision history of %s'), $this->_resolve_object_title());
        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_history($handler_id, array &$data)
    {
        $this->_request_data['history'] = $this->_backend->list_history();
        $this->_request_data['guid'] = $this->_guid;
        midcom_show_style('bergfald-rcs-history');
    }

    private function _resolve_object_title()
    {
        return midcom_helper_reflector::get($this->_object)->get_object_label($this->_object);
    }

    /**
     * Show a diff between two versions
     */
    public function _handler_diff($handler_id, array $args, array &$data)
    {
        $this->_guid = $args[0];
        $this->_load_object();

        if (   !$this->_backend->version_exists($args[1])
            || !$this->_backend->version_exists($args[2])) {
            throw new midcom_error_notfound("One of the revisions {$args[1]} or {$args[2]} does not exist.");
        }

        $this->_request_data['diff'] = $this->_backend->get_diff($args[1], $args[2]);

        $this->_prepare_toolbars($args[2], true);

        $this->_request_data['comment'] = $this->_backend->get_comment($args[2]);

        // Set the version numbers
        $this->_request_data['earlier_revision'] = $this->_backend->get_prev_version($args[1]);
        $this->_request_data['previous_revision'] = $args[1];
        $this->_request_data['latest_revision'] = $args[2];
        $this->_request_data['next_revision']  = $this->_backend->get_next_version($args[2]);

        $this->_request_data['guid'] = $args[0];

        $this->_request_data['view_title'] = sprintf($this->_l10n->get('changes done in revision %s to %s'), $this->_request_data['latest_revision'], $this->_resolve_object_title());
        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);

        $this->_prepare_breadcrumb();
        $this->add_breadcrumb
        (
            "__ais/rcs/preview/{$this->_guid}/{$data['latest_revision']}/",
            sprintf($this->_l10n->get('version %s'), $data['latest_revision'])
        );
        $this->add_breadcrumb
        (
            "__ais/rcs/diff/{$this->_guid}/{$data['previous_revision']}/{$data['latest_revision']}/",
            sprintf($this->_l10n->get('changes from version %s'), $data['previous_revision'])
        );
    }

    /**
     * Show the differences between the versions
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_diff($handler_id, array &$data)
    {
        $data['handler'] = $this;
        midcom_show_style('bergfald-rcs-diff');
    }

    public function translate($string)
    {
        $translated = $string;
        $component = midcom::get()->dbclassloader->get_component_for_class($this->_object->__midcom_class_name__);
        if (midcom::get()->componentloader->is_installed($component)) {
            $translated = midcom::get()->i18n->get_l10n($component)->get($string);
        }
        if ($translated === $string) {
            $translated = $this->_l10n->get($string);
        }
        if ($translated === $string) {
            $translated = $this->_l10n_midcom->get($string);
        }
        return $translated;
    }

    /**
     * View previews
     */
    public function _handler_preview($handler_id, array $args, array &$data)
    {
        $this->_guid = $args[0];
        $this->_args = $args;

        $revision = $args[1];

        $this->_load_object();
        $this->_prepare_toolbars($revision);
        $this->_request_data['preview'] = $this->_backend->get_revision($revision);

        $this->_view_toolbar->hide_item("__ais/rcs/preview/{$this->_guid}/{$revision}/");

        $this->_request_data['view_title'] = sprintf($this->_l10n->get('viewing version %s of %s'), $revision, $this->_resolve_object_title());
        midcom::get()->head->set_pagetitle($this->_request_data['view_title']);

        $this->_prepare_breadcrumb();
        $this->add_breadcrumb
        (
            "__ais/rcs/preview/{$this->_guid}/{$revision}/",
            sprintf($this->_l10n->get('version %s'), $revision)
        );

        // Set the version numbers
        $data['previous_revision'] = $this->_backend->get_prev_version($args[1]);
        $data['latest_revision'] = $args[1];
        $data['next_revision']  = $this->_backend->get_next_version($args[1]);
        $data['guid'] = $args[0];
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_preview($handler_id, array &$data)
    {
        $data['handler'] = $this;
        midcom_show_style('bergfald-rcs-preview');
    }

    /**
     * Restore to diff
     */
    public function _handler_restore($handler_id, array $args, array &$data)
    {
        $this->_guid = $args[0];
        $this->_args = $args;
        $this->_load_object();

        $this->_object->require_do('midgard:update');
        // TODO: set another privilege for restoring?

        if (   $this->_backend->version_exists($args[1])
            && $this->_backend->restore_to_revision($args[1])) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('restore to version %s successful'), $args[1]));
            return new midcom_response_relocate(midcom::get()->permalinks->create_permalink($this->_object->guid));
        }
        throw new midcom_error(sprintf($this->_l10n->get('restore to version %s failed, reason %s'), $args[1], $this->_backend->get_error()));
    }
}
