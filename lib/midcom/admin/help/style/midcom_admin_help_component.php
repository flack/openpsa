<?php
if (!empty($data['help_files'])) {
    echo "<h2>" . $data['l10n']->get('toc') . "</h2>\n";

    echo "<ul>\n";
    foreach ($data['help_files'] as $file_info) {
        $uri_string = basename($file_info['path']);
        $uri_parts = explode('.', $uri_string);
        $uri = $data['router']->generate('help', ['component' => $data['component'], 'help_id' => $uri_parts[0]]);
        echo "<li><a href=\"{$uri}\">{$file_info['subject']}</a></li>\n";
    }
    echo "</ul>\n";
}
