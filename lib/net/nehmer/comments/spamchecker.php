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

    /**
     * Check the post against possible spam filters.
     *
     * This will update post status on the background and log the information.
     */
    public static function check(net_nehmer_comments_comment $comment)
    {
        $ret = self::check_linksleeve($comment->title . ' ' . $comment->content . ' ' . $comment->author);

        if ($ret == self::HAM) {
            // Quality content
            debug_add("Linksleeve noted comment \"{$comment->title}\" ({$comment->guid}) as ham");

            $comment->moderate(net_nehmer_comments_comment::MODERATED, 'reported_not_junk', 'linksleeve');
        } elseif ($ret == self::SPAM) {
            // Spam
            debug_add("Linksleeve noted comment \"{$comment->title}\" ({$comment->guid}) as spam");

            $comment->moderate(net_nehmer_comments_comment::JUNK, 'confirmed_junk', 'linksleeve');
        }
    }

    private static function check_linksleeve($text) : int
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
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($data) . "\r\n";
        $header .= "User-Agent: Mozilla/4.0 (compatible: MSIE 7.0; Windows NT 6.0)\r\n";
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
