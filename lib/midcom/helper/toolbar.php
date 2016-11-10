<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a generic toolbar class. It supports enabling
 * and disabling of buttons, icons and hover-helptexts (currently
 * rendered using TITLE tags).
 *
 * A single button in the toolbar is represented using an associative
 * array with the following elements:
 *
 * <code>
 * $item = Array (
 *     [MIDCOM_TOOLBAR_URL] => $url,
 *     [MIDCOM_TOOLBAR_LABEL] => $label,
 *     [MIDCOM_TOOLBAR_HELPTEXT] => $helptext,
 *     [MIDCOM_TOOLBAR_ICON] => $icon,
 *     [MIDCOM_TOOLBAR_ENABLED] => $enabled,
 *     [MIDCOM_TOOLBAR_HIDDEN] => $hidden
 *     [MIDCOM_TOOLBAR_OPTIONS] => array $options,
 *     [MIDCOM_TOOLBAR_SUBMENU] => midcom_helper_toolbar $submenu,
 *     [MIDCOM_TOOLBAR_ACCESSKEY] => (char) 'a',
 *     [MIDCOM_TOOLBAR_POST] => true,
 *     [MIDCOM_TOOLBAR_POST_HIDDENARGS] => array $args,
 * );
 * </code>
 *
 * The URL parameter can be interpreted in three different ways:
 * If it is a relative URL (not starting with 'http[s]://' or at least
 * a '/') it will be interpreted relative to the current Anchor
 * Prefix as defined in the active MidCOM context. Otherwise, the URL
 * is used as-is. Note, that the Anchor-Prefix is appended immediately
 * when the item is added, not when the toolbar is rendered.
 *
 * The original URL (before prepending anything) is stored internally;
 * so in all places where you reference an element by-URL, you can use
 * the original URL if you wish (actually, both URLs are recognized
 * during the translation into an id).
 *
 * The label is the text shown as the button, the helptext is used as
 * TITLE value to the anchor, and will be shown when hovering over the
 * link therefore. Set it to null, to suppress this feature (this is the
 * default).
 *
 * The icon is a relative URL within the static MidCOM tree, for example
 * 'stock-icons/16x16/attach.png'. Set it to null, to suppress the display
 * of an icon (this is the default)
 *
 * By default, as shown below, the toolbar system renders a standard Hyperlink.
 * If you set MIDCOM_TOOLBAR_POST to true however, a form is used instead.
 * This is important if you want to provide operations directly behind the
 * toolbar entries - you'd run into problems with HTTP Link Prefetching
 * otherwise. It is also useful if you want to pass complex operations
 * to the URL target, as the option MIDCOM_TOOLBAR_POST_HIDDENARGS allows
 * you to add HIDDEN variables to the form. These arguments will be automatically
 * run through htmlspecialchars when rendering. By default, standard links will
 * be rendered, POST versions will only be used if explicitly requested.
 *
 * Note, that while this should prevent link prefetching on the POST entries,
 * this is a big should. Due to its lack of standardization, it is strongly
 * recommended to check for a POST request when processing such toolbar
 * targets, using something like this:
 *
 * <code>
 * if ($_SERVER['REQUEST_METHOD'] != 'post')
 * {
 *     throw new midcom_error_forbidden('Only POST requests are allowed here.');
 * }
 * </code>
 *
 * The enabled boolean flag is set to true (the default) if the link should
 * be clickable, or to false otherwise.
 *
 * The hidden boolean flag is very similar to the enabled one: Instead of
 * having unclickable links, it just hides the toolbar button entirely.
 * This is useful for access control checks, where you want to completely
 * hide items without access. The difference from just not adding the
 * corresponding variable is that you can have a consistent set of
 * toolbar options in a "template" which you just need to tweak by
 * setting this flag. (Note, that there is no explicit access
 * control checks in the toolbar helper itself, as this would mean that
 * the corresponding content objects need to be passed into the toolbar,
 * which is not feasible with the large number of toolbars in use in NAP
 * for example.)
 *
 * The midcom_toolbar_submenu can be used to create nested submenus by adding a pointer
 * to a new toolbar object.
 *
 * The toolbar gets rendered as an unordered list, letting you define the
 * CSS id and/or class tags of the list itself. The default class for
 * example used the well-known horizontal-UL approach to transform this
 * into a real toolbar. The output of the draw call therefore looks like
 * this:
 *
 * The <b>accesskey</b> option is used to assign an accesskey to the toolbar item.
 * It will be rendered in the toolbar text as either underlining the key or stated in
 * parentheses behind the text.
 *
 * <pre>
 * &lt;ul [class="$class"] [id="$id"]&gt;
 *   &lt;li class="(enabled|disabled)"&gt;
 *     [&lt;a href="$url" [title="$helptext"] [ $options as $key =&gt; $val ]&gt;]
 *       [&lt;img src="$calculated_image_url"&gt;]
 *       $label
 *      [new submenu here]
 *     [&lt;/a&gt;]
 *   &lt;/li&gt;
 * &lt;/ul&gt;
 * </pre>
 *
 * Both class and id can be null, indicating no style should be selected.
 * By default, the class will use "midcom_toolbar" and no id style, which
 * will yield a traditional MidCOM toolbar. Of course, the
 * style sheet must be loaded to support this. Note, that this style assumes
 * 16x16 height icons in its toolbar rendering. Larger or smaller icons
 * will look ugly in the layout.
 *
 * The options array. You can use the options array to make simple changes to the toolbar items.
 * Here's a quick example to remove the underlining.
 * <code>
 * foreach ($toolbar->items as $index => $item) {
 *     $toolbar->items[$index][MIDCOM_TOOLBAR_OPTIONS] = array( "style" => "text-decoration:none;");
 * }
 * </code>
 * This will add style="text-decoration:none;" to all the links in the toolbar.
 *
 * @package midcom.helper
 */
class midcom_helper_toolbar
{
    /**
     * The CSS ID-Style rule that should be used for the toolbar.
     * Set to null if none should be used.
     *
     * @var string
     */
    public $id_style;

    /**
     * The CSS class-Style rule that should be used for the toolbar.
     * Set to null if none should be used.
     *
     * @var string
     */
    public $class_style;

    /**
     * The toolbar's label
     *
     * @var string
     */
    protected $label;

    /**
     * The items in the toolbar.
     *
     * The array consists of Arrays outlined in the class introduction.
     * You can modify existing items in this collection but you should use
     * the class methods to add or delete existing items. Also note that
     * relative URLs are processed upon the invocation of add_item(), if
     * you change URL manually, you have to ensure a valid URL by yourself
     * or use update_item_url, which is recommended.
     *
     * @var Array
     */
    public $items = array();

    /**
     * Allow our users to add arbitrary data to the toolbar.
     *
     * This is for example used to track which items have been added to a toolbar
     * when it is possible that the adders are called repeatedly.
     *
     * The entries should be namespaced according to the usual MidCOM
     * Namespacing rules.
     *
     * @var Array
     */
    public $customdata = array();

    /**
     * Basic constructor, initializes the class and sets defaults for the
     * CSS style if omitted.
     *
     * Note that the styles can be changed after construction by updating
     * the id_style and class_style members.
     *
     * @param string $class_style The class style tag for the UL.
     * @param string $id_style The id style tag for the UL.
     */
    public function __construct($class_style = 'midcom_toolbar', $id_style = null)
    {
        $this->id_style = $id_style;
        $this->class_style = $class_style;
    }

    /**
     *
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     *
     * @param string $label
     */
    public function set_label($label)
    {
        $this->label = $label;
    }

    /**
     * Add a help item to the toolbar.
     *
     * @param string $help_id Name of the help file in component documentation directory.
     * @param string $component Component to display the help from
     * @param string $label Label for the help link
     * @param string $anchor Anchor ("a name" or "id" in HTML page) to link to
     */
    public function add_help_item($help_id, $component = null, $label = null, $anchor = null, $before = -1)
    {
        if (is_null($component))
        {
            $uri = "__ais/help/{$help_id}/";
        }
        else
        {
            $uri = "__ais/help/{$component}/{$help_id}/";
        }

        if (!is_null($anchor))
        {
            $uri .= "#{$anchor}";
        }

        if (is_null($label))
        {
            $label = midcom::get()->i18n->get_string('help', 'midcom.admin.help');
        }

        $this->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $uri,
                MIDCOM_TOOLBAR_LABEL => $label,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_help-agent.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'h',
                MIDCOM_TOOLBAR_OPTIONS => array
                (
                    'target' => '_blank',
                ),
            ),
            $before
        );
    }

    /**
     * Add an item to the toolbar.
     *
     * Set before to the index of the element before which you want to insert
     * the item or use -1 if you want to append an item. Alternatively,
     * instead of specifying an index, you can specify a URL instead.
     *
     * This member will process the URL and append the anchor prefix in case
     * the URL is a relative one.
     *
     * Invalid positions will result in a MidCOM Error.
     *
     * @param array $item The item to add.
     * @param mixed $before The index before which the item should be inserted.
     *     Use -1 for appending at the end, use a string to insert
     *     it before a URL, an integer will insert it before a
     *     given index.
     * @see midcom_helper_toolbar::get_index_from_url()
     * @see midcom_helper_toolbar::_check_index()
     * @see midcom_helper_toolbar::clean_item()
     */
    public function add_item($item, $before = -1)
    {
        if ($before != -1)
        {
            $before = $this->_check_index($before, false);
        }
        $item = $this->clean_item($item);

        if ($before == -1)
        {
            $this->items[] = $item;
        }
        else if ($before == 0)
        {
            array_unshift($this->items, $item);
        }
        else
        {
            $start = array_slice($this->items, 0, $before - 1);
            $start[] = $item;
            $this->items = array_merge($start, array_slice($this->items, $before));
        }
    }

    /**
     * Convenience shortcut to add multiple buttons at the same item
     *
     * @param array $items The items to add.
     * @param mixed $before The index before which the item should be inserted.
     *     Use -1 for appending at the end, use a string to insert
     *     it before a URL, an integer will insert it before a
     *     given index.
     */
    public function add_items(array $items, $before = -1)
    {
        foreach ($items as $item)
        {
            $this->add_item($item, $before);
        }
    }

    /**
     * Add an item to another item by either adding the item to the MIDCOM_TOOLBAR_SUBMENU
     * or creating a new subtoolbar and adding the item there.
     *
     * @param array item
     * @param int toolbar itemindex.
     * @return boolean false if insert failed.
     */
    public function add_item_to_index($item, $index)
    {
        $item = $this->clean_item($item);
        if (!array_key_exists($index, $this->items))
        {
            debug_add("Insert of item {$item[MIDCOM_TOOLBAR_LABEL]} into index $index failed");
            return false;
        }

        if (empty($this->items[$index][MIDCOM_TOOLBAR_SUBMENU]))
        {
            $this->items[$index][MIDCOM_TOOLBAR_SUBMENU] = new midcom_helper_toolbar($this->class_style, $this->id_style);
        }

        $this->items[$index][MIDCOM_TOOLBAR_SUBMENU]->items[] = $item;

        return true;
    }

    /**
     * Clean up an item that is added, making sure that the item has all the
     * needed options and indexes.
     *
     * @param array the item to be cleaned
     * @return array the cleaned item.
     */
    public function clean_item($item)
    {
        static $used_access_keys = array();

        $defaults = array
        (
            MIDCOM_TOOLBAR_URL => './',
            MIDCOM_TOOLBAR_OPTIONS => array(),
            MIDCOM_TOOLBAR_HIDDEN => false,
            MIDCOM_TOOLBAR_HELPTEXT => '',
            MIDCOM_TOOLBAR_ICON => null,
            MIDCOM_TOOLBAR_ENABLED => true,
            MIDCOM_TOOLBAR_POST => false,
            MIDCOM_TOOLBAR_POST_HIDDENARGS => array(),
            MIDCOM_TOOLBAR_ACCESSKEY => null
        );
        // we can't use array_merge unfortunately, because the constants are numeric..
        foreach ($defaults as $key => $value)
        {
            if (!array_key_exists($key, $item))
            {
                $item[$key] = $value;
            }
        }

        if (   !empty($item[MIDCOM_TOOLBAR_ACCESSKEY])
            && !array_key_exists($item[MIDCOM_TOOLBAR_ACCESSKEY], $used_access_keys))
        {
            // We have valid access key, add it to help text
            if (   isset($_SERVER['HTTP_USER_AGENT'])
                && strstr($_SERVER['HTTP_USER_AGENT'], 'Macintosh'))
            {
                // Mac users
                $hotkey = 'Ctrl-' . strtoupper($item[MIDCOM_TOOLBAR_ACCESSKEY]);
            }
            else
            {
                // Windows and Linux clients
                $hotkey = 'Alt-' . strtoupper($item[MIDCOM_TOOLBAR_ACCESSKEY]);
            }

            if ($item[MIDCOM_TOOLBAR_HELPTEXT] == '')
            {
                $item[MIDCOM_TOOLBAR_HELPTEXT] = $hotkey;
            }
            else
            {
                $item[MIDCOM_TOOLBAR_HELPTEXT] .= " ({$hotkey})";
            }
        }

        $this->set_url($item, $item[MIDCOM_TOOLBAR_URL]);
        return $item;
    }

    private function set_url(array &$item, $url)
    {
        $item[MIDCOM_TOOLBAR__ORIGINAL_URL] = $url;
        if (   (   empty($item[MIDCOM_TOOLBAR_OPTIONS]["rel"])
                // Some items may want to keep their links unmutilated
                || $item[MIDCOM_TOOLBAR_OPTIONS]["rel"] != "directlink")
            && substr($url, 0, 1) != '/'
            && !preg_match('|^https?://|', $url))
        {
            $url = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . $url;
        }
        $item[MIDCOM_TOOLBAR_URL] = $url;
    }

    /**
     * Removes a toolbar item based on its index or its URL
     *
     * It will trigger a MidCOM Error upon an invalid index.
     *
     * @param mixed $index The (integer) index or URL to remove.
     * @see midcom_helper_toolbar::get_index_from_url()
     * @see midcom_helper_toolbar::_check_index()
     */
    public function remove_item($index)
    {
        $index = $this->_check_index($index);

        if ($index == 0)
        {
            array_shift($this->items);
        }
        else if ($index == count($this->items) -1)
        {
            array_pop($this->items);
        }
        else
        {
            $this->items = array_merge(array_slice($this->items, 0, $index - 1),
                array_slice($this->items, $index + 1));
        }
    }

    /**
     * Clears the complete toolbar.
     */
    public function remove_all_items()
    {
        $this->items = array();
    }

    /**
     * Moves an item on place upwards in the list.
     *
     * This will only work, of course, if you are not working with the top element.
     *
     * @param mixed $index The integer index or URL of the item to move upwards.
     */
    public function move_item_up($index)
    {
        if ($index == 0)
        {
            throw new midcom_error('Cannot move the top element upwards.');
        }
        $index = $this->_check_index($index);

        $tmp = $this->items[$index];
        $this->items[$index] = $this->items[$index - 1];
        $this->items[$index - 1] = $tmp;
    }

    /**
     * Moves an item on place downwards in the list.
     *
     * This will only work, of course, if you are not working with the bottom element.
     *
     * @param mixed $index The integer index or URL of the item to move downwards.
     */
    public function move_item_down($index)
    {
        if ($index == (count($this->items) - 1))
        {
            throw new midcom_error('Cannot move the bottom element downwards.');
        }
        $index = $this->_check_index($index);

        $tmp = $this->items[$index];
        $this->items[$index] = $this->items[$index + 1];
        $this->items[$index + 1] = $tmp;
    }

    /**
     * Set's an item's enabled flag to true.
     *
     * @param mixed $index The integer index or URL of the item to enable.
     */
    public function enable_item($index)
    {
        $index = $this->_check_index($index);
        $this->items[$index][MIDCOM_TOOLBAR_ENABLED] = true;
    }

    /**
     * Set's an item's enabled flag to false.
     *
     * @param mixed $index The integer index or URL of the item to disable.
     */
    public function disable_item($index)
    {
        $index = $this->_check_index($index, false);

        if (is_null($index))
        {
            return false;
        }

        $this->items[$index][MIDCOM_TOOLBAR_ENABLED] = false;
    }

    /**
     * Set's an item's hidden flag to true.
     *
     * @param mixed $index The integer index or URL of the item to hide.
     */
    public function hide_item($index)
    {
        $index = $this->_check_index($index, false);

        if (is_null($index))
        {
            return false;
        }

        $this->items[$index][MIDCOM_TOOLBAR_HIDDEN] = true;
    }

    /**
     * Set's an item's hidden flag to false.
     *
     * @param mixed $index The integer index or URL of the item to show.
     */
    public function show_item($index)
    {
        $index = $this->_check_index($index);
        $this->items[$index][MIDCOM_TOOLBAR_HIDDEN] = false;
    }

    /**
     * Updates an items URL using the same rules as in add_item.
     *
     * @param mixed $index The integer index or URL of the item to update.
     * @param string $url The new URL to set.
     * @see midcom_helper_toolbar::get_index_from_url()
     * @see midcom_helper_toolbar::_check_index()
     * @see midcom_helper_toolbar::add_item()
     */
    public function update_item_url($index, $url)
    {
        $index = $this->_check_index($index);
        $this->set_url($this->items[$index], $url);
    }

    /**
     * Renders the toolbar and returns it as a string.
     *
     * @return string The rendered toolbar.
     */
    public function render()
    {
        $visible_items = array_filter($this->items, function ($item)
        {
            return !$item[MIDCOM_TOOLBAR_HIDDEN];
        });

        if (count($visible_items) == 0)
        {
            debug_add('midcom_helper_toolbar: Tried to render an empty toolbar, returning an empty string.');
            return '';
        }

        // List header
        $output = '<ul';
        if (!is_null($this->class_style))
        {
            $output .= " class='{$this->class_style}'";
        }
        if (!is_null($this->id_style))
        {
            $output .= " id='{$this->id_style}'";
        }
        $output .= '>';

        $last = count($visible_items);
        $first_class = ($last === 1) ? 'only_item' : 'first_item';
        // List items
        foreach ($visible_items as $i => $item)
        {
            $output .= '<li class="';
            if ($i == 0)
            {
                $output .= $first_class .  ' ';
            }
            else if ($i == $last)
            {
                $output .= 'last_item ';
            }

            if ($item[MIDCOM_TOOLBAR_ENABLED])
            {
                $output .= 'enabled">';
            }
            else
            {
                $output .= 'disabled">';
            }

            if ($item[MIDCOM_TOOLBAR_POST])
            {
                $output .= $this->_render_post_item($item);
            }
            else
            {
                $output .= $this->_render_link_item($item);
            }

            $output .= '</li>';
        }

        // List footer
        $output .= '</ul>';

        return $output;
    }

    /**
     * Generate a label for the item that includes its accesskey
     *
     * @param array $item The item to label
     * @return string Item's label to display
     */
    private function _generate_item_label($item)
    {
        $label = htmlentities($item[MIDCOM_TOOLBAR_LABEL], ENT_COMPAT, "UTF-8");

        if (!empty($item[MIDCOM_TOOLBAR_ACCESSKEY]))
        {
            // Try finding uppercase version of the accesskey first
            $accesskey = strtoupper($item[MIDCOM_TOOLBAR_ACCESSKEY]);
            $position = strpos($label, $accesskey);
            if (   $position === false
                && midcom::get()->i18n->get_current_language() == 'en')
            {
                // Try lowercase, too
                $accesskey = strtolower($accesskey);
                $position = strpos($label, $accesskey);
            }
            if ($position !== false)
            {
                $label = substr_replace($label, "<span style=\"text-decoration: underline;\">{$accesskey}</span>", $position, 1);
            }
        }

        return $label;
    }

    /**
     * Render a regular a href... based link target.
     *
     * @param array $item The item to render
     * @return string The rendered item
     */
    private function _render_link_item($item)
    {
        $output = '';
        $attributes = $this->get_item_attributes($item);

        if ($item[MIDCOM_TOOLBAR_ENABLED])
        {
            $tagname = 'a';
            $attributes['href'] = $item[MIDCOM_TOOLBAR_URL];
        }
        else
        {
            $tagname = !empty($attributes['title']) ? 'abbr' : 'span';
        }

        $output .= '<' . $tagname;
        foreach ($attributes as $key => $val)
        {
            $output .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        $output .= '>';

        if (!is_null($item[MIDCOM_TOOLBAR_ICON]))
        {
            $url = MIDCOM_STATIC_URL . '/' . $item[MIDCOM_TOOLBAR_ICON];
            $output .= "<img src='{$url}' alt='' />";
        }

        $output .= '&nbsp;<span class="toolbar_label">' . $this->_generate_item_label($item) . "</span>";
        $output .= '</' . $tagname . '>';

        if (!empty($item[MIDCOM_TOOLBAR_SUBMENU]))
        {
            $output .= $item[MIDCOM_TOOLBAR_SUBMENU]->render();
        }

        return $output;
    }

    private function get_item_attributes(array $item)
    {
        $attributes = ($item[MIDCOM_TOOLBAR_ENABLED]) ? $item[MIDCOM_TOOLBAR_OPTIONS] : array();

        if (!is_null($item[MIDCOM_TOOLBAR_HELPTEXT]))
        {
            $attributes['title'] = $item[MIDCOM_TOOLBAR_HELPTEXT];
        }

        if (   $item[MIDCOM_TOOLBAR_ENABLED]
            && !is_null($item[MIDCOM_TOOLBAR_ACCESSKEY]))
        {
            $attributes['class'] = 'accesskey';
            $attributes['accesskey'] = $item[MIDCOM_TOOLBAR_ACCESSKEY];
        }
        return $attributes;
    }

    /**
     * Render a form based link target.
     *
     * @param array $item The item to render
     * @return string The rendered item
     */
    private function _render_post_item($item)
    {
        $output = '';

        if ($item[MIDCOM_TOOLBAR_ENABLED])
        {
            $output .= "<form method=\"post\" action=\"{$item[MIDCOM_TOOLBAR_URL]}\">";
            $output .= "<div><button type=\"submit\" name=\"midcom_helper_toolbar_submit\"";

            foreach ($this->get_item_attributes($item) as $key => $val)
            {
                $output .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
            }
            $output .= '>';
        }

        if ($item[MIDCOM_TOOLBAR_ICON])
        {
            $url = MIDCOM_STATIC_URL . "/{$item[MIDCOM_TOOLBAR_ICON]}";
            $output .= "<img src=\"{$url}\" alt=\"\" title=\"{$item[MIDCOM_TOOLBAR_HELPTEXT]}\" />";
        }

        $label = $this->_generate_item_label($item);
        $output .= " {$label}";

        if ($item[MIDCOM_TOOLBAR_ENABLED])
        {
            $output .= '</button>';
            foreach ($item[MIDCOM_TOOLBAR_POST_HIDDENARGS] as $key => $value)
            {
                $key = htmlspecialchars($key);
                $value = htmlspecialchars($value);
                $output .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>";
            }
            $output .= '</div></form>';
        }

        if (!empty($item[MIDCOM_TOOLBAR_SUBMENU]))
        {
            $output .= $item[MIDCOM_TOOLBAR_SUBMENU]->render();
        }

        return $output;
    }

    /**
     * Traverse all available items and return the first
     * element whose URL matches the value passed to the function.
     *
     * Note, that if two items point to the same URL, only the first one
     * will be reported.
     *
     * @param string $url The url to search in the list.
     * @return int The index of the item or null, if not found.
     */
    public function get_index_from_url($url)
    {
        foreach ($this->items as $i => $item)
        {
            if (   $item[MIDCOM_TOOLBAR_URL] == $url
                || $item[MIDCOM_TOOLBAR__ORIGINAL_URL] == $url)
            {
                return $i;
            }
        }
        return null;
    }

    /**
     * Check an index for validity.
     *
     * It will automatically convert a string-based URL into an
     * Index (if possible); if the URL can't be found, it will
     * also trigger an error. The translated URL is returned by the
     * function.
     *
     * @param mixed $index The integer index or URL to check
     * @param boolean $raise_error Whether we should raise an error on missing item
     * @throws midcom_error
     * @return int $index The valid index (possibly translated from the URL) or null on missing index.
     */
    private function _check_index($index, $raise_error = true)
    {
        if (is_string($index))
        {
            $url = $index;
            debug_add("Translating the URL '{$url}' into an index.");
            $index = $this->get_index_from_url($url);
            if (is_null($index))
            {
                debug_add("Invalid URL '{$url}', URL not found.", MIDCOM_LOG_ERROR);

                if ($raise_error)
                {
                    throw new midcom_error("Invalid URL '{$url}', URL not found.");
                }
                return null;
            }
        }
        if ($index >= count($this->items))
        {
            throw new midcom_error("Invalid index {$index}, it is off-the-end.");
        }
        if ($index < 0)
        {
            throw new midcom_error("Invalid index {$index}, it is negative.");
        }
        return $index;
    }

    /**
     * Binds this toolbar instance to a DBA content object using the MidCOM toolbar service.
     *
     * @param DBAObject $object The DBA class instance to bind to.
     * @see midcom_services_toolbars
     */
    public function bind_to($object)
    {
        midcom::get()->toolbars->bind_toolbar_to_object($this, $object);
    }
}
