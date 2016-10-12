<p>
    <?php printf($data['l10n']->get('you are being redirected to %s in %s seconds'), "<a href=\"{$data['redirection_url']}\">{$data['redirection_url']}</a>", $data['redirection_speed']); ?>.
    <?php printf($data['l10n']->get('if redirection fails, %s'), "<a href=\"{$data['redirection_url']}\">" . $data['l10n']->get('click here') . "</a>"); ?>.
</p>