<?php
/*
  Plugin Name: Search Engine Keywords
  Version: 1.1.1
  Description: Provide an API to search for keywords within search engine's query variable and react on them.
  Author: Thomas Maier
  Author URI: http://www.webgilde.com/
*/

//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

define('SEK_TEXTDOMAIN', 'search_engine_keywords');
define('SEK_VERSION', '1.1.1');
define('SEK_OPTNAME', 'sek_options');
define('SEK_PLUGINNAME', 'Search Engine Keywords');
define('SEK_DIR', plugin_dir_path(__FILE__));
define('SEK_URL', plugin_dir_url(__FILE__));
define('SEK_LIBDIR', SEK_DIR . 'lib/');
define('SEK_TPLDIR', SEK_DIR . 'tmpl/');
define('SEK_SCRIPTURL', SEK_URL . 'scripts/');

require_once(SEK_LIBDIR . 'sek-data.class.php');
require_once(SEK_LIBDIR . 'sek-pattern.class.php');
require_once(SEK_LIBDIR . 'sek-api.class.php');
require_once(SEK_LIBDIR . 'sek-plugin.class.php');

load_plugin_textdomain(SEK_TEXTDOMAIN, false, SEK_DIR . 'lang/');

if ( '' == session_id() ) {
	session_start();
}


global $SE_Keywords;
$SE_Keywords = new SEK_PLUGIN();
global $SEK_API;
$SEK_API = $SE_Keywords->get_api();

// Template tags

/**
* Return mactch on a variable
*/
function sek_match($variable) {
    global $SEK_API;
    $match = $SEK_API->matches();
    if (isset($match[$variable])) {
        $res = $match[$variable]['value'];
        return $res;
    }
    return false;
}

/**
* Return the pattern witch matches the variable (if there is match)
*/
function sek_match_pattern($variable) {
    global $SEK_API;
    $match = $SEK_API->matches();
    if (isset($match[$variable])) {
        return $match[$variable]['pattern'];
    }
    return false;
}
