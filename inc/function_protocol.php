<?php

 /*----------------------------------------------------------------------------------------------------------\
 |                                                                                                            |
 |                      [ LIVE GAME SERVER LIST ] [ © RICHARD PERRY FROM GREYCUBE.COM ]                       |
 |                                                                                                            |
 |    Released under the terms and conditions of the GNU General Public License Version 3 (http://gnu.org)    |
 |                                                                                                            |
 \-----------------------------------------------------------------------------------------------------------*/

  function lgsl_type_list() { return array("source" => "Counter-Strike Source"); }
  
  function lgsl_protocol_list()
  {
    return array( "source" => "05");
    return $lgsl_protocol_list;
  }

  function lgsl_query_live($type, $ip, $c_port, $q_port, $s_port, $request)
  {
    if (preg_match("/[^0-9a-z\.\-\[\]\:]/i", $ip)) { exit("LGSL PROBLEM: INVALID IP OR HOSTNAME"); }
    $lgsl_protocol_list = lgsl_protocol_list();
    if (!isset($lgsl_protocol_list[$type])) { exit("LGSL PROBLEM: ".($type ? "INVALID TYPE '{$type}'" : "MISSING TYPE")." FOR {$ip}, {$c_port}, {$q_port}, {$s_port}"); }
    $lgsl_function = "lgsl_query_{$lgsl_protocol_list[$type]}";
    if (!function_exists($lgsl_function)) { exit("LGSL PROBLEM: FUNCTION DOES NOT EXIST FOR: {$type}"); }
    if (!intval($q_port)) { exit("LGSL PROBLEM: INVALID QUERY PORT"); }

    $server = array(
    "b" => array("type" => $type, "ip" => $ip, "c_port" => $c_port, "q_port" => $q_port, "s_port" => $s_port, "status" => 1),
    "s" => array("game" => "", "name" => "Сервер выключен", "map" => "пусто", "players" => 0, "playersmax" => 0, "password" => ""),
    "e" => array(),
    "p" => array(),
    "t" => array());
    $response = lgsl_query_direct($server, $request, $lgsl_function, "udp");
    if (!$response) // SERVER OFFLINE
    {
      $server['b']['status'] = 0;
    }
    else
    {
      if (empty($server['s']['game'])) { $server['s']['game'] = $type; }
      if (empty($server['s']['map']))  { $server['s']['map']  = "-"; }
      if (($pos = strrpos($server['s']['map'], "/"))  !== FALSE) { $server['s']['map'] = substr($server['s']['map'], $pos + 1); }
      if (($pos = strrpos($server['s']['map'], "\\")) !== FALSE) { $server['s']['map'] = substr($server['s']['map'], $pos + 1); }
      $server['s']['players']    = intval($server['s']['players']);
      $server['s']['playersmax'] = intval($server['s']['playersmax']);
      if (isset($server['s']['password'][0])) { $server['s']['password'] = (strtolower($server['s']['password'][0]) == "t") ? 1 : 0; }
      else                                    { $server['s']['password'] = intval($server['s']['password']); }
      if (strpos($request, "p") === FALSE && empty($server['p']) && $server['s']['players'] != 0) { unset($server['p']); }
      if (strpos($request, "p") === FALSE && empty($server['t']))                                 { unset($server['t']); }
      if (strpos($request, "e") === FALSE && empty($server['e']))                                 { unset($server['e']); }
      if (strpos($request, "s") === FALSE && empty($server['s']['name']))                         { unset($server['s']); }
    }
    return $server;
  }

  function lgsl_query_direct(&$server, $request, $lgsl_function, $scheme)
  {
    $lgsl_fp = @fsockopen("{$scheme}://{$server['b']['ip']}", $server['b']['q_port'], $errno, $errstr, 1);
    if (!$lgsl_fp) { return FALSE; }
    global $lgsl_config;
    $lgsl_config['timeout'] = intval($lgsl_config['timeout']);
    stream_set_timeout($lgsl_fp, $lgsl_config['timeout'], $lgsl_config['timeout'] ? 0 : 500000);
    stream_set_blocking($lgsl_fp, TRUE);
    $lgsl_need      = array();
    $lgsl_need['s'] = strpos($request, "s") !== FALSE ? TRUE : FALSE;
    $lgsl_need['e'] = strpos($request, "e") !== FALSE ? TRUE : FALSE;
    $lgsl_need['p'] = strpos($request, "p") !== FALSE ? TRUE : FALSE;
    if ($lgsl_need['e'] && !$lgsl_need['s']) { $lgsl_need['s'] = TRUE; }

    do
    {
      $lgsl_need_check = $lgsl_need;
      $response = call_user_func_array($lgsl_function, array(&$server, &$lgsl_need, &$lgsl_fp));
      if (!$response) { break; }
      if ($lgsl_need_check == $lgsl_need) { break; }
      if ($lgsl_need['p'] && $server['s']['players'] == "0") { $lgsl_need['p'] = FALSE; }
    }
    while ($lgsl_need['s'] == TRUE || $lgsl_need['e'] == TRUE || $lgsl_need['p'] == TRUE);

//---------------------------------------------------------+

    @fclose($lgsl_fp);

    return $response;
  }


  function lgsl_query_05(&$server, &$lgsl_need, &$lgsl_fp)
  {
    $challenge_code = isset($lgsl_need['challenge']) ? $lgsl_need['challenge'] : "\x00\x00\x00\x00";
    if     ($lgsl_need['s']) { fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00"); }
    elseif ($lgsl_need['p']) { fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x55{$challenge_code}");       }
    elseif ($lgsl_need['e']) { fwrite($lgsl_fp, "\xFF\xFF\xFF\xFF\x56{$challenge_code}");       }
    $packet_temp  = array();
    $packet_type  = 0;
    $packet_count = 0;
    $packet_total = 4;
    do
    {
      $packet = fread($lgsl_fp, 4096); if (!$packet) { return FALSE; }
      if     ($lgsl_need['s']) { if ($packet[4] == "D")                                           { continue; } }
      elseif ($lgsl_need['p']) { if ($packet[4] == "m" || $packet[4] == "I")                      { continue; } }
      elseif ($lgsl_need['e']) { if ($packet[4] == "m" || $packet[4] == "I" || $packet[4] == "D") { continue; } }
      //---------------------------------------------------------------------------------------------------------------------------------+
      if     (substr($packet, 0,  5) == "\xFF\xFF\xFF\xFF\x41") { $lgsl_need['challenge'] = substr($packet, 5,  4); return TRUE; } // REPEAT WITH GIVEN CHALLENGE CODE
      elseif (substr($packet, 0,  4) == "\xFF\xFF\xFF\xFF")     { $packet_total = 1;                     $packet_type = 1;       } // SINGLE PACKET - HL1 OR HL2
      elseif (substr($packet, 9,  4) == "\xFF\xFF\xFF\xFF")     { $packet_total = ord($packet[8]) & 0xF; $packet_type = 2;       } // MULTI PACKET  - HL1 ( TOTAL IS LOWER NIBBLE OF BYTE )
      elseif (substr($packet, 12, 4) == "\xFF\xFF\xFF\xFF")     { $packet_total = ord($packet[8]);       $packet_type = 3;       } // MULTI PACKET  - HL2
      elseif (substr($packet, 18, 2) == "BZ")                   { $packet_total = ord($packet[8]);       $packet_type = 4;       } // BZIP PACKET   - HL2
      $packet_count ++;
      $packet_temp[] = $packet;
    }
    while ($packet && $packet_count < $packet_total);
	if ($packet_type == 0) { return $server['s'] ? TRUE : FALSE; } // UNKNOWN RESPONSE ( SOME SERVERS ONLY SEND [s] )

    $buffer = array();
    foreach ($packet_temp as $packet)
    {
      if     ($packet_type == 1) { $packet_order = 0; }
      elseif ($packet_type == 2) { $packet_order = ord($packet[8]) >> 4; $packet = substr($packet, 9);  } // ( INDEX IS UPPER NIBBLE OF BYTE )
      elseif ($packet_type == 3) { $packet_order = ord($packet[9]);      $packet = substr($packet, 12); }
      elseif ($packet_type == 4) { $packet_order = ord($packet[9]);      $packet = substr($packet, 18); }
      $buffer[$packet_order] = $packet;
    }
    ksort($buffer);
    $buffer = implode("", $buffer);

    if ($packet_type == 4)
    {
      if (!function_exists("bzdecompress")) // REQUIRES http://php.net/bzip2
      {
        $server['e']['bzip2'] = "unavailable"; $lgsl_need['e'] = FALSE;
        return TRUE;
      }
      $buffer = bzdecompress($buffer);
    }
    $header = lgsl_cut_byte($buffer, 4);
    if ($header != "\xFF\xFF\xFF\xFF") { return FALSE; } // SOMETHING WENT WRONG

//---------------------------------------------------------+

    $response_type = lgsl_cut_byte($buffer, 1);

    if ($response_type == "I") // SOURCE INFO ( HALF-LIFE 2 )
    {
      $server['e']['netcode']     = ord(lgsl_cut_byte($buffer, 1));
      $server['s']['name']        = lgsl_cut_string($buffer);
      $server['s']['map']         = lgsl_cut_string($buffer);
      $server['s']['game']        = lgsl_cut_string($buffer);
      $server['e']['description'] = lgsl_cut_string($buffer);
      $server['e']['appid']       = lgsl_unpack(lgsl_cut_byte($buffer, 2), "S");
      $server['s']['players']     = ord(lgsl_cut_byte($buffer, 1));
      $server['s']['playersmax']  = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['bots']        = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['dedicated']   = lgsl_cut_byte($buffer, 1);
      $server['e']['os']          = lgsl_cut_byte($buffer, 1);
      $server['s']['password']    = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['anticheat']   = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['version']     = lgsl_cut_string($buffer);
    }

    elseif ($response_type == "m") // HALF-LIFE 1 INFO
    {
      $server_ip                  = lgsl_cut_string($buffer);
      $server['s']['name']        = lgsl_cut_string($buffer);
      $server['s']['map']         = lgsl_cut_string($buffer);
      $server['s']['game']        = lgsl_cut_string($buffer);
      $server['e']['description'] = lgsl_cut_string($buffer);
      $server['s']['players']     = ord(lgsl_cut_byte($buffer, 1));
      $server['s']['playersmax']  = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['netcode']     = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['dedicated']   = lgsl_cut_byte($buffer, 1);
      $server['e']['os']          = lgsl_cut_byte($buffer, 1);
      $server['s']['password']    = ord(lgsl_cut_byte($buffer, 1));
      if (ord(lgsl_cut_byte($buffer, 1))) // MOD FIELDS ( OFF FOR SOME HALFLIFEWON-VALVE SERVERS )
      {
        $server['e']['mod_url_info']     = lgsl_cut_string($buffer);
        $server['e']['mod_url_download'] = lgsl_cut_string($buffer);
        $buffer = substr($buffer, 1);
        $server['e']['mod_version']      = lgsl_unpack(lgsl_cut_byte($buffer, 4), "l");
        $server['e']['mod_size']         = lgsl_unpack(lgsl_cut_byte($buffer, 4), "l");
        $server['e']['mod_server_side']  = ord(lgsl_cut_byte($buffer, 1));
        $server['e']['mod_custom_dll']   = ord(lgsl_cut_byte($buffer, 1));
      }
      $server['e']['anticheat'] = ord(lgsl_cut_byte($buffer, 1));
      $server['e']['bots']      = ord(lgsl_cut_byte($buffer, 1));
    }

    elseif ($response_type == "D") // SOURCE AND HALF-LIFE 1 PLAYERS
    {
      $returned = ord(lgsl_cut_byte($buffer, 1));
      $player_key = 0;
      while ($buffer)
      {
        $server['p'][$player_key]['pid']   = ord(lgsl_cut_byte($buffer, 1));
        $server['p'][$player_key]['name']  = lgsl_cut_string($buffer);
        $server['p'][$player_key]['score'] = lgsl_unpack(lgsl_cut_byte($buffer, 4), "l");
        $server['p'][$player_key]['time']  = lgsl_time(lgsl_unpack(lgsl_cut_byte($buffer, 4), "f"));
        $player_key ++;
      }
    }

    elseif ($response_type == "E") // SOURCE AND HALF-LIFE 1 RULES
    {
      $returned = lgsl_unpack(lgsl_cut_byte($buffer, 2), "S");
      while ($buffer)
      {
        $item_key   = strtolower(lgsl_cut_string($buffer));
        $item_value = lgsl_cut_string($buffer);
        $server['e'][$item_key] = $item_value;
      }
    }
    if ($lgsl_need['s'] && !$lgsl_need['e']) { $server['e'] = array(); }
    if     ($lgsl_need['s']) { $lgsl_need['s'] = FALSE; }
    elseif ($lgsl_need['p']) { $lgsl_need['p'] = FALSE; }
    elseif ($lgsl_need['e']) { $lgsl_need['e'] = FALSE; }
    return TRUE;
  }

//---------------------------------------------------------+

  function lgsl_time($seconds)
  {
    if ($seconds === "") { return ""; }
    $n = $seconds < 0 ? "-" : "";
    $seconds = abs($seconds);
    $h = intval($seconds / 3600);
    $m = intval($seconds / 60  ) % 60;
    $s = intval($seconds       ) % 60;
    $h = str_pad($h, "2", "0", STR_PAD_LEFT);
    $m = str_pad($m, "2", "0", STR_PAD_LEFT);
    $s = str_pad($s, "2", "0", STR_PAD_LEFT);
    return "{$n}{$h}:{$m}:{$s}";
  }

//---------------------------------------------------------+

  function lgsl_unpack($string, $format)
  {
    list(,$string) = @unpack($format, $string);
    return $string;
  }

//---------------------------------------------------------+

  function lgsl_cut_byte(&$buffer, $length)
  {
    $string = substr($buffer, 0, $length);
    $buffer = substr($buffer, $length);
    return $string;
  }

//---------------------------------------------------------+

  function lgsl_cut_string(&$buffer, $start_byte = 0, $end_marker = "\x00")
  {
    $buffer = substr($buffer, $start_byte);
    $length = strpos($buffer, $end_marker);
    if ($length === FALSE) { $length = strlen($buffer); }
    $string = substr($buffer, 0, $length);
    $buffer = substr($buffer, $length + strlen($end_marker));
    return $string;
  }

//------------------------------------------------------------------------------------------------------------+
//--------- PLEASE MAKE A DONATION OR SIGN THE GUESTBOOK AT GREYCUBE.COM IF YOU REMOVE THIS CREDIT -----------+
?>
