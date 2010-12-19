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

require_once 'Text/CAPTCHA.php';
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

// Set Captcha options (font must exist!)
$options = Array
(
    'width' => 200,
    'height' => 80,
    'phrase' => $passphrase,
    'imageOptions' => array
    (
        'font_path' => MIDCOM_ROOT . '/midcom/helper/datamanager2/widget/',
        'font_file' => 'captcha-font.ttf',
        'background_color' => '#000000',
        'text_color'       => '#FFFFFF',
    ),
);

/* Generate a new Text_CAPTCHA object, Image driver
 * This is working at least with Text_CAPTCHAS latest alpha version. Older versions
 * give missing font error
 */

   $captcha = Text_CAPTCHA::factory('Image');
   $result = $captcha->init($options);

if (PEAR::isError($result))
{
    throw new midcom_error('Failed to generate CAPTCHA, class init call failed: ' . $result->getMessage());
}

// Get Captcha GD Handle
$image = $captcha->getCAPTCHA();
if (! is_resource($image))
{
    throw new midcom_error('Failed to generate CAPTCHA, rendering failed. Reason: '.$image->getMessage());
}

// Render the Captcha
header('Content-Type: image/png');
imagepng($image);
?>