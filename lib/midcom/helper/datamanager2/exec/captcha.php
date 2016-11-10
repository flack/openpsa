<?php
/**
 * Captcha display handler. Takes the session data and renders the captcha PNG.
 *
 * No caching is done so far.
 *
 * If the session is not valid, a 500 will be triggered.
 *
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
use Gregwar\Captcha\CaptchaBuilder;

global $argc, $argv;

if (!isset($argv[0]))
{
    throw new midcom_error_notfound("Missing CAPTCHA session key.");
}

$builder = new CaptchaBuilder();
$builder->build(200, 80);

$session = new midcom_services_session('midcom_helper_datamanager2_widget_captcha');
$session->set($argv[0], $builder->getPhrase());

// Render the Captcha
midcom::get()->header('Content-Type: image/jpeg');
$builder->output();