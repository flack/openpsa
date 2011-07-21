<?php
$response = array
(
    'success' => $data['success'],
    'action' => $data['next_action'],
    'due' => strftime('%Y-%m-%d', $data['invoice']->due),
    'new_status' => $data['invoice']->get_invoice_class(),
    'message' => $data['message']
);
echo json_encode($response)
?>