<?php
/**
 * Parser/Outputter for vCalendar (and vCard data)
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @version $Id: vxparser.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_vxparser
{
    /**
     * Container for various compatibility options
     */
    var $compatibility = array();
    /**
     * Container for parsed vTimezone records
     */
    var $timezones = array();
    /**
     * charset to use
     */
    var $charset = 'utf-8';

    /**
     * Used then constructor decoding
     * @access private
     */
    var $_parsed = false;

    function __construct($input = false)
    {
        // Compatibility defaults, need to be adjusted for various states of client b0rkedness
        $this->compatibility['data'] = array();
        $this->compatibility['data']['suppose_charset'] = 'ISO-8859-1';
        $this->compatibility['data']['escape_separators'] = true;
        $this->compatibility['data']['folding'] = true;
        $this->compatibility['data']['supported_encodings'] = array();
        //Both iCal and Sunbird do *not* support Quoted Printable or Base64 for text, we default for them.
        $this->compatibility['data']['supported_encodings']['QP'] = false;
        $this->compatibility['data']['supported_encodings']['B64'] = false;
        //for completenes sake we specify this, however newlines in data must always be escaped in any case
        $this->compatibility['data']['supported_encodings']['NLESC'] = true;
        //Quotation sign used for quoting parameters (when exporting)
        $this->compatibility['data']['quotation_sign'] = '"';
        $this->compatibility['times'] = array();

        if ($input !== false)
        {
            //Pass on to decode
            $this->_parsed = array
            (
                'variables' => array(),
                'parameters' => array(),
            );
            $this->vx_parse_recursive($this->_parsed['variables'], $this->_parsed['parameters'], $input);
            return $this->_parsed;
        }
    }

    /**
     * Merge given array with the compatibility array
     */
    function merge_compatibility($merge)
    {
        return $this->_merge_compatibility_array_recursive($merge, $this->compatibility);
    }

    /**
     * Merges two given arrays recursively
     */
    function _merge_compatibility_array_recursive(&$merge, &$comp)
    {
        if (!is_array($merge))
        {
            return false;
        }
        foreach ($merge as $k => $v)
        {
            if (!isset($comp[$k]))
            {
                // If key(space) is not present at all just overwrite it as whole and skip to next key
                $comp[$k] = $v;
                continue;
            }
            if (is_array($v))
            {
                // recurse arrays
                $this->_merge_compatibility_array_recursive($v, $comp[$k]);
                continue;
            }
            // Overwrite normal keys
            $comp[$k] = $v;
        }
        return true;
    }

    /**
     * RFC says field separators in non-compound values must be escaped
     *
     * However some clients are b0rken and do not un-escape, thus check compatibility first
     *
     * @param string $data data to be escaped (in case compatibility check does not disallow it)
     * @param boolean $force force escape in all cases
     * @return string escaped string
     */
    function escape_separators($data, $force = false)
    {
        if (   !$force
            && isset($this->compatibility['data'])
            && isset($this->compatibility['data']['escape_separators'])
            && $this->compatibility['data']['escape_separators'] === false)
        {
            return $data;
        }
        return str_replace(array(';', ','), array('\;','\,'), $data);
    }

    /**
     * Converts DURATION to seconds or an array
     */
    function vCal_duration($input, $toArray=FALSE)
    {
        $ret=array('d' => 0, 'h' => 0, 'm' => 0, 's' => 0);
        $regExp='/([+-])?P(([0-9]+)W)?(([0-9]+)D)?(T(([0-9]+)H)?(([0-9]+)M)?(([0-9]+)S)?)?/';
        preg_match($regExp, $input, $dmatch);
        //Check the prefix, in case of minus make factor -1, else 1.
        if ($dmatch[1]==='-')
        {
            $x=-1;
        }
        else
        {
            $x=1;
        }
        //Weeks
        if (isset($dmatch[3]))
        {
            $ret['d']+=((int)$dmatch[3])*$x;
        }
        //Days
        if (isset($dmatch[5]))
        {
            $ret['d']+=((int)$dmatch[5])*$x;
        }
        //Hours
        if (isset($dmatch[8]))
        {
            $ret['h']+=((int)$dmatch[8])*$x;
        }
        //Minutes
        if (isset($dmatch[10]))
        {
            $ret['m']+=((int)$dmatch[10])*$x;
        }
        //Seconds
        if (isset($dmatch[12]))
        {
            $ret['s']+=((int)$dmatch[12])*$x;
        }

        //If we want seconds in stead of an array: sum and return.
        if ($toArray==FALSE)
        {
            return (($ret['d']*3600*24)+($ret['h']*3600)+($ret['m']*60)+($ret['s']));
        }

        return $ret;
    }

    /**
     * Converts between vCal and Unix (and Midgard created/revised) timestamps
     */
    function vcal_stamp($stamp=false, $params=array())
    {
        if ($stamp === false)
        {
            $stamp=time();
        }
        if (isset($params['TZID']))
        {
            $convert = $params['TZID'];
        }
        else
        {
            //PONDER: should this be 'NO_CONVERSION', so to handle floating times more gracefully ?
            if (   isset($this->compatibility['times']['suppose_utc'])
                && $this->compatibility['times']['suppose_utc'] === true)
            {
                //Unspecified times are supposed to be UTC
                $convert = 'UTC';
            }
            else
            {
                //Unspecified times are left "floating".
                $convert = 'NO_CONVERSION';
            }
        }
        if (!isset($params['VALUE']))
        {
            $params['VALUE']='DATE-TIME';
        }

       //If $stamp is Midgard created/revised timestamp, convert to vCal
       if (preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $stamp, $matches))
       {
            $stamp = mktime((int)$matches[4],(int)$matches[5],(int)$matches[6],(int)$matches[2],(int)$matches[3],(int)$matches[1]);
            //If value is DATE (in stead of DATETIME) return a datestamp in stead
            if ($params['VALUE'] === 'DATE')
            {
                return date('Ymd', $stamp);
            }
            if ($convert) $stamp = $this->timezone_convert($stamp, &$convert, 'to');
            return date('Ymd', $stamp) . 'T' . date('His', $stamp);
       }

        //If $stamp is vCal timestamp convert to Unix timestamp
        if (preg_match("/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})(Z?)/", $stamp, $matches))
        {
            $stamp=mktime((int)$matches[4],(int)$matches[5],(int)$matches[6],(int)$matches[2],(int)$matches[3],(int)$matches[1]);
            //Z modifier at the end of a vCal timestamp specifies that it's in "Zulu time"==UTC, any other TZID is disallowed
            if (   isset($matches[7])
                && $matches[7] != null)
            {
                $convert = 'UTC';
            }
            if ($convert)
            {
                $stamp=$this->timezone_convert($stamp, &$convert, 'from');
            }
            return $stamp;
        }

        //If $tamp is vCal *date* stamp, convert to Unix timestamp (one second after midnight)
        if (preg_match("/(\d{4})(\d{2})(\d{2})/", $stamp, $matches) && $params['VALUE']==='DATE')
        {
            $stamp=mktime(0,0,1,(int)$matches[2],(int)$matches[3],(int)$matches[1]);
            return $stamp;
        }
        //If value is DATE (in stead of DATETIME) return a datestamp
        if ($params['VALUE'] === 'DATE')
        {
            return date('Ymd', $stamp);
        }
        //Otherwise convert to vCal timestamp
        if ($convert)
        {
            $stamp = $this->timezone_convert($stamp, &$convert, 'to');
        }
        return date('Ymd', $stamp) . 'T' . date('His', $stamp);
    }

    function _vtimezone_decode_offset($str)
    {
        preg_match('/([+-])([0-9]{2})([0-9]{2})/', $str, $matches);
        $sign=$matches[1];
        $h=3600*(int)$matches[2];
        $min=60*(int)$matches[3];
        switch ($sign) {
            case '+':
                return $h+$min;
            break;
            case '-':
                return -1*($h+$min);
            break;
        }
    }

    function timezone_convert($stamp, $tzid='UTC', $dir='to')
    {
           //Some clients incorrectly specify Zulu (or whatever) -time even if they mean floating time...
           if (isset($this->compatibility['times']['force_float']) && $this->compatibility['times']['force_float']===TRUE)
           {
                $tzid='NO_CONVERSION';
           }
           switch(strtoupper($tzid))
           {
                case 'UTC': //The normal case, no extra adjustment required.
                    $use_offset=0;
                break;
                case 'NO_CONVERSION':
                    return $stamp;
                break;
                default:
                    if (isset($this->timezones[$tzid])) {
                        $tzInfo=&$this->timezones[$tzid]['var'];
                        if (isset($tzInfo['STANDARD'])) {
                            $std_starts=$this->vCal_stamp($tzInfo['STANDARD']['DTSTART'], array('TZID' => 'NO_CONVERSION'));
                            $std_offset=$tzInfo['STANDARD']['TZOFFSETTO'];
                        }
                        if (isset($tzInfo['DAYLIGHT'])) {
                            $dst_starts=$this->vCal_stamp($tzInfo['DAYLIGHT']['DTSTART'], array('TZID' => 'NO_CONVERSION'));
                            $dst_offset=$tzInfo['DAYLIGHT']['TZOFFSETTO'];
                        }

                        if (isset($std_starts) && isset($dst_starts)) {
                            //Both are present, determine which to use...
                            if ($stamp>$std_starts && $stamp<$dst_starts) {
                                $use_offset=$this->_vtimezone_decode_offset($std_offset);
                            } else if ($stamp>$dst_starts && $stamp<$std_starts) {
                                $use_offset=$this->_vtimezone_decode_offset($dst_offset);
                            } else if ($std_starts>$dst_starts && $stamp>$std_starts) {
                                $use_offset=$this->_vtimezone_decode_offset($std_offset);
                            } else if ($dst_starts>$std_starts && $stamp>$dst_starts) {
                                $use_offset=$this->_vtimezone_decode_offset($dst_offset);
                            }
                        } else if (isset($std_starts)) {
                            //Only standard time is defined
                            $use_offset=$this->_vtimezone_decode_offset($std_offset);
                        } else if (isset($dst_starts)) {
                            //Only DST is defined
                            $use_offset=$this->_vtimezone_decode_offset($dst_offset);
                        }
                    } else {
                        //Do not know how to convert, return the stamp as is
                        return $stamp;
                    }
                break;
           }
           switch(strtolower($dir))
           {
                 case "from":
                    $stamp-=$use_offset;
                    $stamp=gmmktime(date('G',$stamp),date('i',$stamp),date('s',$stamp),date('n',$stamp),date('j',$stamp),date('Y',$stamp));
                 break;
                 case "to":
                    $stamp+=$use_offset;
                    $stamp=mktime(gmdate('G',$stamp),gmdate('i',$stamp),gmdate('s',$stamp),gmdate('n',$stamp),gmdate('j',$stamp),gmdate('Y',$stamp));
                 break;
                 default: //Direction must be to or from but for slightly nicer failure mode we return the original stamp
                    return $stamp;
                 break;
           }
    return $stamp;
    }

    /**
     * Encode value to quoted-printable
     */
    function vx_encode_qp($str, $nl = "\r\n")
    {
        preg_match_all("/[^\x20-\x7e]/", $str, $matches);
        $cache=array();
        foreach ($matches[0] as $char)
        {
            $hex = str_pad(strtoupper(dechex(ord($char))), 2, "0", STR_PAD_LEFT);
            if (isset($cache[$hex]))
            {
                continue;
            }
            $str = str_replace($char, "=$hex", $str);
            $cache[$hex] = true;
        }
        if ($this->_check_folding())
        {
            // "Fold" data if necessary/allowed to do so
            org_openpsa_helpers_vxparser::_vx_encode_fold($str, $nl);
        }
        return $str;
    }


    /**
     * Encode value to base64
     */
    function vx_encode_b64($str, $nl = "\r\n")
    {
        $str=base64_encode($str);
        if ($this->_check_folding())
        {
            // "Fold" data if necessary/allowed to do so
            org_openpsa_helpers_vxparser::_vx_encode_fold($str, $nl);
        }
        return $str;
    }

    /**
     * Encode value to escape newlines
     */
    function vx_encode_nl($str, $nl = "\r\n")
    {
        //Convert and escape linebreaks
        $str = preg_replace("/\r\n|\n\r|\r|\n/", '\n', $str);
        if ($this->_check_folding())
        {
            // "Fold" data if necessary/allowed to do so
            org_openpsa_helpers_vxparser::_vx_encode_fold($str, $nl);
        }
        return $str;
    }

    function _vx_encode_fold(&$str, $nl = "\r\n")
    {
        $str = chunk_split($str, 75, "{$nl} "); //"Fold" data
        //In case chunk_split adds the separator to end of string, we don't want that.
        $str = preg_replace('/' . $nl . ' $/', '', $str);
    }

    /**
     * Check if value needs encoding, encode as necessary
     */
    function vx_encode($str, &$params, $nl="\r\n")
    {
        //See if we have any special characters in str to escape
        if (preg_match_all("/[^\x20-\x7e]/", $str, $matches))
        {
            if (   (   (count($matches[0])/strlen($str)*100)>50
                    && $this->compatibility['data']['supported_encodings']['B64']
                    )
                || (   isset($params['VALUE'])
                    && $params['VALUE'] == 'BINARY'
                    )
                )
            {
                //If over 50% characters require encoding (and client supports it) or data is specified to be binary use base64
                $params['CHARSET'] = strtoupper($this->charset);
                $params['ENCODING'] = 'BASE64';
                $str = $this->vx_encode_b64($str, $nl);
            }
            else if ($this->compatibility['data']['supported_encodings']['QP'])
            {
                //Use quoted-printable encoding if client supports it
                $params['CHARSET'] = strtoupper($this->charset);
                $params['ENCODING'] = 'QUOTED-PRINTABLE';
                $str = $this->vx_encode_qp($str, $nl);
            }
            else
            {
                //Onlyn escape linebreaks (since we have to do this somehow in any case, there is no check against client support)
                $params['CHARSET'] = strtoupper($this->charset);
                $str = $this->vx_encode_nl($str, $nl);
            }
        }
        return $str;
    }


      function vCal_decode($data, $param=array()) {
                if (!(isset($this->compatibility['data']['parse_separators']) && $this->compatibility['data']['parse_separators']===FALSE)) {
                    $data_arr=preg_split("/([^\\\])[,;]/", $data, -1, PREG_SPLIT_DELIM_CAPTURE); //Not very nice but I can't think of a better way to explode with unescaped delimiters...
                    if (count($data_arr)>1 && is_array($data_arr)) { //We have multiple values in $data, decode them separately
                        while (list ($k, $v) = @each ($data_arr)) {
                              if ($k%2==0) continue;
                              $data_arr[$k-1].=$v;
                              unset($data_arr[$k]);
                        } reset ($data_arr);
                        $data=array(); $i=0; $oldParam=$param; $param=array();
                        while (list ($k, $v) = each ($data_arr)) {
                              $param[$i]=$oldParam;
                              $data[$i]=$this->vCal_decode($v, &$param[$i]);
                              $i++;
                        }
                   }
               }

               if (!is_array($data)) {
                    if (!isset($param['ENCODING'])) $param['ENCODING']='QUOTED-PRINTABLE'; //Try quoted printable as default encoding
                    switch (strtoupper($param['ENCODING'])) {
                        default:
                        case 'QUOTED-PRINTABLE':
                            preg_match_all("/=([0-9A-F]{2})/i", $data, $matches);
                            $cache=array();
                            while (list ($k, $hex) = each ($matches[1])) {
                                    if (isset($cache[$hex])) continue;
                                    $data=str_replace("=$hex", chr(hexdec($hex)), $data);
                                    $cache[$hex]=TRUE;
                             }
                            unset($param['ENCODING']);
                        break;
                        case 'BASE64':
                            $data=base64_decode($data);
                            unset($param['ENCODING']);
                        break;
                    }

                   $data=str_replace(array('\n','\;','\,'), array("\n",';',','), $data); //Convert escaped values back to real
                   $data=preg_replace("/\r\n|\n\r|\r/", "\n", $data); //Convert different CR/LF sequences to newlines

                   if (!isset($param['CHARSET'])) $param['CHARSET']=$this->compatibility['data']['suppose_charset'];
                   //Convert characters if necessary
                   //if ($this->__iconv && isset($param['CHARSET']) && $param['CHARSET'] && strtolower($param['CHARSET'])!=strtolower($this->charset) && function_exists('iconv')) {
                   if (isset($param['CHARSET']) && $param['CHARSET'] && strtolower($param['CHARSET'])!=strtolower($this->charset) && function_exists('iconv')) {
                      $icRet=iconv($param['CHARSET'], $this->charset, $data);
                      if ($icRet !== FALSE) { //Make sure iconv succeeded before overwriting data
                         $data=$icRet;
                         unset($param['CHARSET']);
                      }
                   } else if (isset($param['CHARSET']) && strtolower($param['CHARSET'])==strtolower($this->charset)) {
                      //We already have data correct charset, let's remove the parameter as unnecessary.
                      unset($param['CHARSET']);
                   }
               }
        return $data;
      }

    function _unfold($data)
    {
        $data=preg_replace("/\r\n|\n\r|\r/", "\n", $data); //Make sure we only have newlines in the
        $data=preg_replace("/\n[\x9\x20]|=\n/", '', $data); //RFC and MIME "soft-linebreak" unfolding
        return $data;
    }


      function vx_parse_recursive(&$toVars, &$toParams, $data) {
                //"Unfolding" lines (RFC says lines must be unfolded before parsing), at the same time we convert all linebreaks to newlines
                $data=$this->_unfold($data);


                $setMode=FALSE; $setData='';
                $rows=explode("\n", $data);
                while (list ($k, $v) = each ($rows)) {
                        if (!$v) continue; //Skip empthy lines
                        if ($setMode) {
                            if ($v=='END:' . $setMode) {
                                if (isset($toVars[$setMode])) {
                                    if (!isset($seen[$setMode]) || $seen[$setMode]!=TRUE) {
                                        $oldVal=$toVars[$setMode];
                                        $oldPar=$toParams[$setMode];
                                        $toVars[$setMode]=array();
                                        $toVars[$setMode][]=$oldVal;
                                        $toParams[$setMode]=array();
                                        $toParams[$setMode][]=$oldPar;
                                        $seen[$setMode]=TRUE;
                                    }
                                    $tmpVal=&$toVars[$setMode][];
                                    $tmpPar=&$toParams[$setMode][];
                                } else {
                                    $tmpVal=&$toVars[$setMode];
                                    $tmpPar=&$toParams[$setMode];
                                }
                                $this->vx_parse_recursive($tmpVal, $tmpPar, $setData);
                                $setMode=FALSE; $setData='';
                            } else {
                                $setData.=$v."\n";
                            }
                        } else if (preg_match('/BEGIN:(.*)/', $v, $bMatches)) {
                            $setMode=$bMatches[1];
                            $setData='';
                        } else {
                            $this->_vCal_parse_line($toVars, $toParams, $v);
                        }
                }
        return true;
      }

      function _vCal_parse_line(&$data, &$parameters, $v) {
              $key=''; $keyData=''; $keyParam=array(); //Reset temp data
              //Get us the key and parameters (and data)
              list ($keyTmp, $keyData) = explode (":", $v, 2);
              $keyTmp=explode(";", $keyTmp);
              $key=$keyTmp[0];
              unset($keyTmp[0]);
              while (list ($kk, $vv) = each ($keyTmp)) {
                    list ($kpName, $kpVal) = explode("=", $vv, 2);
                    $kpVal=preg_replace("/^([\"']?)(.*?)(\\1?)$/", "\\2",  $kpVal); //Strip outmost quotes from value
                    $keyParam[$kpName]=$this->vCal_decode($kpVal, array('CHARSET'=>'')); //We'll handle the charset issue later for parameters
              }

              if (isset($data[$key])) { //Multiple instances of same key must be parsed as new values, we put them to array.
                 if (!is_array($data[$key])) {
                    $oldVal=$data[$key];
                    $oldParam=$parameters[$key];
                    $data[$key]=array(0 => $oldVal);
                    $parameters[$key]=array(0 => $oldParam);
                 }
                 $data[$key][]=$this->vCal_decode($keyData, &$keyParam);
                 $parameters[$key][]=$keyParam;
              } else {
                 $data[$key]=$this->vCal_decode($keyData, &$keyParam);
                 $parameters[$key]=$keyParam;
              }

        return TRUE;
      }

    /**
     * Whether to "fold" data or not
     *
     * Defensively checks the compatibility settings and returns true/false
     * @return boolean true if folding is allowed
     */
    function _check_folding()
    {
        if (   isset($this->compatibility['data'])
            && $this->compatibility['data']['folding']
            && $this->compatibility['data']['folding'] === false)
        {
            return false;
        }
        return true;
    }

    /**
     * Whether to quote strings or not
     *
     * Defensively checks the compatibility settings and returns true/false
     * @return boolean true if folding is allowed
     */
    function _check_quoting()
    {
        if (   isset($this->compatibility['data'])
            && isset($this->compatibility['data']['quoting'])
            && $this->compatibility['data']['quoting'] === false)
        {
            return false;
        }
        return true;
    }

    /**
     * recurses given arrays and returns vX formatted string
     */
    function export_vx_variables_recursive(&$var, &$param, $keyOverride = false, $nl = "\r\n")
    {
        $ret = '';
        if (!is_array($var))
        {
            return false;
        }
        reset($var);
        foreach ($var as $k => $v)
        {
            //If ignore is set to true for this property, do not export it.
            if (   isset($this->compatibility['ignore_vCal'][$k])
                && $this->compatibility['ignore_vCal'][$k] === true)
            {
                continue;
            }

            //Recurse arrays
            if (is_array($v))
            {
               $ret .= $this->export_vx_variables_recursive($v, $param[$k], $k, $nl);
               continue;
            }
            //Skip empthy fields
            if (empty($v))
            {
                continue;
            }
            // Make sure the params array is set for this key
            if (   !isset($param[$k])
                || !is_array($param[$k]))
            {
                $param[$k] = array();
            }

            // Encode the value properly
            $v = $this->vx_encode($v, &$param[$k], $nl);

            //Append extra parameters on fields
            $keyExtra='';
            if (is_array($param[$k]))
            {
                reset($param[$k]);
                foreach ($param[$k] as $kk => $vv)
                {
                    $dummyarray = array();
                    $vv = $this->vx_encode($vv, $dummyarray, $nl);
                    //quote value if it contains whitespace
                    if (   preg_match("/\s/", $vv)
                        && $this->_check_quoting())
                    {
                        $vv = $this->compatibility['data']['quotation_sign'] . $vv . $this->compatibility['data']['quotation_sign'];
                    }
                    if (!$this->_check_folding())
                    {
                        // Compatibility says not to fold data at all, so we won't
                        $keyExtra .= ';' . $kk . '=' . $vv;
                    }
                    else
                    {
                        //"Fold" parameters (easiest way to keep lines in RFC width and preserve readability)
                        $keyExtra .= ';' . $nl . ' ' . $kk . '=' . $vv;
                    }
                }
            }
            if ($keyOverride !== false)
            {
                $k = $keyOverride;
            }
            if ($this->_check_folding())
            {
                //"Fold" Key and value (readibilty, RFC width)
                $ret .= $k . $keyExtra . ':' . $nl . ' ' . $v . $nl;
            }
            else
            {
                //Folding forbidden
                $ret .= $k . $keyExtra . ':' . $v . $nl;
            }
        }
        return $ret;
    }
}

?>