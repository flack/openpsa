<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
echo "<h1>" . $data['l10n']->get('handlers') . "</h1>\n";
if (count($data['request_switch_info']) > 0) {
    echo "<p>" . $data['l10n']->get('available urls') . "</p>\n";

    echo "<dl>\n";
    foreach ($data['request_switch_info'] as $request_id => $request_info) {
        echo "<dt id=\"{$request_id}\">{$request_info['route']}</dt>\n";
        echo "<dd>\n";
        echo "    <table>\n";
        echo "        <tbody>\n";
        echo "            <tr>\n";
        echo "                <td class='property odd'>" . $data['l10n']->get('handler_id') . "</th>\n";
        echo "                <td class='even'>{$request_id}</td>\n";
        echo "            </tr>\n";

        if (isset($request_info['controller'])) {
            // TODO: Link to class documentation
            echo "            <tr>\n";
            echo "                <td class='property odd'>" . $data['l10n']->get('controller') . "</th>\n";
            echo "                <td class='even'>{$request_info['controller']}</td>\n";
            echo "            </tr>\n";
        }

        if (isset($request_info['action'])) {
            echo "            <tr>\n";
            echo "                <td class='property odd'>" . $data['l10n']->get('action') . "</th>\n";
            echo "                <td class='even'>{$request_info['action']}</td>\n";
            echo "            </tr>\n";
        }
        echo "        </tbody>\n";
        echo "    </table>\n";

        if (isset($request_info['info'])) {
            echo "{$request_info['info']}\n";
        }
        echo "</dd>\n";
    }
    echo "</dl>\n";
} else {
    echo "<p>" . $data['l10n']->get('no routes found') . "</p>";
}
