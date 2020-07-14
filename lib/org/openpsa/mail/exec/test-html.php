<?php
midcom::get()->auth->require_admin_user();

if (   empty($_POST['to'])
    || empty($_POST['from'])) {
    ?>
    <h2>Send test email</h2>
    <form method="post">
        <p>
            Backend: <select name="backend">
                        <option value="try_default">Component default(s)</option>
                        <option value="mail_smtp">SMTP</option>
                        <option value="mail_sendmail">Sendmail</option>
                     </select><br/>
            From: <input name="from" size=50 type="text" value="noreply@openpsa2.org"/><br/>
            To: <input name="to" size=50 type="text" value="test@openpsa2.org" /><br/>
            Subject: <input name="subject" size=50 type="text" value="Testing o.o.mail with special chars (ÄäÖöÅå€)"/><br/>
            Message HTML:<br/>
            <textarea rows=40 cols=80 name="html_body"><p>Test body with special chars (Ää Öö Åå €)</p>
<p>Some images:
    <img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-project.gif"><br/>
    <img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/24x24/lock.png"><br/>
    <img src="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-small.png"><br/>
</p>
<table>
    <tr>
        <td background="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/rorschach.png">
            <p>Table cell with background image.</p>
            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Vestibulum odio. Praesent est odio, aliquet vitae, hendrerit at, porttitor vitae, tellus. Morbi scelerisque dolor. Maecenas nunc. Morbi pretium posuere neque. Nullam cursus laoreet magna. Nam sed tellus et purus rhoncus lacinia. Aenean nonummy tempus eros. Mauris ut nisl. Suspendisse est mauris, porta a, pharetra ut, euismod ac, elit. Fusce id diam sit amet lorem vulputate iaculis. Suspendisse gravida quam sed nulla aliquam vulputate. Donec convallis, diam vitae congue semper, velit lectus rutrum justo, nec imperdiet pede eros sit amet ante.</p>
            <p>Phasellus eget tellus. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aliquam gravida, orci eu lobortis semper, ipsum sem sagittis est, vel imperdiet metus turpis nec enim. Sed justo turpis, pretium a, cursus eget, bibendum eu, tortor. Sed sed est. Quisque gravida justo in lorem. Nullam in lacus id metus scelerisque faucibus. Nam tempor, lorem sed venenatis tempor, ligula purus euismod enim, vitae porttitor dui justo non ipsum. Aenean eros libero, varius quis, sodales eu, consequat aliquet, pede. Ut faucibus vulputate ligula. Curabitur dui. Nullam lacus ipsum, lacinia eu, hendrerit a, tincidunt quis, mi. Nullam dapibus, lacus quis euismod ultrices, lectus nisi vulputate nisl, quis molestie nulla metus feugiat risus. Vivamus sit amet diam. Vestibulum eget risus sit amet nulla vulputate porta. Duis quis massa. In id leo eu metus imperdiet blandit. Nulla ipsum. Nam luctus, leo a interdum blandit, tortor elit posuere ligula, quis molestie nisl nibh hendrerit tellus. Donec in lectus nec est blandit scelerisque.</p>
            <p>Donec varius tempus nulla. Integer diam eros, euismod vel, rhoncus sed, vulputate at, eros. Proin tincidunt. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi a odio. Suspendisse rhoncus lacus id libero. Proin libero. Cras ornare felis quis eros. Sed quis lectus. Integer sed arcu. In ullamcorper metus vel nunc. Vivamus ut lectus. Vivamus at orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce sit amet pede a nisl tristique egestas. Donec eu pede. Morbi nisl. Fusce pede dolor, aliquam ac, bibendum in, semper quis, sem. </p>
        </td>
    </tr>
</table>

<em>Test person</em></textarea><br/>
            <input type="submit" value="Send" />
        </p>
    </form>
<?php

} else {
    $mail = new org_openpsa_mail($_POST['backend']);
    $mail->to = $_POST['to'];
    $mail->from = $_POST['from'];
    $mail->subject = $_POST['subject'];
    $mail->html_body = $_POST['html_body'];
    $mail->embed_images();
    $ret = $mail->send();
    echo "<p>mail->send returned {$ret}<br>\n";
    if (!$ret) {
        echo $mail->get_error_message();
    }
    echo "</p>\n";
}
?>