<?php
/**
 * @package midcom.services.rcs
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 */

use Symfony\Component\HttpFoundation\Request;
use midcom\datamanager\schemabuilder;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package midcom.services.rcs
 */
abstract class midcom_services_rcs_handler extends midcom_baseclasses_components_handler
{
    private midcom_services_rcs_backend $backend;

    protected midcom_core_dbaobject $object;

    protected string $style_prefix = '';

    protected string $url_prefix = '';

    abstract protected function get_object_url() : string;

    abstract protected function reply(string $element) : Response;

    abstract protected function get_breadcrumbs() : array;

    protected function resolve_object_title()
    {
        return midcom_helper_reflector::get($this->object)->get_object_label($this->object);
    }

    protected function load_object(string $guid) : midcom_core_dbaobject
    {
        return midcom::get()->dbfactory->get_object_by_guid($guid);
    }

    /**
     * Load the object and the rcs backend
     */
    private function prepare_backend(string $guid)
    {
        $this->object = $this->load_object($guid);

        if (   !midcom::get()->config->get('midcom_services_rcs_enable')
            || !$this->object->_use_rcs) {
            throw new midcom_error_notfound("Revision control not supported for " . get_class($this->object) . ".");
        }

        $this->backend = midcom::get()->rcs->load_backend($this->object);
    }

    /**
     * Prepare version control toolbar
     */
    private function rcs_toolbar(array $revision, array $revision2 = null)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.services.rcs/rcs.css");
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $this->url_prefix;
        $history = $this->backend->get_history();
        $this->_request_data['rcs_toolbar'] = new midcom_helper_toolbar();
        $this->populate_rcs_toolbar($history, $prefix, $revision, $revision2);

        // RCS functional toolbar
        $this->_request_data['rcs_toolbar_2'] = new midcom_helper_toolbar();
        $restore = $revision2 ?: $revision;

        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => "{$prefix}{$this->object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show history'),
                MIDCOM_TOOLBAR_GLYPHICON => 'history',
            ], [
                MIDCOM_TOOLBAR_URL => "{$prefix}restore/{$this->object->guid}/{$restore['revision']}/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('restore version %s'), $restore['version']),
                MIDCOM_TOOLBAR_GLYPHICON => 'recycle',
                MIDCOM_TOOLBAR_ENABLED => ($restore['revision'] !== $history->get_last()['revision']),
            ]
        ];
        $this->_request_data['rcs_toolbar_2']->add_items($buttons);
    }

    private function populate_rcs_toolbar(midcom_services_rcs_history $history, string $prefix, array $revision, ?array $revision2)
    {
        $first = $history->get_first();
        $last = $history->get_last();

        $diff_view = !empty($revision2);
        $revision2 = $revision2 ?? $revision;

        if ($previous = $history->get_previous($revision['revision'])) {
            $enabled = true;
        } else {
            $enabled = false;
            $previous = $first;
        }
        $this->add_preview_button($prefix, $first, 'fast-backward', $enabled || $diff_view);
        $this->add_preview_button($prefix, $diff_view ? $revision : $previous, 'backward', $enabled || $diff_view);
        $this->add_diff_button($prefix, $previous, $revision, 'step-backward', $enabled);

        $this->_request_data['rcs_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$revision2['revision']}/",
            MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('version %s'), $revision2['version']),
            MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
            MIDCOM_TOOLBAR_ENABLED => $diff_view,
        ]);

        if ($next = $history->get_next($revision2['revision'])) {
            $enabled = true;
        } else {
            $enabled = false;
            $next = $last;
        }
        $this->add_diff_button($prefix, $revision2, $next, 'step-forward', $enabled);
        $this->add_preview_button($prefix, $next, 'forward', $enabled || $diff_view);
        $this->add_preview_button($prefix, $last, 'fast-forward', $enabled || $diff_view);
    }

    private function add_preview_button(string $prefix, array $entry, string $icon, bool $enabled)
    {
        $this->_request_data['rcs_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => "{$prefix}preview/{$this->object->guid}/{$entry['revision']}/",
            MIDCOM_TOOLBAR_LABEL => $entry['version'],
            MIDCOM_TOOLBAR_GLYPHICON => $icon,
            MIDCOM_TOOLBAR_ENABLED => $enabled,
        ]);
    }

    private function add_diff_button(string $prefix, array $first, array $second, string $icon, bool $enabled)
    {
        $this->_request_data['rcs_toolbar']->add_item([
            MIDCOM_TOOLBAR_URL => "{$prefix}diff/{$this->object->guid}/{$first['revision']}/{$second['revision']}/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('show differences'),
            MIDCOM_TOOLBAR_GLYPHICON => $icon,
            MIDCOM_TOOLBAR_ENABLED => $enabled,
        ]);
    }

    private function prepare_request_data(string $view_title)
    {
        foreach ($this->get_breadcrumbs() as $item) {
            $this->add_breadcrumb($item[MIDCOM_NAV_URL], $item[MIDCOM_NAV_NAME]);
        }
        $this->add_breadcrumb($this->url_prefix . "{$this->object->guid}/", $this->_l10n->get('show history'));

        $this->_request_data['handler'] = $this;
        $this->_request_data['view_title'] = $view_title;
        midcom::get()->head->set_pagetitle($view_title);
    }

    public function translate(string $string) : string
    {
        $stack = [$this->_component, 'midcom'];
        if ($component = midcom::get()->dbclassloader->get_component_for_class($this->object->__midcom_class_name__)) {
            array_unshift($stack, $component);
        }
        foreach ($stack as $db) {
            $translated = $this->_i18n->get_string($string, $db);
            if ($translated != $string) {
                return $translated;
            }
        }
        return $string;
    }

    /**
     * Show the changes done to the object
     */
    public function _handler_history(Request $request, array &$data, array $args)
    {
        // Check if the comparison request is valid
        $first = $request->query->get('first');
        $last = $request->query->get('last');
        if ($first && $last && $first != $last) {
            return new midcom_response_relocate($this->url_prefix . "diff/{$args[0]}/{$first}/{$last}/");
        }

        $this->prepare_backend($args[0]);
        $view_title = sprintf($this->_l10n->get('revision history of %s'), $this->resolve_object_title());

        $this->prepare_request_data($view_title);
        $data['history'] = $this->backend->get_history();
        $data['guid'] = $this->object->guid;
        return $this->reply($this->style_prefix . 'history');
    }

    /**
     * Show a diff between two versions
     */
    public function _handler_diff(array $args, array &$data)
    {
        $this->prepare_backend($args[0]);
        $history = $this->backend->get_history();

        $compare_revision = $history->get($args[1]);
        $latest_revision = $history->get($args[2]);

        if (!$compare_revision || !$latest_revision) {
            throw new midcom_error_notfound("One of the revisions {$args[1]} or {$args[2]} does not exist.");
        }

        $data['diff'] = array_filter($this->backend->get_diff($args[1], $args[2]), function($value, $key) {
            return array_key_exists('diff', $value)
                && !is_array($value['diff'])
                && midcom_services_rcs::is_field_showable($key);
        }, ARRAY_FILTER_USE_BOTH);
        $data['comment'] = $latest_revision;
        $data['revision_info1'] = $this->render_revision_info($compare_revision);
        $data['revision_info2'] = $this->render_revision_info($latest_revision);

        // Set the version numbers
        $data['guid'] = $args[0];

        $view_title = sprintf($this->_l10n->get('changes between revisions %s and %s'), $compare_revision['version'], $latest_revision['version']);

        // Load the toolbars
        $this->rcs_toolbar($compare_revision, $latest_revision);
        $this->prepare_request_data($view_title);
        $this->add_breadcrumb(
            $this->url_prefix . "diff/{$this->object->guid}/{$compare_revision['revision']}/{$latest_revision['revision']}/",
            sprintf($this->_l10n->get('differences between %s and %s'), $compare_revision['version'], $latest_revision['version'])
        );

        return $this->reply($this->style_prefix . 'diff');
    }

    private function render_revision_info(array $metadata) : string
    {
        $output = sprintf($this->_l10n->get('version %s'), $metadata['version']) . ' <span>';

        if ($user = midcom::get()->auth->get_user($metadata['user'])) {
            $output .= $user->get_storage()->name;
        } else {
            $output .= $this->_l10n_midcom->get('unknown user');
        }
        $longdate = $this->_l10n->get_formatter()->datetime($metadata['date'], IntlDateFormatter::MEDIUM, IntlDateFormatter::LONG);
        $output .= ', <span title="' . $longdate . '">' . $this->_l10n->get_formatter()->datetime($metadata['date']) . '</span></span>';

        return $output;
    }

    /**
     * View previews
     */
    public function _handler_preview(array $args, array &$data)
    {
        $revision = $args[1];
        $data['latest_revision'] = $revision;
        $data['guid'] = $args[0];
        $this->prepare_backend($args[0]);
        $metadata = $this->backend->get_history()->get($args[1]);
        if (!$metadata) {
            throw new midcom_error_notfound("Revision {$args[1]} does not exist.");
        }

        $data['preview'] = array_filter($this->backend->get_revision($revision), function ($value, $key) {
            return !is_array($value)
                && !in_array($value, ['', '0000-00-00'])
                && midcom_services_rcs::is_field_showable($key);
        }, ARRAY_FILTER_USE_BOTH);

        $schemadb = (new schemabuilder($this->object))->create(null);
        $data['datamanager'] = (new datamanager($schemadb))
            ->set_defaults($data['preview'])
            ->set_storage(new $this->object->__midcom_class_name__);

        $view_title = sprintf($this->_l10n->get('viewing version %s from %s'), $metadata['version'], $this->_l10n->get_formatter()->datetime($metadata['date']));
        // Load the toolbars
        $this->rcs_toolbar($metadata);
        $this->prepare_request_data($view_title);
        return $this->reply($this->style_prefix . 'preview');
    }

    /**
     * Restore to diff
     */
    public function _handler_restore(array $args)
    {
        $this->prepare_backend($args[0]);

        $this->object->require_do('midgard:update');
        // TODO: set another privilege for restoring?

        if (   $this->backend->get_history()->version_exists($args[1])
            && $this->backend->restore_to_revision($args[1])) {
            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.rcs'), sprintf($this->_l10n->get('restore to version %s successful'), $args[1]));
            return new midcom_response_relocate($this->get_object_url());
        }
        throw new midcom_error(sprintf($this->_l10n->get('restore to version %s failed, reason %s'), $args[1], midcom_connection::get_error_string()));
    }
}
