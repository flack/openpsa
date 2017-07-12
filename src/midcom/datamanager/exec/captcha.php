<?php
/**
 * Captcha display handler. Takes the session data and renders the captcha image
 *
 * @package midcom.datamanager
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
use Gregwar\Captcha\CaptchaBuilder;

global $argc, $argv;

if (!isset($argv[0])) {
    throw new midcom_error_notfound("Missing CAPTCHA session key.");
}

$builder = new CaptchaBuilder();
$builder->build(200, 80);

$session = new midcom_services_session('midcom_datamanager_captcha');
$session->set($argv[0], $builder->getPhrase());

// Render the Captcha
midcom::get()->header('Content-Type: image/jpeg');
$builder->output();
