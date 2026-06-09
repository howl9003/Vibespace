<?php
/**
 * More change, more fun.
 * Wrapper for the various info class objects as of IPB 1.1.2 and later, and
 * other IPB things that might turn MY sources into copyrighted by IPS.
 *
 * Some code (c)2001-whenever by Invision Power Services, where indicated.
 *
 * @package IPBSDK
 * @author CirTap <cirtap@otherone.org>
 * @author Cow <khlo@global-centre.com>
 * @version 04.04.2004
 */

/**
 * Defines the number of the most recent known and supported board version.
 * This also helps SDK modules and Injector plugins to run conditional code.
 */
define('IPB_INFO_CLASS', '130');

/**
 * Get the desired info class to create the famous $ibforums object.
 * Requires IPB's conf_global.php to be already loaded by the main script.
 *
 * (c) 2003 media++ || WebMechanic.biz
 *
 * @author CirTap <cirtap@otherone.org>
 * @param integer $version Valid values range from 110 to IPB_INFO_CLASS
 * @param bool $acp If TRUE, creates an ACP info object instead
 * @see IPB_INFO_CLASS, $ibforums
 */
function &get_info($version, $acp = false) {
	global $INFO;
	// See what info class we have to load - Forum or ACP?
	$cls = ($acp) ? 'acp_info' : 'ipb_info';
	$cls .= trim((string)$version);
	if (class_exists($cls)) {
		if ($acp) {
			return new $cls($INFO);
		} else {
			return new $cls();
		}
	} else {
		// Assume its the most recent version
		$classname = ($acp) ? 'acp_info' : 'ipb_info';
		$classname .= (string)IPB_INFO_CLASS;
		// trigger a PHP notice about invalid info class version
		trigger_error("Unknown IPB info Class <tt>'$cls'</tt>. Using the default <tt>'$classname'</tt>.", E_USER_NOTICE);
		return ($acp) ? new $classname($INFO) : new $classname;
	}
}

/**
 * For v1.1.x - a blantant assumption.
 *
 * @package IPBSDK
 * @ignore
 */
class ipb_info110 {
	# (c) IPS
	var $member = array();
	var $input = array();
	var $session_id = "";
	var $base_url = "";
	var $vars = "";
	var $skin_id = "0";
	var $skin_rid = "";
	var $lang_id = "en";
	var $skin = "";
	var $lang = "";
	var $server_load = 0;
	var $version = "v1.1.2";
	var $lastclick = "";
	var $location = "";
	var $debug_html = "";

	function ipb_info110() {
		global $INFO;
		$this->vars = $INFO;
		if (!isset($INFO['html_url'])) return;
		$this->vars['TEAM_ICON_URL'] = $INFO['html_url'] . '/team_icons';
		$this->vars['AVATARS_URL'] = $INFO['html_url'] . '/avatars';
		$this->vars['EMOTICONS_URL'] = $INFO['html_url'] . '/emoticons';
		// how smart to make this one lowercase ...
		$this->vars['mime_img'] = $INFO['html_url'] . '/mime_types';
	}
	function info() {
		$this->ipb_info110();
	}
}

/**
 * For ACP v1.x
 *
 * @package IPBSDK
 * @ignore
 */
class acp_info100 {
	# (c) IPS
	var $vars = "";
	var $version = '1.0';
	function acp_info100(&$INFO) {
		$this->vars = $INFO;
	}
	function info($INFO) {
		$this->acp_info100($INFO);
	}
}

/**
 * For IPB v1.1
 * @ignore
 */
class acp_info110 extends acp_info100 {
	# (c) IPS
	var $version = '1.1';
	function acp_info110($INFO) {
		parent::info($INFO);
	}
	function info($INFO) {
		$this->acp_info110($INFO);
	}
}

/**
 * For IPB v1.2x
 * @ignore
 */
class ipb_info120 extends ipb_info110 {
	# (c) IPS
	var $version = "v1.2";
	var $perm_id = "";
	var $forum_read = array();
	var $topic_cache = "";
	var $session_type = "";
	function ipb_info120() {
		parent::info();
	}
	function info() {
		$this->ipb_info120();
	}
}

/**
 * For ACP v1.2x
 * @ignore
 */
class acp_info120 extends acp_info110 {
	# (c) IPS
	var $version = '1.2';
	var $acpversion = '12005';
	var $base_url = '';
	function acp_info120($INFO) {
		parent::info($INFO);
		$this->base_url = $INFO['board_url'] . "/index." . $INFO['php_ext'] . '?';
		if (!isset($INFO['html_url'])) return;
		$this->vars['TEAM_ICON_URL'] = $INFO['html_url'] . '/team_icons';
		$this->vars['AVATARS_URL'] = $INFO['html_url'] . '/avatars';
		$this->vars['EMOTICONS_URL'] = $INFO['html_url'] . '/emoticons';
		$this->vars['mime_img'] = $INFO['html_url'] . '/mime_types';
	}
	function info($INFO) {
		$this->acp_info120($INFO);
	}
}

/**
 * For IPB v1.3x
 * @ignore
 */
class ipb_info130 extends ipb_info120 {
	# (c) IPS
	var $version = 'v1.3 Final';
	var $perm_id = "";
	var $forum_read = array();
	var $topic_cache = "";
	var $session_type = "";
	function ipb_info130() {
		parent::info();
	}
	function info() {
		$this->ipb_info130();
	}
}

/**
 * For ACP v1.3x
 * @ignore
 */
class acp_info130 extends acp_info120 {
	# (c) IPS
	var $version = '1.3';
	var $acpversion = '13005';
	var $base_url = '';
	function acp_info130($INFO) {
		parent::info($INFO);
	}
	function info($INFO) {
		$this->acp_info130($INFO);
	}
}

if (!class_exists('debug')) {
	/**
	 * The oh-so-important Debug class.
	 * This class declaration is ommitted if the SDK runs inside IPB.
	 *
	 * @package IPBSDK
	 */
	class Debug {
		# (c) IPS
		function startTimer() {
			global $starttime;
			$mtime = microtime ();
			$mtime = explode (' ', $mtime);
			$mtime = $mtime[1] + $mtime[0];
			$starttime = $mtime;
		}
		function endTimer() {
			global $starttime;
			$mtime = microtime ();
			$mtime = explode (' ', $mtime);
			$mtime = $mtime[1] + $mtime[0];
			$endtime = $mtime;
			$totaltime = round (($endtime - $starttime), 5);
			return $totaltime;
		}
	}
}

/**
 * Another useless function for the statistic freaks.
 *
 * @see Debug::startTimer()
 */
function ipb_set_timer() {
	$GLOBALS['Debug'] = new Debug;
	$GLOBALS['Debug']->startTimer();
} // function ipb_set_timer
/**
 * Loads and instatiates a set of given IPB object variables.
 * When you call this function take care of the correct order.
 * Check in index.php and admin.php for this,
 * Typical usage: ipb_set_objects( array('std', 'sess', 'DB') );
 * Load additional objects: ipb_set_objects( array('emailer', 'print') );
 * DB and sess require additional steps provided by ipb_set_database() and
 * ipb_set_session().
 *
 * (c) 2003 media++ || WebMechanic.biz
 * @author CirTap <cirtap@webmechanic.biz>
 * @param array $wanted. IPB Object names to be initialized
 * @see $DB, ipb_set_database(), $ibforums, get_info(), $sess, ipb_set_session()
 * @since IPBSDK 1.1 opt. returns a reference to the object instead of adding it to $GLOBALS only
 */
function &ipb_set_objects($wanted, $getref=FALSE) {
# only major scripts here, the other files are too specific but can
# be added to the list without any guarantee to work properly :)
	$_sources = array(
		'FUNC'=>'sources/functions.php',
		'db_driver'=>'sources/Drivers/mySQL.php',
		'session'=>'sources/functions.php',
		'display'=>'sources/functions.php',
		'post_parser'=>'sources/lib/post_parser.php',
		'emailer'=>'sources/lib/emailer.php'
		);

	$_objects = array(
		'FUNC'=>'std',
		'db_driver'=>'DB',
		'session'=>'sess',
		'display'=>'print',
		'post_parser'=>'parser',
		'emailer'=>'emailer',
		);
	if ($getref) $objects = array();
	foreach( array_keys(array_intersect($_objects, $wanted)) as $_x => $_cls) {
		if (!$getref) {
			ipb_init_object($_objects[$_cls], $_cls, $_sources[$_cls], $getref);
		} else {
			$objects[$_objects[$_cls]] =& ipb_init_object($_objects[$_cls], $_cls, $_sources[$_cls], $getref);
		}
	}
	if ($getref) return $objects;
} // function ipb_set_objects

/**
 * Called from ipb_set_objects(), initializes a single object.
 *
 * @see ipb_set_objects
 * @since IPBSDK 1.1 opt. returns a reference to the object instead of adding it to $GLOBALS only
 */
function &ipb_init_object($obj, $cls, $source, $getref=FALSE) {
global $INFO, $ibforums;
	if (!isset($GLOBALS['SIM'])) {
		// most likely we're *not* running in the Simulator environment
		require_once ROOT_PATH . $source;
	} else {
		// aha: the Simulator is our "host"
		require_once $SIM->vars['board_home'] . $source;
	}
	if (!$getref) {
		$GLOBALS[$obj] =& new $cls;
	} else {
		return new $cls;
	}
} // function ipb_init_object

/**
 * Requires the global {@link $DB} object to be initialized, i.e. with: ipb_set_objects( array('DB') )
 * and sets the properties of the object.
 *
 * @see ipb_set_objects
 */
function ipb_set_database() {
	global $INFO;
	$GLOBALS['DB']->obj['sql_database'] = $INFO['sql_database'];
	$GLOBALS['DB']->obj['sql_user'] = $INFO['sql_user'];
	$GLOBALS['DB']->obj['sql_pass'] = $INFO['sql_pass'];
	$GLOBALS['DB']->obj['sql_host'] = $INFO['sql_host'];
	$GLOBALS['DB']->obj['sql_tbl_prefix'] = $INFO['sql_tbl_prefix'];
	$GLOBALS['DB']->obj['debug'] = ($INFO['sql_debug'] == 1) ? (int)$_GET['debug'] : 0;
	$GLOBALS['DB']->connect();
} // function ipb_set_database
/**
 * Sets the session values for the current (logged in) member -- or guest.
 * Requires $DB to be initialized and connected, i.e. via ipb_set_database() or from within IPB.
 *
 * @see ipb_set_database
 */
function ipb_set_session() {
	# (c) IPS
	global $DB, $INFO, $std, $sess, $ibforums;
	$ibforums->member = $sess->authorise();
	$ibforums->session_id = $sess->session_id;
	$ibforums->location = $sess->location;
	$ibforums->lastclick = $sess->last_click;
}

?>