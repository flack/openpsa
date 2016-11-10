<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Datamanager 2 CSRF datatype.
 *
 * Based on symfony-csrf
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_csrf extends midcom_helper_datamanager2_widget
{
    /**
     * CSRF Token manager instance
     *
     * @var Symfony\Component\Security\Csrf\CsrfTokenManager
     */
    private $manager;

    /**
     * CSRF Token
     *
     * @var string
     */
    public $value;

    public function _on_initialize()
    {
        $this->manager = new CsrfTokenManager;
        $this->_require_type_value();
    }

    public function get_default()
    {
        return (string) $this->manager->getToken($this->_namespace . $this->name);
    }

    public function sync_type_with_widget($results)
    {
        $this->_type->value = $results[$this->name];
    }

    /**
     * Adds hidden field with token and validation rules
     */
    public function add_elements_to_form($attributes)
    {
        $attributes['class'] = 'hiddentext';
        $this->_form->addElement('hidden', $this->name, null, $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $this->_form->addFormRule(array(&$this, 'validate'));
    }

    /**
     * @return boolean Indicating validity.
     */
    public function validate($fields)
    {
        $value = (string) $fields[$this->name];

        if (!$value) {
            return array($this->name => $this->_l10n->get('type csrf: missing csrf token'));
        }
        if (!$this->manager->isTokenValid(new CsrfToken($this->_namespace . $this->name, $value))) {
            return array($this->name => $this->_l10n->get('type csrf: invalid csrf token'));
        }

        return true;
    }
}
