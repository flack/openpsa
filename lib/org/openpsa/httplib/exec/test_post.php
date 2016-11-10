<?php
midcom::get()->auth->require_valid_user();
if (!isset($_REQUEST['url'])) {
    ?>
<form method="post">
    <h2>address and data</h2>
    URL: <input name="url" value="" /><br/>
    Key => Value array (for eval())<br/>
    <textarea name="variables" rows=10 cols=40>'key' => 'value',
    </textarea>
    <h2>basic auth (optional)</h2>
    Username: <input name="username" value="" /><br/>
    Password: <input name="password" value="" /><br/>
    <input type="submit" value="post" />
</form>
<?php

} else {
    $client = new org_openpsa_httplib();
    eval("\$vars = array({$_REQUEST['variables']});");
    if (   !empty($_REQUEST['username'])
        && !empty($_REQUEST['password'])) {
        $client->basicauth['user'] = $_REQUEST['username'];
        $client->basicauth['password'] = $_REQUEST['password'];
    }
    if (!isset($vars)) {
        $display = "<h1>Error</h1>\n<p>Could not determine variables to post</p>";
    } else {
        $response = $client->post($_REQUEST['url'], $vars);
        if (!$response) {
            $display = "<h1>Error</h1>\n<p>Client error: {$client->error}</p>";
        } else {
            $display = "<h1>Success</h1>\n{$response}";
        }
    }
    echo $display;
}
?>