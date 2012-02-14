<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h1>" . midcom::get('i18n')->get_string('handlers', 'midcom.admin.help') . "</h1>\n";
if (count($data['request_switch_info']) > 0)
{
    echo "<p>" . midcom::get('i18n')->get_string('available urls', 'midcom.admin.help') . "</p>\n";

    echo "<dl>\n";
    foreach ($data['request_switch_info'] as $request_id => $request_info)
    {
        echo "<dt id=\"{$request_id}\">{$request_info['route']}</dt>\n";
        echo "<dd>\n";
        echo "    <table>\n";
        echo "        <tbody>\n";
        echo "            <tr>\n";
        echo "                <td class='property odd'>" . midcom::get('i18n')->get_string('handler_id', 'midcom.admin.help') . "</th>\n";
        echo "                <td class='even'>{$request_id}</td>\n";
        echo "            </tr>\n";

        if (isset($request_info['controller']))
        {
            // TODO: Link to class documentation
            echo "            <tr>\n";
            echo "                <td class='property odd'>" . midcom::get('i18n')->get_string('controller', 'midcom.admin.help') . "</th>\n";
            echo "                <td class='even'>{$request_info['controller']}</td>\n";
            echo "            </tr>\n";
        }

        if (isset($request_info['action']))
        {
            echo "            <tr>\n";
            echo "                <td class='property odd'>" . midcom::get('i18n')->get_string('action', 'midcom.admin.help') . "</th>\n";
            echo "                <td class='even'>{$request_info['action']}</td>\n";
            echo "            </tr>\n";
        }
        echo "        </tbody>\n";
        echo "    </table>\n";

        if (isset($request_info['info']))
        {
            echo "{$request_info['info']}\n";
        }
        echo "</dd>\n";
    }
    echo "</dl>\n";
}
else
{
    echo "<p>" . midcom::get('i18n')->get_string('no routes found', 'midcom.admin.help') . "</p>";
}
?>