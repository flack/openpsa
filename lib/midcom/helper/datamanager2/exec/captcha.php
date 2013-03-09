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

$session = new midcom_services_session('midcom_helper_datamanager2_widget_captcha');

if (!isset($argv[0]))
{
    throw new midcom_error_notfound("Missing CAPTCHA session key.");
}

if (   $argc == 0
    || !$session->exists($argv[0]))
{
    throw new midcom_error('Failed to generate CAPTCHA, the session key passed is invalid.');
}

$passphrase = $session->get($argv[0]);
if (empty($passphrase))
{
    throw new midcom_error('Failed to generate CAPTCHA, no passphrase in session.');
}

$builder = new CaptchaBuilder($passphrase);
$builder->build(200, 80);

// Render the Captcha
_midcom_header('Content-Type: image/jpeg');
$builder->output();
?>
