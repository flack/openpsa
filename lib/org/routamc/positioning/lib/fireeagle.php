<?php

/**
 * FireEagle OAuth+API PHP bindings
 *
 * Copyright (C) 2007-08 Yahoo! Inc
 *
 * See http://fireeagle.yahoo.net/developer/documentation/walkthru_php
 * for usage instructions.
 *
 */

/*

NOTES:

- You'll probably need PHP 5.2.3+.  If you find you don't have the
  hash_hmac() function, see here for a pure PHP version:

    http://laughingmeme.org/code/hmacsha1.php.txt

- To get HTTPS working on Windows, download curl-ca-bundle.crt from
  here:

    http://curl.haxx.se/latest.cgi?curl=win32-ssl

  Then add this line after the curl_init() call in the
  Fireeagle::http() function, replacing c:/web with the path of the
  folder containing curl-ca-bundle.crt:

    curl_setopt($ch, CURLOPT_CAINFO, 'c:/web/curl-ca-bundle.crt');

*/

// Requires OAuth.php from http://oauth.googlecode.com/svn/code/php/OAuth.php
require_once(dirname(__FILE__)."/OAuth.php");

// Various things that can go wrong
class FireEagleException extends Exception {
  const TOKEN_REQUIRED = 1; // call missing an oauth request/access token
  const LOCATION_REQUIRED = 2; // call to update() without a location
  const REMOTE_ERROR = 3; // FE sent an error
  const REQUEST_FAILED = 4; // empty or malformed response from FE
  const CONNECT_FAILED = 5; // totally failed to make an HTTP request

  public $response; // for REMOTE_ERROR codes, this is the response from FireEagle (useful: $response->code and $response->message)

  // Values of $this->response->code:
  const UPDATE_NOT_PERMITTED = 1;

  function __construct($msg, $code, $response=null) {
    parent::__construct($msg, $code);
    $this->response = $response;
  }
}

/**
 * FireEagle API access helper class.
 */
class FireEagle {

  public static $FE_ROOT = "http://fireeagle.yahoo.net";
  public static $FE_API_ROOT = "https://fireeagle.yahooapis.com";

  public static $FE_DEBUG = false; // set to true to print out debugging info
  public static $FE_DUMP_REQUESTS = false; // set to a pathname to dump out http requests to a log

  // OAuth URLs
  function requestTokenURL() { return self::$FE_API_ROOT.'/oauth/request_token'; }
  function authorizeURL() { return self::$FE_ROOT.'/oauth/authorize'; }
  function accessTokenURL() { return self::$FE_API_ROOT.'/oauth/access_token'; }
  // API URLs
  function methodURL($method) { return self::$FE_API_ROOT.'/api/0.1/'.$method.'.json'; }

  function __construct($consumerKey,
               $consumerSecret, 
               $oAuthToken = null, 
               $oAuthTokenSecret = null)  {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumerKey, $consumerSecret, NULL);
    if (!empty($oAuthToken) && !empty($oAuthTokenSecret)) {
      $this->token = new OAuthConsumer($oAuthToken, $oAuthTokenSecret);
    } else {
      $this->token = NULL;
    }
  }

  /**
   * Get a request token for authenticating your application with FE.
   *
   * @returns a key/value pair array containing: oauth_token and
   * oauth_token_secret.
   */
  public function getRequestToken() {
    $r = $this->oAuthRequest($this->requestTokenURL());
    $token = $this->oAuthParseResponse($r);
    if (self::$FE_DUMP_REQUESTS) self::dump("Now the user is redirected to ".$this->getAuthorizeURL($token['oauth_token'])."\nOnce the user returns, via the callback URL for web authentication or manually for desktop authentication, we can get their access token and secret by calling /oauth/access_token.\n\n");
    return $token;
  }

  /**
   * Get the URL to redirect to to authorize the user and validate a
   * request token.
   *
   * @returns a string containing the URL  to redirect to.
   */
  public function getAuthorizeURL($token) {
    return $this->authorizeURL() . '?oauth_token=' . $token;
  }
  
  /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   *
   * @returns array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  public function getAccessToken() {
    $this->requireToken();
    $r = $this->oAuthRequest($this->accessTokenURL());
    return $this->oAuthParseResponse($r);
  }

  /**
   * Generic method call function.  You can use this to get the raw
   * output from an API method, or to call future API methods.
   *
   * e.g.
   *   Get a user's location: $fe->call("user")
   *     or $fe->user()
   *   Set a user's location: $fe->call("update", array("q" => "new york, new york"))
   *     or $fe->update(array("q" => "new york, new york"))
   */

  public function call($method, $params=array()) {
    $this->requireToken();
    $r = $this->oAuthRequest($this->methodURL($method), $params);
    return $this->parseJSON($r);
  }

  // --- Wrappers for individual methods ---
  
  /**
   * Wrapper for 'user' API method, which fetches the current location
   * for a user.
   */
  public function user() {
    $r = $this->call("user");
    // add latitudes and longitudes, and extract best guess
    if (isset($r->user->location_hierarchy)) {
      $r->user->best_guess = NULL;
      foreach ($r->user->location_hierarchy as &$loc) {
    $c = $loc->geometry->coordinates;
    switch ($loc->geometry->type) {
    case 'Box': // DEPRECATED
      $loc->bbox = $c;
      $loc->longitude = ($c[0][0] + $c[1][0]) / 2;
      $loc->latitude = ($c[0][1] + $c[1][1]) / 2;
      $loc->geotype = 'box';
      break;
    case 'Polygon':
      $loc->bbox = $bbox = $loc->geometry->bbox;
      $loc->longitude = ($bbox[0][0] + $bbox[1][0]) / 2;
      $loc->latitude = ($bbox[0][1] + $bbox[1][1]) / 2;
      $loc->geotype = 'box';
      break;
    case 'Point':
      list($loc->longitude, $loc->latitude) = $c;
      $loc->geotype = 'point';
      break;
    }
    if ($loc->best_guess) $r->user->best_guess = $loc; // add shortcut to get 'best guess' loc
    unset($loc);
      }
    }
    
    return $r;
  }

  /**
   * Wrapper for 'update' API method, to set a user's location.
   */
  public function update($args=array()) {
    if (empty($args)) throw new FireEagleException("FireEagle::update() needs a location", FireEagleException::LOCATION_REQUIRED);
    return $this->call("update", $args);
  }

  /**
   * Wrapper for 'lookup' API method, to run a location query without
   * setting the user's location (so an application can show a list of
   * possibilities that match a user-supplied query -- not to be used
   * as a generic geocoder).
   */
  public function lookup($args=array()) {
    if (empty($args)) throw new FireEagleException("FireEagle::lookup() needs a location", FireEagleException::LOCATION_REQUIRED);
    return $this->call("lookup", $args);
  }

  // --- Internal bits and pieces ---

  protected function parseJSON($json) {
    $r = json_decode($json);
    if (empty($r)) throw new FireEagleException("Empty JSON response", FireEagleException::REQUEST_FAILED);
    if ($r->stat != 'ok') throw new FireEagleException($r->code.": ".$r->message, FireEagleException::REMOTE_ERROR, $r);
    return $r;
  }

  protected function requireToken() {
    if (!isset($this->token)) {
      throw new FireEagleException("This function requires an OAuth token", FireEagleException::TOKEN_REQUIRED);
    }
  }
  
  // Parse a URL-encoded OAuth response
  protected function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $r[urldecode($pair[0])] = urldecode($pair[1]);
    }  
    return $r;
  }

  // Format and sign an OAuth / API request
  function oAuthRequest($url, $args=array()) {
    $method = empty($args) ? "GET" : "POST";
    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
    if (self::$FE_DEBUG) {
      echo "<div>[OAuth request: <blockquote><code>".nl2br(htmlspecialchars(var_export($req, TRUE)))."</code><br>base string: ".htmlspecialchars($req->base_string)."</blockquote>]</div>";
    }
    if (self::$FE_DUMP_REQUESTS) {
      $k = $this->consumer->secret . "&";
      if ($this->token) $k .= $this->token->secret;
      self::dump("---\n\nOAUTH REQUEST TO $url");
      if (!empty($args)) self::dump(" WITH PARAMS ".json_encode($args));
      self::dump("\n\nBase string: ".$req->base_string."\nSignature string: $k\n");
    }
    switch ($method) {
    case 'GET': return $this->http($req);
    case 'POST': return $this->http($req->get_normalized_http_url(), $req->to_postdata());
    }
  }

  // Make an HTTP request, throwing an exception if we get anything other than a 200 response
  public function http($url, $postData=null) {
    if (self::$FE_DEBUG) {
      echo "[FE HTTP request: url: ".htmlspecialchars($url).", post data: ".htmlspecialchars(var_export($postData, TRUE))."]";
    }
    if (self::$FE_DUMP_REQUESTS) {
      self::dump("Final URL: $url\n\n");
      $url_bits = parse_url($url);
      if (isset($postData)) {
    self::dump("POST ".$url_bits['path']." HTTP/1.0\nHost: ".$url_bits['host']."\nContent-Type: application/x-www-urlencoded\nContent-Length: ".strlen($postData)."\n\n$postData\n");
      } else {
    $get_url = $url_bits['path'];
    if ($url_bits['query']) $get_url .= '?' . $url_bits['query'];
    self::dump("GET $get_url HTTP/1.0\nHost: ".$url_bits['host']."\n\n");
      }
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (isset($postData)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (!$status) throw new FireEagleException("Connection to $url failed", FireEagleException::CONNECT_FAILED);
    if ($status != 200) {
      throw new FireEagleException("Request to $url failed: HTTP error $status ($response)", FireEagleException::REQUEST_FAILED);
    }
    if (self::$FE_DUMP_REQUESTS) {
      self::dump("HTTP/1.0 $status OK\n");
      $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      if ($ct) self::dump("Content-Type: $ct\n");
      $cl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
      if ($cl) self::dump("Content-Length: $cl\n");
      self::dump("\n$response\n\n");
    }
    curl_close ($ch);

    if (self::$FE_DEBUG) {
      echo "[HTTP response: <code>".nl2br(htmlspecialchars($response))."</code>]";
    }
    
    return $response;
  }

  private function dump($text) {
    if (!self::$FE_DUMP_REQUESTS) throw new Exception('FireEagle::$FE_DUMP_REQUESTS must be set to enable request trace dumping');
    file_put_contents(self::$FE_DUMP_REQUESTS, $text, FILE_APPEND);
  }

}

?>