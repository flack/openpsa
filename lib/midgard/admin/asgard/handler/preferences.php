<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Preferences interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_preferences extends midcom_baseclasses_components_handler
{
    /**
     * User for the preferences page
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * Process the UI information
     */
    private function _process_request_data(array &$data)
    {
        $data['view_title'] = $this->_l10n->get('user preferences');

        // Set the breadcrumb data
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('preferences'), $this->_l10n->get('user preferences'));

        if ($this->_person->guid !== midcom::get()->auth->user->guid) {
            $this->add_breadcrumb('', $this->_person->name);
        }
    }

    /**
     * Handle the preference request
     *
     * @param Request $request The request object
     * @param array &$data The local request data.
     * @param string $guid The person GUID
     */
    public function _handler_preferences(Request $request, array &$data, $guid = null)
    {
        midcom::get()->auth->require_valid_user();

        if (isset($guid)) {
            $this->_person = new midcom_db_person($guid);
        } else {
            $this->_person = new midcom_db_person(midcom_connection::get_user());
        }

        // Load the controller instance
        $data['controller'] = datamanager::from_schemadb($this->_config->get('schemadb_preferences'))
            ->set_storage($this->_person)
            ->get_controller();

        $return_page = $request->query->get('return_uri', '__mfa/asgard/');
        // Process the requested form
        switch ($data['controller']->handle($request)) {
            case 'save':
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('preferences saved'));
                return new midcom_response_relocate($return_page);

            case 'cancel':
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n_midcom->get('cancelled'));
                return new midcom_response_relocate($return_page);
        }

        // Load the common data
        $this->_process_request_data($data);
        return new midgard_admin_asgard_response($this, '_show_preferences');
    }

    /**
     * Show the preferences page
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_preferences($handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_preferences');
    }

    /**
     * Get the languages
     */
    public static function get_languages()
    {
        $lang_str = midcom::get()->i18n->get_current_language();
        $languages = midcom::get()->i18n->list_languages();

        if (!array_key_exists($lang_str, $languages)) {
            return $languages;
        }

        // Initialize a new array for the current language
        $language = [
            '' => midcom::get()->i18n->get_string('default setting')
        ];
        $language[$lang_str] = $languages[$lang_str];

        // Remove the reference from the original array
        unset($languages[$lang_str]);

        // Join the arrays
        return array_merge($language, $languages);
    }

    /**
     * AJAX backend for saving data on the fly
     *
     * @param Request $request The request object
     */
    public function _handler_ajax(Request $request)
    {
        $this->_person = new midcom_db_person(midcom_connection::get_user());
        $this->_person->require_do('midgard:update');

        // Patch for Midgard ACL problem of setting person's own parameters
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        foreach ($request->request->all() as $key => $value) {
            if (is_array($value)) {
                $value = serialize($value);
            }

            if (!$this->_person->set_parameter('midgard.admin.asgard:preferences', $key, $value)) {
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('failed to save the preference for %s'), $this->_l10n->get($key)));
            }

            debug_add("Added configuration key-value pair {$key} => {$value}");
        }

        midcom::get()->auth->drop_sudo();
        return new midcom_response_json;
    }
}
