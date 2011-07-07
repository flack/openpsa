'default_try_backends' => array
(
    'mail_sendmail',
    'mail_smtp',
    'mail',
),

'mail_smtp_params' => array
(
    'host' => null,
    'port' => null
),

'mail_sendmail_params' => array
(
    'sendmail_path' => null,
    'sendmail_args' => null,
),

//Theses domains report character set incorrectly (thus messing up the conversion if we trust it)
'incorrect_charset_domains' => array
(
        'yahoo.com',
),
'mb_detect_encoding_list' => 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS',
//Transliterate by default (other valid options '//IGNORE' or null)
'iconv_append_target' => '//TRANSLIT',
