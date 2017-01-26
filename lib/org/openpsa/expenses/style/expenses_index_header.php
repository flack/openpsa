<?php
echo "<h1>" . sprintf($data['l10n']->get("expenses in week %s"), strftime("%V", $data['week_start'])) . "</h1>\n";
