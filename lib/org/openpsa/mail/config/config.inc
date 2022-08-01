'default_try_backends' => [
    'mail_sendmail',
    'mail_smtp'
],

//These are passed to the SMTP transport
'mail_smtp_params' => [
    'host' => null,
    'port' => null
],

//These are passed to the Sendmail transport
'mail_sendmail_params' => [
    'sendmail_path' => '/usr/sbin/sendmail',
    'sendmail_args' => '-bs',
],
