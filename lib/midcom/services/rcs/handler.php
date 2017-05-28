<?php
/**
 * @package midcom.services.rcs
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 */

/**
 * @package midcom.services.rcs
 */
abstract class midcom_services_rcs_handler extends midcom_baseclasses_components_handler
{
    /**
     * RCS backend
     *
     * @var midcom_services_rcs_backend
     */
    private $backend;

    /**
     * Pointer to midgard object
     *
     * @var midcom_core_dbaobject
     */
    protected $object;

    protected $style_prefix = '';

    protected $url_prefix = '';

    abstract protected function get_object_url();

    abstract protected function handler_callback($handler_id);

    protected function resolve_object_title()
    {
        return midcom_helper_reflector::get($this->object)->get_object_label($this->object);
    }

    /**
     * Load the object and the rcs backend
     *
     * @param string $guid
     */
    private function load_object($guid)
    {
        $this->object = midcom::get()->dbfactory->get_object_by_guid($guid);

        if (   !midcom::get()->config->get('midcom_services_rcs_enable')
            || !$this->object->_use_rcs) {
            throw new midcom_error_notfound("Revision control not supported for " . get_class($this->object) . ".");
        }

        $this->backend = midcom::get()->rcs->load_handler($this->object);
    }

    /**
     * Prepare version control toolbar
     */
    private function rcs_toolbar($current = null, $diff_view = false)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $this->url_prefix;

        $keys = array_keys($this->backend->list_history());

        if (isset($keys[0])) {
            $first = end($keys);
            $last = $keys[0];
        }

        $this->_request_data['rcs_toolbar'] = new midcom_helper_toolbar();
        $buttons = array();
        if (isset($first)) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$first}",
                MIDCOM_TOOLBAR_LABEL => $first,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/start.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $first || $diff_view),
            );
        }

        if (!empty($current)) {
            $previous = $this->backend->get_prev_version($current);
            if (!$previous) {
                $previous = $first;
            }

            $next = $this->backend->get_next_version($current);
            if (!$next) {
                $next = $last;
            }

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$previous}",
                MIDCOM_TOOLBAR_LABEL => $previous,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/previous.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $first || $diff_view),
            );

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}diff/{$this->object->guid}/{$current}/{$previous}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show differences'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/diff-previous.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $first),
            );

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$current}/{$current}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('version %s'), $current),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/document.png',
                MIDCOM_TOOLBAR_ENABLED => false,
            );

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}diff/{$this->object->guid}/{$current}/{$next}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show differences'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/diff-next.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $last),
            );

            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$next}",
                MIDCOM_TOOLBAR_LABEL => $next,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/forward.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $last || $diff_view),
            );
        }

        if (isset($last)) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$last}",
                MIDCOM_TOOLBAR_LABEL => $last,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/finish.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $last || $diff_view),
            );
        }
        $this->_request_data['rcs_toolbar']->add_items($buttons);

        // RCS functional toolbar
        $this->_request_data['rcs_toolbar_2'] = new midcom_helper_toolbar();
        $buttons = array(
            array(
                MIDCOM_TOOLBAR_URL => "{$prefix}{$this->object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show history'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/history.png',
            )
        );

        if (!empty($current)) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "{$prefix}restore/{$this->object->guid}/{$current}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('restore version %s'), $current),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_ENABLED => ($current !== $last),
            );
        }
        $this->_request_data['rcs_toolbar_2']->add_items($buttons);
    }

    public function translate($string)
    {
        $translated = $string;
        $component = midcom::get()->dbclassloader->get_component_for_class($this->object->__midcom_class_name__);
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
     * Show the changes done to the object
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_history($handler_id, array $args, array &$data)
    {
        // Check if the comparison request is valid
        if (    !empty($_GET['first']) && !empty($_GET['last'])
            && $_GET['first']  !== $_GET['last']) {
            return new midcom_response_relocate($this->url_prefix . "diff/{$args[0]}/{$_GET['first']}/{$_GET['last']}/");
        }

        $this->load_object($args[0]);
        $data['view_title'] = sprintf($this->_l10n->get('revision history of %s'), $this->resolve_object_title());

        $this->rcs_toolbar();
        return $this->handler_callback($handler_id);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_history($handler_id, array &$data)
    {
        $data['history'] = $this->backend->list_history();
        $data['guid'] = $this->object->guid;
        midcom_show_style($this->style_prefix . 'history');
    }

    /**
     * Show a diff between two versions
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_diff($handler_id, array $args, array &$data)
    {
        $this->load_object($args[0]);

        if (   !$this->backend->version_exists($args[1])
            || !$this->backend->version_exists($args[2])) {
            throw new midcom_error_notfound("One of the revisions {$args[1]} or {$args[2]} does not exist.");
        }

        $data['diff'] = $this->backend->get_diff($args[1], $args[2]);

        $data['comment'] = $this->backend->get_comment($args[2]);

        // Set the version numbers
        $data['compare_revision'] = $args[1];
        $data['latest_revision'] = $args[2];
        $data['guid'] = $args[0];

        $data['view_title'] = sprintf($this->_l10n->get('changes done in revision %s to %s'), $data['latest_revision'], $this->resolve_object_title());
        $data['handler'] = $this;

        // Load the toolbars
        $this->rcs_toolbar($args[2], true);
        return $this->handler_callback($handler_id);
    }

    /**
     * Show the differences between the versions
     */
    public function _show_diff()
    {
        midcom_show_style($this->style_prefix . 'diff');
    }

    /**
     * View previews
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_preview($handler_id, array $args, array &$data)
    {
        $revision = $args[1];
        $data['latest_revision'] = $revision;
        $data['guid'] = $args[0];

        $this->load_object($args[0]);
        $data['preview'] = $this->backend->get_revision($revision);

        $this->_view_toolbar->hide_item($this->url_prefix . "preview/{$this->object->guid}/{$revision}/");

        $data['view_title'] = sprintf($this->_l10n->get('viewing version %s of %s'), $revision, $this->resolve_object_title());
        $data['handler'] = $this;
        // Load the toolbars
        $this->rcs_toolbar($revision);
        return $this->handler_callback($handler_id);
    }

    public function _show_preview()
    {
        midcom_show_style($this->style_prefix . 'preview');
    }

    /**
     * Restore to diff
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_restore($handler_id, array $args, array &$data)
    {
        $this->load_object($args[0]);

        $this->object->require_do('midgard:update');
        // TODO: set another privilege for restoring?

        if (   $this->backend->version_exists($args[1])
            && $this->backend->restore_to_revision($args[1])) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.rcs'), sprintf($this->_l10n->get('restore to version %s successful'), $args[1]));
            return new midcom_response_relocate($this->get_object_url());
        }
        throw new midcom_error(sprintf($this->_l10n->get('restore to version %s failed, reason %s'), $args[1], $this->backend->get_error()));
    }
}
