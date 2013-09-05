'default_try_backends' => array
(
    'mail',
'mail_sendmail',
    'mail_smtp',

),

//This becomes the fifth parameter of mail()
'mail_params' => array(
    'additional_params' => ''
),

//These are passed to the Mail_smtp backend
'mail_smtp_params' => array
(
    'host' => null,
    'port' => null
),

//These are passed to the Mail_sendmail backend
'mail_sendmail_params' => array
(
    'sendmail_path' => '/usr/sbin/sendmail',
    'sendmail_args' => '-bs',
),

//Theses domains report character set incorrectly (thus messing up the conversion if we trust it)
'incorrect_charset_domains' => array
(
     'yahoo.com',
),
'mb_detect_encoding_list' => 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS',
//Transliterate by default (other valid options '//IGNORE' or null)
'iconv_append_target' => '//TRANSLIT',

'schemadb_formmailer' => 'file:/org/openpsa/mail/config/schemadb_formmailer.inc',
'formmailer_from' => 'formmailer@openpsa2.org',
'formmailer_to' => '',
'formmailer_subject' => 'New form submission',
'formmailer_body' => '
Submitted form data:

Subject: __SUBJECT__

Message: __MESSAGE__
',