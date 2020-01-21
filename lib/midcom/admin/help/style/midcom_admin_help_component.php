<?php
if (!empty($data['help_files'])) {
    echo "<h2>" . $data['l10n']->get('toc') . "</h2>\n";

    echo "<ul>\n";
    foreach ($data['help_files'] as $help_id => $file_info) {
        $uri = $data['router']->generate('help', ['component' => $data['component'], 'help_id' => $help_id]);
        echo "<li><a href=\"{$uri}\">{$file_info['subject']}</a></li>\n";
    }
    echo "</ul>\n";
}
