<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Preferences interface
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_preferences extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private midcom_db_person $_person;

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
     * @param string $guid The person GUID
     */
    public function _handler_preferences(Request $request, array &$data, ?string $guid = null)
    {
        midcom::get()->auth->require_valid_user();

        if (isset($guid)) {
            $this->_person = new midcom_db_person($guid);
        } else {
            $this->_person = midcom::get()->auth->user->get_storage();
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
        return $this->get_response('midgard_admin_asgard_preferences');
    }

    /**
     * Get the languages as identifier -> name pairs
     */
    public static function get_languages() : array
    {
        $current_lang = midcom::get()->i18n->get_current_language();

        $languages = [];
        $finder = new Finder;
        foreach ($finder->files()->in(dirname(__DIR__) . '/locale')->name('default.*.txt') as $file) {
            $identifier = str_replace('default.', '', $file->getBasename('.txt'));
            $languages[$identifier] = Languages::getName($identifier, $current_lang);
            $localname = Languages::getName($identifier, $identifier);
            if ($localname != $languages[$identifier]) {
                $languages[$identifier] .= ' (' . $localname . ')';
            }
        }

        if (!array_key_exists($current_lang, $languages)) {
            return $languages;
        }

        // Initialize a new array for the current language
        $prepend = [
            '' => midcom::get()->i18n->get_string('default setting', 'midgard.admin.asgard'),
            $current_lang => $languages[$current_lang]
        ];

        // Remove the reference from the original array
        unset($languages[$current_lang]);

        // Join the arrays
        return array_merge($prepend, $languages);
    }

    /**
     * AJAX backend for saving data on the fly
     */
    public function _handler_ajax(Request $request)
    {
        $this->_person = midcom::get()->auth->user->get_storage();
        $this->_person->require_do('midgard:update');

        // Patch for Midgard ACL problem of setting person's own parameters
        midcom::get()->auth->request_sudo($this->_component);

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
        return new JsonResponse;
    }
}
