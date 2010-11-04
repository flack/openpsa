<?php
/**
 * @package midcom
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 *
 */
if (!function_exists('midcom_helper_formatters_links'))
{
    function midcom_helper_formatters_links($content, $echo_results = true)
    {
        // echo $content;
        // echo "\n<br />---------<br />\n";

        $length = strlen($content);
        $start = 0;
        $end = $length;
        $blocks = explode(" ",$content);

        foreach ($blocks as $block)
        {
            if (empty($block))
            {
                continue;
            }

            $start = strpos($content, $block, $start);

            if (preg_match('/(([a-z]+:\/\/)|^(www\.))+[^<>\s]+[a-z0-9\/]/i',$block))
            {
                if (!preg_match('/([a-z]+=")+[^<>\s]+[a-z0-9\/]/i',$block))
                {
                    $end = strpos($content, $block, $start);
                    $end += strlen($block);

                    while (preg_match('/[,\.]$/', $block) )
                    {
                        $block = substr( $block, 0, -1 );
                        $end--;
                    }

                    $new_block = $block;

                    $new_block = preg_replace('/(((f|ht){1}tp:\/\/)[-a-zA-Z0-9@:%_\+.~#?&\/=]+)/i', '<a href="\\1">\\1</a>', $new_block);
                    if ($new_block == $block)
                    {
                        $new_block = preg_replace('/( www.[-a-zA-Z0-9@:%_\+.~#?&\/=]+)/i', '<a href="http://\\1">\\1</a>', $new_block);
                    }

                    _midcom_helper_formatters_replace_content($content, $new_block, $start, $end);

                    $start += strlen($new_block);
                }
            }
            else
            {
                $end = strpos($content, $block, $start);
            }

            $start += 1;
        }

        if ($echo_results)
        {
            echo $content;
        }
        else
        {
            return $content;
        }
    }
    _midcom_helper_formatters_register_filter('links');
}

if (!function_exists('midcom_helper_formatters_maillinks'))
{
    function midcom_helper_formatters_maillinks($content,$echo_results=true)
    {
        $length = strlen($content);
        $start = 0;
        $end = $length;
        $blocks = explode(" ",$content);

        foreach ($blocks as $block)
        {
            if (empty($block))
            {
                continue;
            }

            $start = strpos($content, $block, $start);

            if (preg_match('/([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})/i',$block))
            {
                if (!preg_match('/([a-z]+="mailto:)/i',$block))
                {
                    $end = strpos($content, $block, $start);
                    $end += strlen($block);

                    while (preg_match('/[,\.]$/', $block))
                    {
                        $block = substr($block, 0, -1 );
                        $end--;
                    }

                    $new_block = $block;
                    $new_block = preg_replace('/([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})/i', '<a href="mailto:\\1">\\1</a>', $new_block);

                    _midcom_helper_formatters_replace_content($content, $new_block, $start, $end);

                    $start += strlen($new_block);
                }
            }
            else
            {
                $end = strpos($content, $block, $start);
            }

            $start += 1;
        }

        if ($echo_results)
        {
            echo $content;
        }
        else
        {
            return $content;
        }
    }
    _midcom_helper_formatters_register_filter('maillinks');
}

if (!function_exists('midcom_helper_formatters_obfmaillinks'))
{
    function midcom_helper_formatters_obfmaillinks($content, $echo_results=true)
    {
        $content = midcom_helper_formatters_maillinks($content,false);

        $content = preg_replace('/<a [^>]*href="mailto:([^"]+)"[^>]*>(.*?)<\/a>/ie', '_midcom_helper_formatters_obfuscate_email_link("\\1",false)', $content);

        if ($echo_results)
        {
            echo $content;
        }
        else
        {
            return $content;
        }
    }
    _midcom_helper_formatters_register_filter('obfmaillinks');

    function _midcom_helper_formatters_obfuscate_email($email,$echo_results=true)
    {
        $obfuscated = '';

        $len = strlen($email);
        for ($i=0;$i<$len;$i++)
        {
            $obfuscated .= "&#" . ord($email[$i]);
        }

        if ($echo_results)
        {
            echo $obfuscated;
        }
        else
        {
            return $obfuscated;
        }
    }
    _midcom_helper_formatters_register_filter('obfmail','_midcom_helper_formatters_obfuscate_email');

    function _midcom_helper_formatters_obfuscate_email_link($email,$echo_results=true)
    {
        $obfuscated = _midcom_helper_formatters_obfuscate_email($email,false);

        $link = "<a href=\"mailto:{$obfuscated}\">{$obfuscated}</a>";

        if ($echo_results)
        {
            echo $link;
        }
        else
        {
            return $link;
        }
    }
    _midcom_helper_formatters_register_filter('obfmaillink','_midcom_helper_formatters_obfuscate_email_link');
}

if (!function_exists('midcom_helper_formatters_plaintext'))
{
    function midcom_helper_formatters_plaintext($content)
    {
        // echo $content;
        // echo "\n<br />---------<br />\n";

        $search = array
        (
            "/\r/",                                  // Non-legal carriage return
            "/[\n\t]+/",                             // Newlines and tabs
            '/[ ]{2,}/',                             // Runs of spaces, pre-handling
            '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
            '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
            '/<h[123][^>]*>(.*?)<\/h[123]>/ie',      // H1 - H3
            '/<h[456][^>]*>(.*?)<\/h[456]>/ie',      // H4 - H6
            '/<p[^>]*>/i',                           // <P>
            '/<br[^>]*>/i',                          // <br>
            '/<b[^>]*>(.*?)<\/b>/ie',                // <b>
            '/<strong[^>]*>(.*?)<\/strong>/ie',      // <strong>
            '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
            '/<em[^>]*>(.*?)<\/em>/i',               // <em>
            '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
            '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
            '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
            '/<li[^>]*>/i',                          // <li>
            '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/ie', // <a href="">
            '/<hr[^>]*>/i',                          // <hr>
            '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
            '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
            '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
            '/<th[^>]*>(.*?)<\/th>/ie',              // <th> and </th>
            '/&(nbsp|#160);/i',                      // Non-breaking space
            '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
            '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
            '/&gt;/i',                               // Greater-than
            '/&lt;/i',                               // Less-than
            '/&(amp|#38);/i',                        // Ampersand
            '/&(copy|#169);/i',                      // Copyright
            '/&(trade|#8482|#153);/i',               // Trademark
            '/&(reg|#174);/i',                       // Registered
            '/&(mdash|#151|#8212);/i',               // mdash
            '/&(ndash|minus|#8211|#8722);/i',        // ndash
            '/&(bull|#149|#8226);/i',                // Bullet
            '/&(pound|#163);/i',                     // Pound sign
            '/&(euro|#8364);/i',                     // Euro sign
            '/&[^&;]+;/i',                           // Unknown/unhandled entities
            '/[ ]{2,}/'                              // Runs of spaces, post-handling
        );

        $replace = array
        (
            '',                                     // Non-legal carriage return
            ' ',                                    // Newlines and tabs
            ' ',                                    // Runs of spaces, pre-handling
            '',                                     // <script>s -- which strip_tags supposedly has problems with
            '',                                     // <style>s -- which strip_tags supposedly has problems with
            "strtoupper(\"\n\n\\1\n\n\")",          // H1 - H3
            "ucwords(\"\n\n\\1\n\n\")",             // H4 - H6
            "\n\n\t",                               // <P>
            "\n",                                   // <br>
            'strtoupper("\\1")',                    // <b>
            'strtoupper("\\1")',                    // <strong>
            '_\\1_',                                // <i>
            '_\\1_',                                // <em>
            "\n\n",                                 // <ul> and </ul>
            "\n\n",                                 // <ol> and </ol>
            "\t* \\1\n",                            // <li> and </li>
            "\n\t* ",                               // <li>
            '"\\1"',                                // <a href="">
            "\n-------------------------\n",        // <hr>
            "\n\n",                                 // <table> and </table>
            "\n",                                   // <tr> and </tr>
            "\t\t\\1\n",                            // <td> and </td>
            "strtoupper(\"\t\t\\1\n\")",            // <th> and </th>
            ' ',                                    // Non-breaking space
            '"',                                    // Double quotes
            "'",                                    // Single quotes
            '>',                                    // Greater-than
            '<',                                    // Less-than
            '&',                                    // Ampersand
            '(c)',                                  // Copyright
            '(tm)',                                 // Trademark
            '(R)',                                  // Registered
            '--',                                   // mdash
            '-',                                    // ndash
            '*',                                    // Bullet
            '£',                                    // Pound sign
            'EUR',                                  // Euro sign. € ?
            '',                                     // Unknown/unhandled entities
            ' '                                     // Runs of spaces, post-handling
        );

        $formatted = trim(stripslashes($content));
        $formatted = preg_replace($search, $replace, $formatted);
        $formatted = strip_tags($formatted);

        $formatted = preg_replace("/\n\s+\n/", "\n\n", $formatted);
        $formatted = preg_replace("/[\n]{3,}/", "\n\n", $formatted);

        echo $formatted;
    }
    _midcom_helper_formatters_register_filter('plaintext');
}

if (!function_exists('midcom_helper_formatters_noimages'))
{
    function midcom_helper_formatters_noimages($content, $echo_results=true)
    {
        // echo "{$content}";
        // echo "<br/>------<br/>";

        $search = array
        (
            '/<img [^>]*src="([^"]+)"[^>]*alt="([^"]+)"[^>]*>/is',
            '/<img [^>]*src="([^"]+)"[^>]*[^>]*>/is',
               '/<img [^>]*src=\'([^"]+)\'[^>]*alt="([^"]+)"[^>]*>/is',
               '/<img [^>]*src=\'([^"]+)\'[^>]*alt=\'([^"]+)\'[^>]*>/is',
               '/<img [^>]*src=\'([^"]+)\'[^>]*[^>]*>/is',
        );

        foreach ($search as $re)
        {
            $content = preg_replace_callback($re, "_midcom_helper_formatters_noimages_link", $content);
        }

        if ($echo_results)
        {
            echo $content;
        }
        else
        {
            return $content;
        }
    }
    _midcom_helper_formatters_register_filter('noimages');

    function _midcom_helper_formatters_noimages_link($matches)
    {
        $url = $matches[1];
        $title = '';
        if (isset($matches[2]))
        {
            $title = $matches[2];
        }

        $link = '';
        if (empty($url))
        {
            return $link;
        }

        if (   empty($title)
            || $title == $url)
        {
            $url_parts = explode('/',$url);
            $title = $url_parts[(count($url_parts)-1)];
        }

        $title_prefix = $_MIDCOM->i18n->get_string('image','midcom');
        $link = "<a href=\"{$url}\" title=\"{$title}\">[{$title_prefix}:{$title}]</a>";

        return $link;
    }
}

/**
 * Chained
*/

function midcom_helper_formatters_links_and_obfmaillinks($content)
{
    $content = midcom_helper_formatters_obfmaillinks($content, false);
    $content = midcom_helper_formatters_links($content, false);

    echo $content;
}
_midcom_helper_formatters_register_filter('linksobfmails','midcom_helper_formatters_links_and_obfmaillinks');

function midcom_helper_formatters_links_and_maillinks($content)
{
    $content = midcom_helper_formatters_maillinks($content, false);//mgd_format($content, 'xmaillink');
    $content = midcom_helper_formatters_links($content, false);//mgd_format($content, 'xlinks');

    echo $content;
}
_midcom_helper_formatters_register_filter('linksmails','midcom_helper_formatters_links_and_maillinks');

function midcom_helper_formatters_automatic($content)
{
    if (   isset($GLOBALS['midcom_config']['auto_formatter'])
        && !empty($GLOBALS['midcom_config']['auto_formatter']))
    {
        foreach ($GLOBALS['midcom_config']['auto_formatter'] as $formatter)
        {
            if (in_array($formatter, array('h', 'F')))
            {
                $content = mgd_format($content, $formatter);
            }
            else
            {
                $content = $formatter($content, false);
            }
        }
    }

    echo $content;
}
_midcom_helper_formatters_register_filter('af','midcom_helper_formatters_automatic');

/**
 * Helpers
 */

function _midcom_helper_formatters_register_filter($name, $method=null)
{
    if ($method === null)
    {
        $method = "midcom_helper_formatters_{$name}";
    }

    mgd_register_filter($name, $method);
}

function _midcom_helper_formatters_replace_content(&$in, $replace, $start, $end)
{
    $begin = substr($in, 0, $start);
    $end   = substr($in, $end, strlen($in)-$end);
    $in    = $begin.$replace.$end;
}

if (!function_exists('midcom_helper_toc_formatter'))
{
    /**
     * This function parses HTML content and looks for header tags, making index of them.
     *
     * What exactly it does is looks for all H<num> tags and converts them to named
     * anchors, and prepends a list of links to them to the start of HTML.
     *
     * @todo Parse the heading structure to create OL subtrees based on their relative levels
     */
    function midcom_helper_toc_formatter_prefix($level)
    {
        $prefix = '';
        for ($i=0; $i < $level; $i++)
        {
            $prefix .= '    ';
        }
        return $prefix;
    }
    function midcom_helper_toc_formatter($data)
    {
        if (!preg_match_all("/(<(h([1-9][0-9]*))[^>]*?>)(.*?)(<\/\\2>)/i", $data, $headings))
        {
            echo mgd_format($data, 'h');
            return;
        }

        $current_tag_level = false;
        $current_list_level = 1;
        echo "\n<ol class=\"midcom_helper_toc_formatter level_{$current_list_level}\">\n";
        foreach ($headings[4] as $key => $heading)
        {
            $anchor = md5($heading);
            $tag_level =& $headings[3][$key];
            $heading_code =& $headings[0][$key];
            $heading_tag =& $headings[2][$key];
            $heading_new_code = "<a name='{$anchor}'></a>{$heading_code}";
            $data = str_replace($heading_code, $heading_new_code, $data);
            $prefix = midcom_helper_toc_formatter_prefix($current_list_level);
            if ($current_tag_level === false)
            {
                $current_tag_level = $tag_level;
            }
            if ($tag_level > $current_tag_level)
            {
                for ($i = $current_tag_level; $i < $tag_level; $i++)
                {
                    $current_tag_level = $tag_level;
                    $current_list_level++;
                    echo "{$prefix}<ol class=\"level_{$current_list_level}\">\n";
                    $prefix .= '    ';
                }
            }
            if ($tag_level < $current_tag_level)
            {
                for ($i = $current_tag_level; $i > $tag_level; $i--)
                {
                    $current_tag_level = $tag_level;
                    if ($current_list_level > 1)
                    {
                        $current_list_level--;
                        $prefix = midcom_helper_toc_formatter_prefix($current_list_level);
                        echo "{$prefix}</ol>\n";
                    }
                }
            }
            echo "{$prefix}<li class='{$heading_tag}'><a href='#{$anchor}'>" . strip_tags($heading) .  "</a></li>\n";
        }
        for ($i = $current_list_level; $i > 0; $i--)
        {
            $prefix = midcom_helper_toc_formatter_prefix($i-1);
            echo "{$prefix}</ol>\n";
        }

        echo mgd_format($data, 'h');
    }

    /**
     * Register the formatter as "toc", meaning that &(variable:xtoc); will filter through it
     */
    mgd_register_filter('toc', 'midcom_helper_toc_formatter');
}
?>