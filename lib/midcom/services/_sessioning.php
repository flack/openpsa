<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base singleton class of the MidCOM sessioning service.
 *
 * This is a singleton class, that is accessible through the MidCOM Service
 * infrastructure. It manages session data of MidCOM driven applications.
 *
 * This class provides a generic interface to store keyed session values in the
 * domain of the corresponding component.
 *
 * All requests involving this service will always be flagged as no_cache.
 *
 * If you store class instances within a session, which is perfectly safe in
 * general, there are known problems due to the fact, that a class declaration
 * has to be available before it can be deserialized. As PHP sessioning does this
 * deserialization automatically, this might fail with MidCOM, where the sequence
 * in which the code gets loaded and the sessioning gets started up is actually
 * undefined. To get around this problems, the sessioning system stores not the
 * actual data in the sessioning array, but a serialized string of the data, which
 * can always be deserialized on PHP sessioning startup (its a string after all).
 * This has an important implication though: The sessioning system always stores
 * copies of the data, not references. So if you put something in to the session
 * store and modify it afterwards, this change will not be reflected in the
 * sessioning store.
 *
 * <b>Important:</b>
 *
 * Do <b>never</b> create an instance of this class directly. This is handled
 * by the framework. Instead use midcom_service_session which ensures the
 * singleton pattern.
 *
 * Do <b>never</b> work directly with the $_SESSION["midcom_session_data"]
 * variable, this is a 100% must-not, as this will break functionality.
 *
 * @package midcom.services
 * @see midcom_services_session
 */
class midcom_services__sessioning extends Session
{
    public function __construct(RequestStack $stack, bool $cookie_secure)
    {
        $storage = $this->prepare_storage($stack->getCurrentRequest(), $cookie_secure);

        parent::__construct($storage, new AttributeBag('midcom_session_data'));
    }

    protected function prepare_storage(?Request $request, bool $cookie_secure)
    {
        return new NativeSessionStorage([
            'cookie_path' => midcom_connection::get_url('prefix') ?: '/',
            'cookie_secure' => $cookie_secure && $request && $request->isSecure(),
            'cookie_httponly' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null) : mixed
    {
        if ($this->has($key)) {
            midcom::get()->cache->content->no_cache();
        }
        return parent::get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value) : void
    {
        midcom::get()->cache->content->no_cache();
        parent::set($key, $value);
    }
}
