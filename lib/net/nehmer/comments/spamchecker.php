<?php
/**
 * @package net.nehmer.comments
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for spam checks
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_spamchecker
{
    const ERROR = -1;
    const SPAM = 0;
    const HAM = 1;

    public static function check_linksleeve($text)
    {
        $data = 'content=' . $text;
        $buf = "";

        $fp = fsockopen("www.linksleeve.org", 80, $errno, $errstr, 30);
        if ($fp === false) {
            debug_add('Connection failure: ' . $errstr, MIDCOM_LOG_WARN);
            return self::ERROR;
        }
        $header  = "POST /pslv.php HTTP/1.0\r\n";
        $header .= "Host: www.linksleeve.org\r\n";
        $header .= "Content-type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-length: " . strlen($data) . "\r\n";
        $header .= "User-agent: Mozilla/4.0 (compatible: MSIE 7.0; Windows NT 6.0)\r\n";
        $header .= "Connection: close\r\n\r\n";
        $header .= $data;

        fwrite($fp, $header, strlen($header));

        while (!feof($fp)) {
            $buf .= fgets($fp, 128);
        }

        fclose($fp);

        if (!stristr($buf, "-slv-1-/slv-")) {
            return self::SPAM;
        }
        return self::HAM;
    }
}
