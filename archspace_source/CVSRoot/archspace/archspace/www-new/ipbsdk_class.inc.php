<?php
/**
 * IPB SDK - Version 1.1
 * <br>Main SDK Class.
 *
 * IPB SDK is a library of PHP functions, which will help you develop advanced applications on your site. Integration between your forum and site is essential in the websites of today. IPB already has a great member system, why re-write another one if you can use one member database for both?
 * <br>Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Code (c) 2003-2004 IPB SDK Development Team
 * http://ipbsdk.sourceforge.net
 *
 * @package IPBSDK
 * @author IPB SDK Development Team (Run {@link IPBSDK::sdk_info()} for credits)
 * @version 1.1.2 (maintenance release 05/02/2004)
 * @copyright 2003-2004 IPB SDK Development Team
 */

/**
 * Full qualified path to the SDK class file.
 */
if (!defined('SDK_PATH')) {
	define ('SDK_PATH', dirname(__FILE__) . '/');
}
/**
 * The full qualified path to the DocumentRoot of the VirtualHost/Domain,
 * always ending with a slash.
 *
 * This variable is usually avalable as of all releases of PHP 4 running
 * on Apache and Linux. However, on Window's IIS webserver, this variable
 * may not exist, so we try to rebuild it from the path of the calling script.
 *
 * @global string
 * @name DOCUMENT_ROOT
 * @see SDK_PATH
 * @since 1.0.1
 */
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
	$_SERVER['DOCUMENT_ROOT'] = str_replace($_SERVER['PATH_INFO'], '', str_replace("\\\\", DIRECTORY_SEPARATOR, $_SERVER['PATH_TRANSLATED'] ) );
}

if (!defined('DIRECTORY_SEPARATOR')) {
	/**
	 * DIRECTORY_SEPARATOR is undefined in some versions of PHP 4, so we define it ourselfs if necessary.
	 */
	define('DIRECTORY_SEPARATOR', (substr(PHP_OS, 0, 3) == 'WIN') ? '\\' : '/');
}
if (!defined('PATH_SEPARATOR')) {
	/**
	 * PATH_SEPARATOR is undefined in some versions of PHP 4, so we define it ourselfs if necessary.
	 */
	define('PATH_SEPARATOR', (substr(PHP_OS, 0, 3) == 'WIN') ? ';' : ':');
}
// Add the SDK Path to the include path.
// On some servers ini_set() might be disabled for some
// paranoid reasons so we try set_include_path() first
// thanx to the forxer.net for accidently pointing this out :)
if (function_exists('set_include_path')) {
	set_include_path(get_include_path() . PATH_SEPARATOR . SDK_PATH);
} else {
	// if this won't work, we have to live without SDK_PATH in the include_path
	if (strpos(@ini_get('disable_functions'), 'ini_set') === FALSE ) {
		ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . SDK_PATH);
	}
}

if (!defined('IN_IPB')) {
	/**
	 * Load IPB class wrappers for IPB's own info Class.<br />
	 * This step is ommitted in IPB SDK runs inside IPB itself.
	 */
	require_once SDK_PATH . 'lib/ipb_classes.inc.php';
	/**
	 * Instatiates a dummy for later use with IPB's own database connection object.
	 *
	 * @global object $DB
	 */
	$DB = new stdClass();
	/**
	 * Instatiates a dummy for later re-assignment with the wrapper classes of IPB's magic $ibforums object
	 *
	 * @global object $ibforums
	 * @see get_info()
	 */
	$ibforums = new stdClass();
	/**
	 * Instatiates a dummy for later re-assignment of IPB's native classed from function.php
	 *
	 * @global object $std
	 */
	$std = new stdClass();
	/**
	 * Instatiates a dummy for later re-assignment with IPB's native session object.
	 *
	 * @global object $sess
	 */
	$sess = new stdClass();
	/**
	 * Instatiates a dummy for later re-assignment with the parser class.
	 *
	 * @global object $parser
	 */
	$parser = new stdClass();
	/**
	 * Placeholder for what's in IPB's configuration file config_global.php
	 *
	 * @global array $INFO
	 */
	$INFO = array();
}

/**
 * The Main class wrapps all the functionality of the IPB SDK library and extensions modules.
 *
 * Usage example:
 * <code>require_once('ipbsdk/ipbsdk_class.inc.php');
 * $SDK =& new IPBSDK();
 * $SDK->sdk_info();
 * </code>
 *
 * @package IPBSDK
 */
class IPBSDK {
	/**
	 * Guess what ...
	 * @access protected
	 * @var string
	 */
	var $ipbsdk_version = '1.1.2';
	/**
	 * Configuration settings, many comming from the config file and changed on initialization.
	 * @var array
	 */
	var $ipbsdk_settings = array();
	/**
	 * Current language code used for localization. Language files are located in the <var>lib/</var> subfolder of {@link SDK_PATH}
	 * @var string
	 */
	var $lang;
	/**
	 * The full qualified URL to your board without '/index.php'.
	 * It must not end with a trailing slash.
	 *
	 * <b>Default URL:</b> 'http://' . $_SERVER['HTTP_HOST'] . '/forum';
	 * <b>Example URL:</b> "http://www.mydomain.com/community/forums";
	 * @var string a backup
	 */
	var $board_url;
	/**
	 * An internal copy of the database connection from IPB. Created from the mySQL driver class.
	 * @access protected
	 * @var object
	 */
	var $DB;
	/**
	 * Extra libraries and object references most taken from what's in IPB /sources/.
	 * @var array
	 */
	var $extra = array();
	/**
	 * Last known member data from {@link get_info()}, {@link get_advinfo()}
	 * @var array
	 */
	var $member;
	/**
	 * Last known member ID from {@link get_info()}, {@link get_advinfo()}
	 * @var string
	 */
	var $memberid = '';
	/**
	 * Whether the member is logged in ('0', '1')
	 * @var string
	 */
	var $loggedin = '0';

	// -- private properties go here -----------------
    /**#@+
     * @access private
     */
	/**
	 * Runtime options
	 */
	var $_options = array();
	/**
	 * List of last error occured in both IPB and the SDK.
	 */
	var $_errors = array();
	/**
	 * Last error occured in any of IPB and the SDK.
	 */
	var $_lasterror;
	/**
	 * Cached SQL query results, used if {@link $allow_caching} is on.
	 */
	var $_cache = array();
	/**
	 * This is what's allowed to come via $options, other keys are ignored (see constructor)
	 */
	var $_allowedOptions = array(
			'root_path'=>'root_path',
			'board_url'=>'board_url',
			'language'=>'sdklang',
			'board_version'=>'board_version',
			'allow_caching'=>'allow_caching',
			'timer'=>'timer',
			'debug'=>'debug');

	/**
	 * @ignore
	 * this one's temporary until support for the deprecated methods is dropped :)
	 */
	var $_depr_msg = '<b>NOTICE:</b> Use of deprecated method: "<b>%s</b>"! This method may not be supported in future versions of the IPB SDK. Please update your scripts to use "<b>%s</b>" instead.';

	/**
	 * Class constructor.
	 * Does all the dirty work for IPB to run as smooth as possible.
	 *
	 * Initilaizes the database connection, loads the language pack, and does other nice tricks to easy your life as a PHP developer.
	 *
	 * @param array $options Use this to overwrite settings from the configuration file.
	 * @author CirTap <cirtap@otherone.org>
	 * @author Cow <khlo@global-centre.com>
	 * @since 1.0.0
	 * @return object Instance of IPBSDK
	 */
	function IPBSDK($options = array('root_path' => '', 'board_url' => '', 'sdklang' => '', 'board_version' =>'', 'allow_caching'=>'', 'timer'=>'', 'debug'=>'')) {
		// Board vars, SDK Settings etc. No $ibforums!!!
		global $INFO;

		// load SDK configuration
		$config = $this->load_config();
		foreach (array_keys($options) as $k) {
			if (empty($options[$k])) {
				$options[$k] = @$config[$k];
			}
		}

		// argh. design errors always persist ;-)
		$this->_options['language'] = $options['sdklang'];

		// assign IPB SDK related settings
		// $ipbsdk_settings is always for the boards settings
		// Meanwhile $_options can be runtime ones, too.
		// $_options should be used everywhere else.
		$this->ipbsdk_settings['board_url']     = $config['board_url'];
		$this->ipbsdk_settings['root_path']     = $config['root_path'];
		$this->ipbsdk_settings['allow_caching'] = $config['allow_caching'];
		$this->ipbsdk_settings['sdklang']       = $config['sdklang'];
		$this->ipbsdk_settings['board_version'] = $config['board_version'];
		$this->board_url = $config['board_url'];

		// Put runtime options in $this->_options
		// E_ALL compliant ;)
		$this->_options['board_url']     = (isset($options['board_url'])) ? $options['board_url'] : $config['board_url'];
		$this->_options['board_path']    = (isset($options['root_path'])) ? $options['root_path'] : $config['root_path'];
		$this->_options['board_version'] = (isset($options['board_version'])) ? $options['board_version'] : $config['board_version'];

		// for BC: global out some settings
		$GLOBALS['board_url'] = $this->_options['board_url'];
		$GLOBALS['root_path'] = $this->_options['board_path'];

		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', $this->_options['board_path']);
		}

		if (defined('IN_IPB')) {
			// Load classes for use within IPB itself.
			require_once SDK_PATH . 'lib/inipb/classes.inc.php';
			$GLOBALS['parser'] = new SDK_post_parser;
		} else {
			/**
			 * Load IPB's own configuration file into $INFO
			 */
			require_once $this->_options['board_path'] . 'conf_global.php';
			// some food for IPB
			$INFO['board_url'] = $this->_options['board_url'];
			$this->base_url = $this->_options['board_url'] . '/index.' . $INFO['php_ext'];
			// Now: shut up!
			// swallows all the annoying notices & warnings of functions.php
			ob_start();

			ipb_set_objects(array('std', 'sess', 'DB', 'parser'));
			// assign sql_* settings and connect
			ipb_set_database();
			$this->_errors['IPB Related'] = (ob_get_length()) ? ob_get_contents() : 'Clean Run! WOW! Now try E_ALL ;-)';
			// Do you need a stopwatch?
			// This IPB Stopwatch is very special, but we won't bother
			// loading it unless it's requested as it's a waste of time.
			if (isset($options['timer'])) {
				ipb_set_timer();
				$this->_options['timer'] = '1';
			}
			/*
			info as of IPB 1.2 Forum (not 1.1.2 nor any ACP <g>)
			Praise the Creator (no, not 'Homer') there's no is_subclass_of()
			in the IPB code that verifies an object's origin ;-)
			we need to use $GLOBALS['ibforums'], as PHP won't update the simple $ibforum
			when using global (and whick is a copy by then) until we're done and 'return'
			from this constructor.
			but the IPB functions already need the vars and props assigned in the next
			couple of steps.
			*/
			$GLOBALS['ibforums'] = &get_info($this->_options['board_version'], FALSE);
			$GLOBALS['ibforums']->input = $GLOBALS['std']->parse_incoming();

			// add/fix some of the always missing input keys; they'll be empty, but they exist
			settype($GLOBALS['ibforums']->input['act'], 'string');
			settype($GLOBALS['ibforums']->input['code'], 'string');
			settype($GLOBALS['ibforums']->input['s'], 'string');
			settype($GLOBALS['ibforums']->input['Privacy'], 'string');

			// authenticate the user, adds to $GLOBALS['ibforums']->member
			ipb_set_session();
			$GLOBALS['ibforums']->input['last_activity'] = @$GLOBALS['ibforums']->member['last_activity'];
			$GLOBALS['ibforums']->input['last_visit']    = @$GLOBALS['ibforums']->member['last_visit'];

			// back in business, errors that follow belong to us :)
			$this->_errors['IPB Related'] = (ob_get_length()) ? ob_get_contents() : 'Clean Run! WOW! Now try E_ALL ;)';
			ob_end_clean();
		}

		// copy DB for all internal requests
		$this->DB = $GLOBALS['DB'];

		if (empty($GLOBALS['ibforums']->member['mgroup']) OR $GLOBALS['ibforums']->member['mgroup'] == $GLOBALS['ibforums']->vars['guest_group'] OR $GLOBALS['ibforums']->member['id'] == '0') {
			$GLOBALS['ibforums']->loggedin = 0;
		} else {
			$GLOBALS['ibforums']->loggedin = 1;
		}
		$this->loggedin = $GLOBALS['ibforums']->loggedin;

		// See what language pack. If it's unavailable, load default
		// SDK Language Pack as in Settings. This can be changed later in the
		// main script so one can change it to the members setting or whatever.
		if ($options['language']) {
			if (!$this->sdk_set_language($options['language'])) {
				$this->sdk_set_language($this->ipbsdk_settings['sdklang']);
			}
		} else {
			$this->sdk_set_language($this->ipbsdk_settings['sdklang']);
		}
		$this->_options['language'] = $this->ipbsdk_settings['sdklang'];

	} // function IPBSDK


	/**
	 * The Factory method will instatiate the requested Module and
	 * returns a reference to the newly create object.
	 *
	 * @param string $obj_class The module to be created
	 * @param array $options Possible runtime options used by the module.
	 * @return object Instance of requested module.
	 */
	function &factory($obj_class, $options = array()) {
		// Load the module
		require_once SDK_PATH . 'lib/lib_' . $obj_class . '.php';
		$cls = 'SDK_' . $obj_class;
		return new $cls($this, $options);
	}

    /**#@+
	 * @group Utilities
	 */
	/**
	 * Loads the configuration file and returns its settings as an associated array.
	 * If the config files is missing, some general, well guessed defaults are returned :)
	 *
	 * This method can be also called statically in your script using <code>$config = IPBSDK::load_config().</code>
	 *
	 * @author CirTap
	 * @return array
	 * @since 1.0.1
	 */
	function load_config() {
		$cfg = FALSE;

		if ( @include(SDK_PATH . 'ipbsdk_conf.inc.php') ) {
			$cfg = get_defined_vars();
		}

		// If we had no config file when try and guess it or if the path is wrong

		if (!$cfg OR !realpath($cfg['root_path'])) {
			$cfg = array();
			if (@$GLOBALS['INFO'] && @$GLOBALS['INFO']['board_url'] && @$GLOBALS['INFO']['base_dir']) {
				$board_url = $GLOBALS['INFO']['board_url'];
				$root_path = $GLOBALS['INFO']['base_dir'];
			} else {
				$board_url = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
				foreach(array('/forum','/forums','/board','/','/Forum','/Forums') as $f) {
					if (is_dir($_SERVER['DOCUMENT_ROOT'] . $f)) {
						$root_path = $_SERVER['DOCUMENT_ROOT'] . $f . DIRECTORY_SEPARATOR;
						$board_url = 'http://' . $board_url . $f;
						break;
					}
				}
			}
			$cfg['board_url']     = $board_url;
			$cfg['root_path']     = $root_path;
			$cfg['allow_caching'] = 1;
			$cfg['sdklang']       = 'en';
			$cfg['board_version'] = 130;
		}

		$cfg['root_path'] = realpath($cfg['root_path']) . DIRECTORY_SEPARATOR;

		return $cfg;
	}

	/**
	* Debuggin helper routine. Writes the given variables $v to the output stream.
	* This method can be also called statically in your script using <code>IPBSDK::dbg_print(arguments).</code>
	*
	* If your PHP script runs in a webpage that also loads a stylesheet you may customize the
	* default visibility of the XMP element by adding <code>pre.DbgPrint xmp.DbgPrint { display:none; }</code>
	* to this page's stylesheet. To <em>visually</em> prevent any output by default use
	* <code>pre.DbgPrint { display:none; }</code>.
	* Note that this ONLY affects the visibility of the generated XMP element, it's content will
	* still exist in this page and contain sensible information!
	*
	* @param mixed $v The variable you may wish to debug
	* @param integer $lines The height of the debug area in CSS em units applied to the XMP element.
	* @param bool $rem Optional: short remark printed above the output. Tip: Use __FILE__ .' '. __LINE_ to locate and identify a particular call of dbg_print() more easily :)
	*
	* @author CirTap <cirtap@otherone.org>
	* @since 1.0.1
	*/
	function dbg_print($v, $lines=0, $rem=FALSE) {
		// a header-like section
		$click = $title = $css = '';
		$rem   = (!$rem) ? '' : $rem;
		if ($lines>0) {
			$click = '<span title="Click here to show/hide debug output" style="color:#c00;cursor:pointer;cursor:hand;" onclick="var cn=this.parentNode.childNodes;var s=cn[cn.length-1].style;s.display=(s.display==\'none\')?\'block\':\'none\';">[+]</span>';
			$title = "title='Click the [+] to show/hide debug output'";
			$css   = "height:{$lines}em;clip:rect(0em,99%,{$lines}em,0em);overflow:auto;";
		}
		if ($lines>0) echo "<pre {$title} class='DbgPrint' style='position:relative;font-size:0.9em;line-height:auto;width:98%;background-color:#efefef;color:black;border:1px solid gray;margin:2px;padding:1px 2px;'>{$click}&nbsp;<b>{$rem}</b>";
		echo "<xmp class='DbgPrint' {$title} style='display:none;position:relative;font-size:11px;line-height:11px;width:99%;{$css}background:white;color:black;border:1px solid gray;margin:0px;padding:2px 4px;'>";
		print_r($v);
		echo '</xmp>';
		if ($lines>0) echo '</pre>';
	}


	/**
	 * Makes a string safe for usage.
	 *
	 * This method can be also called statically in your script using <code>$string = IPBSDK::makesafe($string).</code>
	 *
	 * @param string $value HTML string
	 * @author Cow <khlo@global-centre.com>
	 * @return string safe version of value
	 */
	function makesafe($html) {
		$html = stripslashes($html);
		$html = str_replace ('<!--', '&lt;&#33;--', $html);
		$html = str_replace ('-->', '--&gt;', $html);
		$html = str_replace ('<', '&lt;', $html);
		$html = str_replace ('>', '&gt;', $html);
		$html = str_replace ('&#032;', ' ', $html);
		$html = str_replace ("\n", '<br />', $html);
		$html = str_replace ("'", '&#39;', $html);
		$html = str_replace ('\'', '&quot;', $html);
		return $html;
	}

	/**
	 * Get execution time if timer enabled.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return mixed Time or flase if timer was not enabled.
	 */
	function get_exectime() {
		return ($this->_options['timer']) ? $GLOBALS['Debug']->EndTimer() : FALSE;
	}

	/**
	 * Get database query count.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return integer Amount of queries
	 */
	function get_querycount() {
		return $this->DB->query_count;
	}

    /**#@+
	 * @group Caching
	 */
	/**
	 * Gets function results cache.
	 *
	 * @access private
	 * @author Cow <khlo@global-centre.com>
	 * @param string $function SDK Method who's query results have been cached
	 * @param string $id Key to identify a query from the function
	 * @return mixed Cached item or FALSE if $key does not exist.
	 */
	function get_cache($function, $id) {
		if ($this->ipbsdk_settings['allow_caching'] && array_key_exists($function, $this->_cache)) {
			return (array_key_exists($id, $this->_cache[$function])) ? $this->_cache[$function][$id] : FALSE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Saves/Updates function results cache.
	 *
	 * @access private
	 * @author Cow <khlo@global-centre.com>
	 * @param string $function SDK Method who's query results have been cached
	 * @param string $id Key to identify a query from the function
	 * @param string $data Data being cached
	 * @return void
	 */
	function save_cache($function, $id, $data) {
		if ($this->ipbsdk_settings['allow_caching']) {
			$this->_cache[$function][$id] = $data;
		}
	}

	/**
	 * Attempts to find some value/object in the cache for cross variable assignments.
	 *
	 * @author CirTap <cirtap@otherone.org>
	 * @param string $function SDK Method who's query results have been cached
	 * @param string $key Key to search for in this method's results
	 * @return mixed value/object whatever found in cache
	 */
	function find_cache($function, $key) {
		// Firstly see if caching is disabled
		if (!$this->ipbsdk_settings['allow_caching']) {
			return FALSE;
		}

		$data = array();
		if ($this->_cache[$function]) {
			foreach (array_keys($this->_cache[$function]) as $id) {
				$vtype = gettype($this->_cache[$function][$id]);
				if ($vtype == 'array' && isset($this->_cache[$function][$id][$key])) {
					// find array element
					$val = &$this->_cache[$function][$id][$id][$key];
				} else if ($vtype == 'object' && isset($this->_cache[$function][$id]->$key)) {
					// find object property
					$val = &$this->_cache[$function][$id]->$key;
				} else {
					// find value
					$val = &$this->_cache[$function][$id];
				}

				if (isset($val)) {
					$data[] = $val;
				}

				unset($val);
			}
		}
		return $data;
	}

	// -----------------------------------------------
	// Main SDK Functions Follow...
	// -----------------------------------------------

	// -----------------------------------------------
	// BBCODE FUNCTIONS
	// If you like BBCode these thingys are for you :P
	// -----------------------------------------------
    /**#@+
	 * @group BBCode
	 */
	/**
	 * Converts BBCode to HTML using IPB's native
	 * parser.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return string HTML version of input
	 * @see html2bbcode(), parse_dohtml()
	 */
	function bbcode2html($input, $smilies = '1') {
		$input = $GLOBALS['parser']->convert(array('TEXT' => $input, 'SMILIES' => $smilies, 'CODE' => 1, 'SIGNATURE' => 0, 'HTML' => 0));

		return $input;
	}

	/**
	 * Converts HTML to BBCode using IPB's native parser.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return string BBCode version of input
	 * @see bbcode2html(), parse_dohtml()
	 */
	function html2bbcode($input) {
		$input = $GLOBALS['parser']->unconvert($input, 1);

		return $input;
	}

	/**
	 * Parses [doHTML] tags.
	 * This is a wrapper for IPB's native parser. You can turn doHTML parsing on or off.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $input String to parse for BBCode
	 * @param integer $dohtml Parse for doHTML tags; 1 = yes (default), 0 = no
	 * @return string doHTML parsed version of input
	 */
	function parse_dohtml($input, $dohtml = '1') {
		$input = $GLOBALS['parser']->post_db_parse($input, $dohtml);

		return $input;
	}

	// -----------------------------------------------
	// CACHE STORE FUNCTIONS
	// The IPB Cache Store is something very little
	// people know about but can be very very useful.
	// -----------------------------------------------
    /**#@+
	 * @group CacheStore
	 */
	/**
	 * List all cache stores.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array all cache store key, values and extra.
 	 * @see set_cache_store_value(), get_cache_store_value(), search_cache_store()
	 */
	function list_cache_stores() {
		if ($cache = $this->get_cache('list_cache_stores', '1')) {
			return $cache;
		} else {
			$this->DB->query ('SELECT cs_key, cs_value, cs_extra FROM acf_cache_store');
			$cs = array();

			while ($row = $this->DB->fetch_row()) {
				$cs[$row['cs_key']] = $row;
			}

			$this->save_cache('list_cache_stores', '1', $cs);
			return $cs;
		}
	}

	/**
	 * Get the value of a cache store.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $key Key of the cache store
	 * @return string value of a cache store.
	 * @see set_cache_store_value(), list_cache_stores(), search_cache_store()
	 */
	function get_cache_store_value($key) {
		$cs = $this->list_cache_stores();

		if ($cs[$key]) {
			return $cs[$key]['cs_value'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Sets or updates the value of a cache store.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $key Key of the cache store
	 * @param string $value Value to store
	 * @return bool TRUE on success.
	 * @see get_cache_store_value(), list_cache_stores(), search_cache_store()
	 */
	function set_cache_store_value($key, $value = '') {
		$cs = $this->list_cache_stores();

		if ($cs[$key]) {
			// Already exists so just use UPDATE
			$this->DB->query ("UPDATE acf_cache_store SET cs_value='" . $value . "' WHERE cs_key='" . $key . "'");

			if ($this->DB->get_affected_rows()) {
				// And update our cached copy
				$cs[$key] = array('cs_key' => $key,
					'cs_value' => $value,
					'cs_extra' => '',
					);

				$this->save_cache('list_cache_stores', '1', $cs);

				return TRUE; // We're done.
			} else {
				// What happened? I dunno.
				return FALSE;
			}
		} else {
			// Doesn't exist so use INSERT
			$this->DB->query ("INSERT INTO acf_cache_store (cs_key, cs_value) VALUES ('" . $key . "', '" . $value . "')");
			if ($this->DB->get_affected_rows()) {
				// And update our cached copy
				$cs[$key] = array('cs_key' => $key,
					'cs_value' => $value,
					'cs_extra' => '',
					);

				$this->save_cache('list_cache_stores', '1', $cs);
				return TRUE; // And your done...
			} else {
				// I don't know why on earth it wouldn't work but it might not
				return FALSE;
			}
		}
	}

	/**
	 * Searches the cache store.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param mixed $value Storage value to search
	 * @param bool $exactmatch Use exact matching or wildcard search
	 * @return array cache stores matching criteria
  	 * @see set_cache_store_value(), get_cache_store_value(), list_cache_stores()
	 */
	function search_cache_store($value, $exactmatch = FALSE) {
		// Do the SQL Query
		if ($exactmatch) {
			$this->DB->query ("SELECT * FROM acf_cache_store WHERE cs_value='" . $value . "'");
		} else {
			$this->DB->query ("SELECT * FROM acf_cache_store WHERE cs_value LIKE '%" . $value . "%'");
		}

		$cs = array();

		while ($row = $this->DB->fetch_row()) {
			$cs[$row['cs_key']] = $row;
		}

		return $cs;
	}

	// -----------------------------------------------
	// CATEGORIES FUNCTIONS
	// Do stuff with categories.
	// -----------------------------------------------
    /**#@+
	 * @group Categories
	 */
	/**
	 * List categories.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Board Categories
	 * @see get_category_info(), new_category()
	 */
	function list_categories() {
		if ($cache = $this->get_cache('list_categories', '1')) {
			return $cache;
		} else {
			$this->DB->query ("SELECT * FROM acf_categories WHERE id != '-1'");
			$cat = array();

			while ($row = $this->DB->fetch_row()) {
				$cat[$row['id']] = $row;
			}

			$this->save_cache('list_categories', '1', $cat);
			return $cat;
		}
	}

	/**
	 * Get Information on a Category
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $categoryid Unique ID of the category
	 * @return array Information on category categoryid
	 * @see list_categories(), new_category()
	 */
	function get_category_info($categoryid) {
		$cats = $this->list_categories();

		if ($cats[$categoryid]) {
			return $cats[$categoryid];
		} else {
			// Category doesn't exist.
			return FALSE;
		}
	}

	/**
	 * Creates a new category.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $name Name of the category to create
	 * @return mixed Category ID or FALSE on failure
	 * @see list_categories(), get_category_info()
	 */
	function new_category($name) {
		// Grab the biggest number
		$this->DB->query('SELECT MAX(id) as mmpie FROM acf_categories');
		// This SQL Query was dedicated to Weebl and Bob.
		$row = $this->DB->fetch_row();

		$id = intval($row['mmpie']) + 1;
		// Insert it
		$this->DB->query ("INSERT INTO acf_categories (id,position,state,name,description,image,url) VALUES ('" . $id . "','" . $id . "','1','" . $this->makesafe($name) . "','','','') ");
		// Why on earth do they bother creating fields for description, image and URL if they do nothing useful?
		// Check it worked
		if ($this->DB->get_affected_rows()) {
			return $id;
		} else {
			return FALSE;
		}
	}

	// -----------------------------------------------
	// CUSTOM FIELDS FUNCTIONS
	// You know those special fields in profiles :)
	// -----------------------------------------------
    /**#@+
	 * @group CustomFields
	 */
	/**
	 * Gets the value of a custom profile field for a given member.
	 * If $memberid is ommitted, the last known member id is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $fieldid Field ID (number) to retrieve.
	 * @param integer $memberid Member ID to read the custom profile field from.
	 * @return string Value of memberid's custom profile field field-id
	 * @see list_customfields(), update_customfield()
	 */
	function get_customfield_value($fieldid, $memberid = '') {
		if ($memberid) {
			$info = $this->get_advinfo($memberid);
		} else {
			$info = $this->get_advinfo();
		}

		if ($info['field_' . $fieldid]) {
			return $info['field_' . $fieldid];
		} else {
			return FALSE;
		}
	}

	/**
	 * Grab a list of custom profile fields, and their properties.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array custom profile fields and properties
	 * @see get_customfield_value(), update_customfield()
	 */
	function list_customfields() {
		// Check for cache...
		if ($cache = $this->get_cache('list_customfields', '1')) {
			return $cache;
		} else {
			$this->DB->query ('SELECT * FROM acf_pfields_data ORDER BY fid');
			if ($this->DB->get_num_rows()) {
				while ($info = $this->DB->fetch_row()) {
					$fields['field_' . $info['fid']] = $info;
				}

				$this->save_cache('list_customfields', '1', $fields);

				return $fields;
			} else {
				return array();
			}
		}
	}

	/**
	 * Updates the value of a custom profile field.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id
	 * @param string $newvalue
	 * @param bool $bypassperms
	 * @return bool whether the action was successful
	 * @see list_customfields(), get_customfield_value()
	 */
	function update_customfield($id, $newvalue, $bypassperms = FALSE) {
		$bypassperms = (bool)$bypassperms;
		$fieldinfo = $this->list_customfields();
		if ($info = $fieldinfo['field_' . $id]) {
			if ($info['fedit'] OR $bypassperms) {
				if ($info['ftype'] == 'drop') {
					$allowed = array();

					$i = explode ('|', $info['fcontent']);
					foreach ($i as $j) {
						$k = explode ('=', $j);
						$allowed[] = $k['0'];
					}

					if (!in_array($newvalue, $allowed)) {
						$this->sdkerror($this->lang['sdk_cfinvalidvalue']);
						return FALSE;
					}
				}

				if ($info['freq'] AND !$newvalue) {
					$this->sdkerror(sprintf($this->lang['sdk_cfmustfillin']), $id);
					return FALSE;
				}

				$this->DB->query ("UPDATE acf_pfields_content SET field_" . $id . "='" . $newvalue . "' WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
				return TRUE;
			} else {
				$this->sdkerror(sprintf($this->lang['sdk_cfcantedit'], $id));
				return FALSE;
			}
		} else {
			$this->sdkerror(sprintf($this->lang['sdk_cfnotexist'], $id));
			return FALSE;
		}
	}

	// -----------------------------------------------
	// EMAIL FUNCTIONS
	// Sends an e-mail to a member.
	// -----------------------------------------------
    /**#@+
	 * @group EMail
	 */
	/**
	 * Sends an email to a member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id Member ID
	 * @param string $subject Message subject
	 * @param string $message Message body
	 * @return bool Success
	 */
	function mail_member($id, $subject, $message) {
		if ($info = $this->get_advinfo($id)) {
			if (!$this->extra['emailer']) {
				// OMG, a usable lib ;)
				$this->extra = array_merge($this->extra, ipb_set_objects(array('emailer'), TRUE));
			}

			$this->extra['emailer']->to = $info['email']; // Set to
			$this->extra['emailer']->subject = $subject;
			$this->extra['emailer']->template = $this->lang['sdk_email_template']; // Oh dear

			$this->extra['emailer']->build_message(array('MESSAGE' => $message));

			$this->extra['emailer']->send_mail();
			return TRUE;
		} else {
			return FALSE;
		}
	}

	// -----------------------------------------------
	// EMOTICONS FUNCTIONS
	// Functions to do with Emoticons.
	// -----------------------------------------------
    /**#@+
	 * @group Emoticons
	 */
	/**
	 * List emoticons, optional limit the result to clickable emoticons only.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @author Foxrer
	 * @param bool $clickable Clickable emoticons only. Default: FALSE
	 * @return array Assoc array with Emoticons, keys 'typed', 'image'
	 */
	function list_emoticons($clickable = FALSE) {
		if ($clickable) {
			$this->DB->query ("SELECT typed, image FROM acf_emoticons WHERE clickable='1'");
		} else {
			$this->DB->query ("SELECT typed, image FROM acf_emoticons");
		}

		$this->DB->query ("SELECT typed, image FROM acf_emoticons");
		$emos = array();

		while ($row = $this->DB->fetch_row()) {
			$row['typed'] = str_replace(array('&lt;', '&gt;'), array('<', '>'), $row['typed']);
			$emos[$row['typed']] = $row['image'];
		}

		return $emos;
	}

	// -----------------------------------------------
	// GROUP FUNCTIONS
	// Group stuff
	// -----------------------------------------------
    /**#@+
	 * @group Groups
	 */
	/**
	 * Returns information on a group.
	 * If $group is ommited, the last known group (of the last member) is used.
	 *
	 * @author CTiga <crouchintiga@comcast.net>
	 * @param integer $group Group ID
	 * @return array Group Information
	 */
	function get_groupinfo($group = '') {
		if (!$group) {
			// No Group? Return current group info
			$group = $GLOBALS['ibforums']->member['mgroup'];
		}
		// Check for cache - if exists don't bother getting it again
		if ($cache = $this->get_cache('get_groupinfo', $group)) {
			return $cache;
		} else {
			// Return group info if group given
			$this->DB->query ("SELECT g.* FROM acf_groups g WHERE g_id='" . intval($group) . "'");
			if ($this->DB->get_num_rows()) {
				$info = $this->DB->fetch_row();
				$this->save_cache('get_groupinfo', $group, $info);
				return $info;
			} else {
				return FALSE;
			}
		}
	}

	// -----------------------------------------------
	// SDK FUNCTIONS
	// Misc functions which don't interact with IPB
	// but the SDK.
	// -----------------------------------------------
    /**#@+
	 * @group SDK
	 */
	/**
	 * Adds an error message to the list of existing SDK error messages.
	 * Sets the last error property which get be retrieved by sdk_error()
	 *
	 * @access private
	 * @author Cow <khlo@global-centre.com>
	 * @param string $error
	 * @return void
	 * @see sdk_error()
	 */
	function sdkerror($error) {
		// Update _lasterror
		$this->_lasterror = $error;
		$this->_errors[] = $error;
	}

	/**
	 * Returns the last error message generated by IPB SDK.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return string Last error generated by IPB SDK
	 */
	function sdk_error() {
		if ($this->_lasterror) {
			return $this->_lasterror;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns SDK version.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return string SDK Version Number.
	 * @since 1.0.0 Returns a phpversion() compliant format.
	 */
	function sdk_version() {
		return $this->ipbsdk_version;
	}

	/**
	 * Prints a useful page with debug information on IPB SDK and the things behind the scene.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @since 1.1 Out-sourced to reduce file-weight of the main class.
	 */
	function sdk_info() {
		@include(SDK_PATH . 'lib/sdk_info.inc');
	}

	/**
	 * Returns textual/timestamp offsetted date by board and by
	 * member offset and DST setting.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $timestamp Numeric representation of the time beeing formatted
	 * @param string $dateformat date() compliant format (see PHP manual)
	 * @param integer $noboard 1=Offset with Board Time firstly, default = 0
	 * @param integer $nomember 1=Bypass member's time offset and DST, default = 0
	 * @return mixed textual/timestamp offsetted date by board and by member offset and DST setting.
	 */
	function sdk_date($timestamp = '', $dateformat = '', $noboard = '0', $nomember = '0') {
		// Strictly not IPB SDK related but so what :)
		// Grab Member Settings - We rely on get_advinfo() for this :)
		$info = $this->get_advinfo();
		// If theres no timestamp make it current time using time()
		if (!$timestamp) {
			$timestamp = time();
		}
		// Offset with Board Time firstly, if enabled
		// Also Check no member offset
		if (!$noboard) {
			if (!$nomember AND !$info['time_offset']) {
				$timestamp = $timestamp + ($GLOBALS['ibforums']->vars['time_offset'] * 60);
			}
		}
		// Board Time Adjust
		if ($GLOBALS['ibforums']->vars['time_adjust']) {
			$timestamp = $timestamp + ($GLOBALS['ibforums']->vars['time_adjust'] * 60);
		}
		// This is where website integration get's really good :)
		// If member has set an indiviual offset in the User CP
		// because they may be in a totally different country
		// using DST or whatever we can make those times affect it
		// across the whole website as well :D
		if ($this->loggedin AND !$nomember) {
			if ($info['time_offset']) {
				$timestamp = $timestamp + ($info['time_offset'] * 3600);
			}

			if ($info['dst_in_use']) {
				$timestamp = $timestamp + 3600;
			}
		}

		if ($dateformat) {
			$timestamp = date($dateformat, $timestamp);
		}

		return $timestamp;
	}

	/**
	 * Changes the SDK Language Pack.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $language Language code. Must match the code used for the language filename
	 * @return bool success
	 */
	function sdk_set_language($language) {
		// Great stuff...
		if (file_exists(SDK_PATH . 'lib/lang_ipbsdk_' . $language . '.php')) {
			if (include(SDK_PATH . 'lib/lang_ipbsdk_' . $language . '.php')) {
				// Change $this->lang
				$this->lang = $lang;
				unset($lang);
				// And update _options
				$this->_options['language'] = $language;
				// Done!!!
				return TRUE;
			} else {
				// Can't include it. Return FALSE.
				return FALSE;
			}
		} else {
			// Doesn't exist. Invalid Language.
			return FALSE;
		}
	}

	// -----------------------------------------------
	// MEMBER FUNCTIONS
	// Functions which interact with IPB's member system.
	// -----------------------------------------------
    /**#@+
	 * @group Members
	 */
	/**
	 * Returns current member's login status.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return bool Whether the current user is logged in
	 */
	function is_loggedin() {
		return (bool) $this->loggedin;
	}

	/**
	 * Returns whether a member is a super moderator.
	 * If $memberid is ommited, the last known member id is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return bool Whether currently logged in member is a Super Moderator
	 */
	function is_supermod($memberid = '') {
		if (!$memberid) {
			// Current Member
			if ($GLOBALS['ibforums']->member['g_is_supmod']) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			if ($sm = $this->get_advinfo($memberid)) {
				return $sm['g_is_supmod'];
			} else {
				$this->sdkerror($this->lang['sdk_badmemid']);
				return FALSE;
			}
		}
	}

	/**
	 * Returns whether a member can access the board's Admin CP.
	 * If $memberid is ommited, the last known member id is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return bool Whether currently logged in member can access ACP
	 */
	function is_admin($memberid = '') {
		if (!$memberid) {
			// Current Member
			if ($GLOBALS['ibforums']->member['g_access_cp']) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			if ($a = $this->get_advinfo($memberid)) {
				return $a['g_access_cp'];
			} else {
				$this->sdkerror($this->lang['sdk_badmemid']);
				return FALSE;
			}
		}
	}

	/**
	 * Returns whether a member is in the specified group(s).
	 * If $memberid is ommited, the last known member id is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $group Group ID or array of groups-ids separated with comma: 2,5,7
	 * @param integer $member Member ID to find
	 * @return mixed Whether member is in group(s)
	 */
	function is_ingroup($group, $member = '') {
		if (!is_array($group)) $group = explode(',', $group);
		settype($group, 'array');
		if ($member) {
			$this->DB->query ("SELECT mgroup FROM acf_members WHERE id='" . $member . "'");
			if ($row = $this->DB->fetch_row()) {
				if (in_array($row['mgroup'], $group)) {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		} else {
			if (in_array($GLOBALS['ibforums']->member['mgroup'], $group)) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Creates a new account and returns the member ID for further processing.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $username Username
	 * @param string $password In plain text. Will be encrypted with md5()
	 * @param string $email Mail
	 * @param array $customfields Optional values for the (existing) custom profile fields.
	 * @return long New Member ID or FALSE on failure
	 * @since 1.1 Returns the member ID on success rather than TRUE
	 */
	function create_account($username, $password, $email, $customfields = array()) {
		$cfields = array();
		// Custom Profile Stuff
		$this->DB->query("SELECT * from acf_pfields_data WHERE fedit='1'");
		while ($row = $this->DB->fetch_row()) {
			// Required and No Field Specified? return FALSE
			if ($row['freq'] AND !$customfields[$row['fid']]) {
				$this->sdkerror($this->lang['sdk_cfmissing']);
				return FALSE;
			}
			// Is it too long?
			if ($row['fmaxinput'] > 0) {
				if (strlen($customfields[$row['fid']]) > $row['fmaxinput']) {
					$this->sdkerror($this->lang['sdk_cflength']);
					return FALSE;
				}
			}

			$cfields['field_' . $row['fid']] = str_replace('<br>', "\n", $customfields[$row['fid']]);
		}
		// Check and Clean Username, Password & Email
		$username = trim(str_replace('|', '&#124;' , $username));
		$password = trim($password);
		$email = strtolower(trim($email));
		// Strip Multiple Spaces
		$username = preg_replace("/\s{2,}/", ' ', $username);

		if (empty($username) OR strlen($username) < 3 OR strlen($username) > 32) {
			$this->sdkerror($this->lang['sdk_acc_user']);
			return FALSE;
		}
		if (empty($password) OR strlen($password) < 3 OR strlen($password) > 32) {
			$this->sdkerror($this->lang['sdk_acc_pass']);
			return FALSE;
		}

		$email = $GLOBALS['std']->clean_email($email);

		if (empty($email) OR strlen($email) < 6) {
			$this->sdkerror($this->lang['sdk_acc_email']);
			return FALSE;
		}
		// Already taken?
		$this->DB->query("SELECT id FROM acf_members WHERE LOWER(name)='" . strtolower($username) . "' OR email='" . $email . "'");
		if ($this->DB->get_num_rows() OR strtolower($username) == 'guest') {
			$this->sdkerror($this->lang['sdk_acc_taken']);
			return FALSE;
		}
		// Reserved?
		if ($GLOBALS['ibforums']->vars['ban_names']) {
			$reserved = explode ('|', $GLOBALS['ibforums']->vars['ban_names']);
			foreach ($reserved as $i) {
				if ($i != '') {
					if (preg_match("/" . preg_quote($i, "/") . "/i", $in_username)) {
						$this->sdkerror($this->lang['sdk_acc_user']);
						return FALSE;
					}
				}
			}
		}
		// Insert all into Database
		$this->DB->query("SELECT MAX(id) as new_id FROM acf_members");
		$r = $this->DB->fetch_row();

		$member_id = $r['new_id'] + 1;

		$member = array('id' => $member_id,
			'name' => $username,
			'password' => md5($password),
			'email' => $email,
			'mgroup' => $GLOBALS['ibforums']->vars['member_group'],
			'posts' => 0,
			'avatar' => 'noavatar',
			'joined' => time(),
			'ip_address' => $GLOBALS['ibforums']->input['IP_ADDRESS'],
			'time_offset' => $GLOBALS['ibforums']->vars['time_offset'],
			'view_sigs' => 1,
			'email_pm' => 1,
			'view_img' => 1,
			'view_avs' => 1,
			'restrict_post' => 0,
			'view_pop' => 1,
			'vdirs' => 'in:Inbox|sent:Sent Items',
			'msg_total' => 0,
			'new_msg' => 0,
			'coppa_user' => 0,
			'language' => $GLOBALS['ibforums']->vars['default_language'],
			);

		$DB_string = $GLOBALS['std']->compile_db_string($member);

		$this->DB->query("INSERT INTO acf_members (" . $DB_string['FIELD_NAMES'] . ") VALUES (" . $DB_string['FIELD_VALUES'] . ")");
		$this->DB->query("INSERT INTO acf_member_extra (id) VALUES ($member_id)");

		unset($DB_string);
		// Custom Fields
		$this->DB->query("DELETE FROM acf_pfields_content WHERE member_id=" . $member['id']);
		$this->DB_string = $this->DB->compile_db_insert_string($cfields);

		$fields = $this->DB_string['FIELD_NAMES'] ? ', ' . $this->DB_string['FIELD_NAMES'] : '';
		$values = $this->DB_string['FIELD_VALUES'] ? ', ' . $this->DB_string['FIELD_VALUES'] : '';

		$this->DB->query("INSERT INTO acf_pfields_content (member_id" . $fields . ") VALUES(" . $member_id . $values . ")");

		unset($this->DB_string);
		// STATS!!!!!!!!
		$this->DB->query("UPDATE acf_stats SET MEM_COUNT=MEM_COUNT+1, LAST_MEM_NAME='" . $member['name'] . "', LAST_MEM_ID='" . $member['id'] . "'");
		// Finally
		return $member_id;
	}

	/**
	 * Login a user.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @author CTiga <crouchintiga@comcast.net>
	 * @param string $username
	 * @param string $password
	 * @param integer $cookie Default: 1=Use cookie to save login session, 0=no cookies
	 * @param integer $anon Default: 0=Keep user anonymous on forums, 1=keep anon.
	 * @param integer $sticky Default: 1='Remember Me' cookie, 0=auto log off when session expires
	 * @return bool Success.
	 */
	function login($username, $password, $cookie = '1', $anon = '0', $sticky = '1') {
		$username = $GLOBALS['std']->txt_stripslashes($username);
		$username = preg_replace("/&#([0-9]+);/", '-', $username);
		$username = $this->makesafe($username);
		$password = $GLOBALS['std']->txt_stripslashes($password);
		$password = preg_replace("/&#([0-9]+);/", '-', $password);
		$password = $this->makesafe($password);

		$sticky = $sticky ? '1' : '0';

		if (!$username OR !$password) {
			$this->sdkerror($this->lang['sdk_login_nofields']);
			return FALSE;
		}
		if (strlen($username) > 32 OR strlen($password) > 32) {
			$this->sdkerror($this->lang['sdk_login_length']);
			return FALSE;
		}

		$username = strtolower(str_replace('|', '&#124;', $username));
		$password = md5($password);
		// Query
		$this->DB->query ("SELECT m.name, m.id, m.password, m.email, m.title, m.mgroup, m.view_sigs, m.view_img, m.view_avs, g.* FROM acf_members m LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE LOWER(name)='" . $username . "'");

		if ($member = $this->DB->fetch_row()) {
			// Wrong PW?
			if ($member['password'] != $password) {
				$this->sdkerror($this->lang['sdk_login_wrongpass']);
				return FALSE;
			}
			// Just incase
			if (!$member['id']) {
				$this->sdkerror($this->lang['sdk_login_memberid']);
				return FALSE;
			}
			// Still here... Means its Okely Doke
			if ($cookie) {
				$sid = md5(uniqid(microtime()));

				$GLOBALS['std']->my_setcookie('member_id', $member['id'], $sticky);
				$GLOBALS['std']->my_setcookie('pass_hash', $password, $sticky);
				$GLOBALS['std']->my_setcookie('session_id', $sid, $sticky);
				if ($anon) {
					$GLOBALS['std']->my_setcookie('anonlogin', 1, $sticky);
				}
				// Destroy Sessions
				$this->DB->query("DELETE FROM acf_sessions WHERE ip_address='" . $_SERVER['REMOTE_ADDR'] . "'");
				// Create Session
				$id = $member['id'];
				$browser = substr($_SERVER['HTTP_USER_AGENT'], 0, 64);
				$ip = substr($_SERVER['REMOTE_ADDR'], 0, 16);

				$this->DBstring = $this->DB->compile_db_insert_string(array('id' => $sid,
						'member_name' => $member['name'],
						'member_id' => $member['id'],
						'running_time' => time(),
						'member_group' => $member['mgroup'],
						'ip_address' => $ip,
						'browser' => $browser,
						'login_type' => $anon ? '1' : '0'
						));

				$this->DB->query('INSERT INTO acf_sessions (' . $this->DBstring['FIELD_NAMES'] . ') VALUES (' . $this->DBstring['FIELD_VALUES'] . ')');
			}

			return $member;
		} else {
			$this->sdkerror($this->lang['sdk_login_nomember']);
			return FALSE;
		}
	}

	/**
	 * Logout a user.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return bool success
	 */
	function logout() {
		// Try and p00p the sessions
		$this->DB->query("UPDATE acf_sessions SET member_name='', member_id='0', login_type='0' WHERE id='" . $GLOBALS['sess']->session_id . "'");
		$this->DB->query("UPDATE acf_members SET last_visit='" . time() . "', last_activity='" . time() . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");

		$GLOBALS['std']->my_setcookie('member_id', '0');
		$GLOBALS['std']->my_setcookie('pass_hash', '0');
		$GLOBALS['std']->my_setcookie('anonlogin', '-1');

		return TRUE;
	}

	/**
	 * Gets the Member ID associated with a Member Name.
	 *
	 * If you pass an array with names, the function also returns an array with
	 * each name beeing the key and the ID as its value. If a member name could
	 * not be found, the value will be set to FALSE.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param mixed $names String
	 * @return mixed Single Member ID, assoc. array with id/name pairs, or FALSE if the name(s) could not be found
	 * @see id2name()
	 */
	function name2id($names) {
		if (is_array($names)) {
			foreach ($names as $i => $j) {
				$this->DB->query ("SELECT id FROM acf_members WHERE LOWER(name)='" . $j . "'");
				if ($row = $this->DB->fetch_row()) {
					$ids[$i] = $row['id'];
				} else {
					$ids[$i] = FALSE;
				}
			}

			return $ids;
		} else {
			$this->DB->query ("SELECT id FROM acf_members WHERE LOWER(name)='" . $names . "'");
			if ($row = $this->DB->fetch_row()) {
				return $row['id'];
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Gets the Member Name associated with a Member ID.
	 *
	 * If you pass an array with IDs, the function also returns an array with
	 * each ID beeing the key and the member name as its value. If a member ID
	 * could not be found, the value will be set to FALSE.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id
	 * @return mixed Single member name, assoc. array with name/id pairs, or FALSE if the ID(s) could not be found
	 * @see name2id()
	 */
	function id2name($id) {
		if (is_array($id)) {
			foreach ($id as $i => $j) {
				if ($row = $this->get_advinfo($j)) {
					$names[$i] = $row['name'];
				} else {
					$names[$i] = FALSE;
				}
			}

			return $names;
		} else {
			if ($row = $this->get_advinfo($id)) {
				return $row['name'];
			} else {
				return FALSE;
			}
		}
	}


	/**
	 * Returns basic information on a member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return array Basic Information on a member, or FALSE on failure
	 * @see get_advinfo(),get_avatar(),get_raw_sig(),get_photo(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_info($memberid = '') {
		// No caching in this function or anything good which
		// will be better then get_advinfo(). So use that if possible.
		// However I guess you could get $ibforums->member with it and
		// its good for easy backward compatibility so I'll keep it in
		// here.
		if (!$memberid) {
			// No UID? Return current user info
			return ($GLOBALS['ibforums']->member);
		} else {
			// Return user info if UID given
			$this->DB->query ("SELECT m.name, m.id, m.password, m.email, m.title, m.mgroup, m.view_sigs, m.view_img, m.view_avs, g.* FROM acf_members m LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE id='" . intval($memberid) . "'");
			if ($this->DB->get_num_rows()) {
				$info = $this->DB->fetch_row();

				return $info;
			} else {
				$this->sdkerror($this->lang['sdk_badmemid']);
				return FALSE;
			}
		}
	}

	/**
	 * Grabs detailed information on a member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return array Member Information, or FALSE on failure
	 * @see get_info(),get_avatar(),get_raw_sig(),get_photo(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_advinfo($memberid = '') {
		if (!$memberid) {
			// No UID? Return current user info
			$memberid = $GLOBALS['ibforums']->member['id'];
		}
		// Check for cache - if exists don't bother getting it again
		if ($cache = $this->get_cache('get_advinfo', $memberid)) {
			return $cache;
		} else {
			// Return user info if UID given
			$this->DB->query ("SELECT m.*, g.*, cf.* FROM acf_members m LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) LEFT JOIN acf_pfields_content cf ON (cf.member_id=m.id) WHERE id='" . intval($memberid) . "'");
			if ($this->DB->get_num_rows()) {
				$info = $this->DB->fetch_row();
				$this->save_cache('get_advinfo', $memberid, $info);

				return $info;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Returns the HTML code to show a member's avatar.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return string HTML Code for member's avatar, or FALSE on failure
	 * @see get_info(),get_advinfo(),get_raw_sig(),get_photo(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_avatar($member = '') {
		// No Member ID specified? Go for the current users UID.
		if (!$member) {
			$member = $GLOBALS['ibforums']->member['id'];
		}
		// Get Avatar Info
		if ($row = $this->get_advinfo($member)) {
			return $GLOBALS['std']->get_avatar ($row['avatar'], 1, $row['avatar_size']);
		} else {
			$this->sdkerror($this->lang['sdk_badmemid']);
			return FALSE;
		}
	}

	/**
	 * Get member's sig in BBCode
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return string Member Code in BBCode.
	 * @see get_sig(),update_sig(),get_info(),get_advinfo(),get_avatar(),get_photo(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_raw_sig($memberid = '') {
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}

		if ($info = $this->get_advinfo($memberid)) {
			return $GLOBALS['parser']->unconvert($info['signature'], $GLOBALS['ibforums']->vars['sig_allow_ibc'], $GLOBALS['ibforums']->vars['sig_allow_html']);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns HTML code for member photo.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return string HTML code for member photo
	 * @see update_photo(),get_info(),get_advinfo(),get_avatar(),get_raw_sig(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_photo($memberid = '') {
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}
		// Random Comment. Homer bad, Cow good.
		$this->DB->query ("SELECT photo_type, photo_location, photo_dimensions FROM acf_member_extra WHERE id='" . intval($memberid) . "'");
		if ($row = $this->DB->fetch_row()) {
			if ($row['photo_type'] AND $row['photo_location']) {
				$dimensions = explode (',', $row['photo_dimensions']);

				$height = ($dimensions['0'] > 0) ? 'height="' . $dimensions['0'] . '" ' : '';
				$width = ($dimensions['1'] > 0) ? 'width="' . $dimensions['1'] . '" ' : '';

				if ($row['photo_type'] == 'url') {
					$photourl = $row['photo_location'];
				} else {
					$photourl = $GLOBALS['ibforums']->vars['upload_url'] . '/' . $row['photo_location'];
				}
				// Generate Photo HTML Code
				return '<img src="' . $photourl . '" ' . $height . $width . 'alt="'.$this->lang['sdk_memphoto'].'" />';
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the amount of pips a member has.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id
	 * @return int Member Pips Count
	 * @see get_info(),get_advinfo(),get_avatar(),get_raw_sig(),get_photo(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function get_member_pips($id = '') {
		if (!$id) {
			$id = $GLOBALS['ibforums']->member['id'];
		}

		if ($info = $this->get_advinfo($id)) {
			// Grab Pips
			$this->DB->query ('SELECT * FROM acf_titles ORDER BY pips ASC');
			$pips = '0';
			// Loop through pip numbers checking which is good
			while ($row = $this->DB->fetch_row()) {
				if ($row['posts'] <= $info['posts']) {
					$pips = $row['pips'];
				}
			}

			return $pips;
		} else {
			$this->sdkerror($this->lang['sdk_badmemid']);
			return FALSE;
		}
	}

	/**
	 * Returns a member's icon in HTML
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id
	 * @return string HTML for member's icon
	 * @see get_info(),get_advinfo(),get_avatar(),get_raw_sig(),get_photo(),get_member_pips(),get_num_new_posts(),get_skin_id()
	 */
	function get_member_icon($memberid = '') {
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}

		if ($info = $this->get_advinfo($memberid)) {
			if ($info['g_icon']) {
				// Use Group Icon
				return '<img src="' . $GLOBALS['ibforums']->vars['html_url'] . '/team_icons/' . $info['g_icon'] . '" border="0" alt="'.$this->lang['sdk_groupicon'].'" />';
			} else {
				// Use Pips
				$pips = $this->get_member_pips($memberid);
				$pipsc = '';
				$skininfo = $this->get_skin_info($this->get_skin_id());

				while ($pips > 0) {
					$skininfo['img_dir'] = $skininfo['img_dir'] ? $skininfo['img_dir'] : '1';
					$pipsc .= '<img src="' . $this->_options['board_url'] . '/style_images/' . $skininfo['img_dir'] . '/pip.gif" border="0"  alt="*" />';
					$pips = $pips - '1';
				}

				return $pipsc;
			}
		} else {
			$this->sdkerror($this->lang['sdk_badmemid']);
			return FALSE;
		}
	}

	/**
	 * Returns the number of new posts of the currently logged in member since its last visit.
	 *
	 * @author CTiga <crouchintiga@comcast.net>
	 * @return int Number of posts since last visit
	 * @see get_info(),get_advinfo(),get_avatar(),get_raw_sig(),get_photo(),get_member_pips(),get_member_icon(),get_skin_id()
	 */
	function get_num_new_posts() {
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$this->DB->query("SELECT COUNT(pid) AS new FROM acf_posts WHERE post_date > '".$GLOBALS['ibforums']->member['last_visit']."'");
		if ($posts = $this->DB->fetch_row()) {
			return $posts['new'];
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Update the current member's signature
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $newsig New signature text. HTML allowed as per board settings.
	 * @return bool success.
	 * @see get_raw_sig(),update_member(),update_sig(),get_info(),get_advinfo(),get_avatar(),get_photo(),get_member_pips(),get_member_icon(),get_num_new_posts(),get_skin_id()
	 */
	function update_sig($newsig) {
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$newsig = $this->makesafe($newsig);
		$memberinfo = $this->get_advinfo();

		if (strlen($newsig) > $GLOBALS['ibforums']->vars['max_sig_length']) {
			$this->sdkerror($this->lang['sdk_sig_toolong']);
			return FALSE;
		}

		if ($GLOBALS['ibforums']->vars['sig_allow_html'] AND $memberinfo['g_dohtml']) {
			$newsig = $GLOBALS['parser']->parse_html($newsig, 0);
		}
		if ($GLOBALS['ibforums']->vars['sig_allow_ibc'] AND $memberinfo) {
			$newsig = $GLOBALS['parser']->convert(array('TEXT' => $newsig, 'SIGNATURE' => '1', 'CODE' => '1'));
		}

		$this->DB->query ("UPDATE acf_members SET signature='" . addslashes($newsig) . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");

		return TRUE;
	}

	/**
	 * Update current member's photograph.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $photourl The URL where the photo is saved
	 * @param integer $height Height in pixels
	 * @param integer $width Width in pixels
	 * @return bool success
	 * @see get_photo(), update_member(), update_sig()
	 */
	function update_photo($photourl = '', $height = '', $width = '') {
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
		// Remove Photo
		if (!$photourl) {
			$this->DB->query ("UPDATE acf_member_extra SET photo_type='', photo_location='', photo_dimensions='' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
			return TRUE;
		}
		// Change Photo
		$info = $this->get_advinfo();
		$max = explode (':', $info['g_photo_max_vars']);

		$height = ($height > $max['1']) ? $max['1'] : intval($height);
		$width = ($width > $max['2']) ? $max['2'] : intval($width);

		if ($height AND $width) {
			$dimensions = $height . ',' . $width;
		} else {
			$dimensions = '';
		}

		$this->DB->query("UPDATE acf_member_extra SET photo_type='url', photo_location='" . $photourl . "', photo_dimensions='' . $dimensions . '' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");

		return TRUE;
	}

	/**
	 * Changes a user's password.
	 *
	 * @author  Saint <saint@saintdevelopment.com>
	 * @return  bool success.
	 */
	function update_password($new_pass, $memberid = "") {
		$new_pass = trim($new_pass);
		$new_md5pass = md5($new_pass);

		// Do we have a member to update or not?
		if ($memberid) {
			$memberid = intval($memberid);
 		}
		else {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}

		// Check we are logged in
		$info = $this->get_advinfo($memberid);
		if (!$GLOBALS['ibforums']->loggedin) {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}

		if (empty($new_pass) OR strlen($new_pass) < 3 OR strlen($new_pass) > 32) {
 			$this->sdkerror($this->lang['sdk_acc_pass']);
			return FALSE;
		}

		$this->DB->query("UPDATE acf_members SET password='".$new_md5pass."' WHERE id='".$memberid."'");

		return TRUE;
	}

	/**
	 * Update properties of a member's record.
	 *
	 * The following fields can be used in the $updatewhat array:
	 * <code>'avatar', 'avatar_size', 'aim_name', 'icq_number', 'location', 'signature', 'website', 'yahoo', 'title', 'interests', 'hide_email', 'email_pm', 'skin', 'language', 'msnname', 'view_sigs', 'view_img', 'view_avs', 'view_pop', 'bday_day', 'bday_month', 'bday_year', 'dst_in_use', 'integ_msg'</code>
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param array $updatewhat Associative array with fieldnames and values to update
	 * @param integer $memberid The Member ID to update
	 * @param integer $bypassperms Default: 0=use board permissions to allow update, 1=bypass permissions
	 * @return bool success.
	 * @see update_sig(), update_photo()
	 */
	function update_member($updatewhat = array(), $memberid = '', $bypassperms = '0') {
		// Do we have a member to update or not?
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}
		$memberid = intval($memberid);
		// Check we are logged in and can update profiles
		$info = $this->get_advinfo($memberid);
		if ((!$GLOBALS['ibforums']->loggedin OR !$info['g_edit_profile']) AND !$bypassperms) {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}
		// Array of allowed array keys in $updatewhat we can update
		$allowed = array('avatar', 'avatar_size', 'aim_name', 'icq_number', 'location', 'signature', 'website', 'yahoo', 'title', 'interests', 'hide_email', 'email_pm', 'skin', 'language', 'msnname', 'view_sigs', 'view_img', 'view_avs', 'view_pop', 'bday_day', 'bday_month', 'bday_year', 'dst_in_use', 'integ_msg');

		$update = array(); // Init
		$sql = '';
		// If we have something to update
		if (count($updatewhat) > 0) {
			foreach ($updatewhat as $i => $j) {
				if (in_array($i, $allowed)) {
					// We can do this!!!!
					$update[$i] = $this->makesafe($j);

					if ($sql) {
						$sql .= ',' . $i . "='" . $update[$i] . "'";
					} else {
						$sql .= $i . "='" . $update[$i] . "'";
					}
				}
			}
			// Check we have something to do again
			if ($sql) {
				// Update in Database
				$this->DB->query ("UPDATE acf_members SET " . $sql . " WHERE id='" . $info['id'] . "'");
				// Update in get_advinfo() cache.
#				foreach ($update as $x => $y) {
#					$info[$x] = $y;
#				}
				$info = array_merge($info, $update);

				$this->save_cache('get_advinfo', $info['id'], $info);
			}
		}
		// Return TRUE if not as although there was nowt to do
		// There was no error.
		return TRUE;
	}

	/**
	 * Lists the board's members.
	 *
	 * The following options can be used to overwrite the default query results.
	 * <br>'order' default: 'asc'
	 * <br>'start' default: '0' start with first record
	 * <br>'limit' default: '30' no. of members per page
	 * <br>'orderby' default: 'name' other keys see below
	 * <br>'group' default: '*' all groups. You can specifiy a number or list of numbers
	 *
	 * Sort keys: any field from acf_members or acf_groups.
	 * To avoid trouble ordering by a field 'xxx', use <b>m.XXX</b> or <b>g.XXX</b> as
	 * the full qualified fieldname, not just 'xxx'.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param array $options Overwrites default behaviour of SQL query.
	 * @return array Members
	 * @see list_online_members(), get_active_count()
	 */
	function list_members ($options = array('order' => 'asc', 'start' => '0', 'limit' => '30', 'orderby' => 'name', 'group' => '*')) {
		// Ordering
		$orders = array('id', 'name', 'posts', 'joined');
		if (!in_array($options['orderby'], $orders)) {
			$options['orderby'] = 'name';
		}
		// Order By
		$options['order'] = ($options['order'] == 'desc') ? 'DESC' : 'ASC';
		// Start and Limit
		$filter = 'LIMIT ' . intval($options['start']) . ',' . intval($options['limit']);
		// Grouping
		$where = '';
		if (is_array($options['group']) AND $options['group'] != '*') {
			foreach ($options['group'] as $i) {
				$i = (int)$i;
				if ($i > 0) {
					if ($where) {
						$where .= "OR mgroup='" . $i . "' ";
					} else {
						$where .= "mgroup='" . $i . "' ";
					}
				}
			}
		}

		if ($where) {
			$where = "WHERE m.id != '0' AND (" . $where . ')';
		} else {
			$where = "WHERE m.id != '0'";
		}

		$this->DB->query ('SELECT m.*, g.*, cf.* FROM acf_members m LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) LEFT JOIN acf_pfields_content cf ON (cf.member_id=m.id) ' . $where . ' ORDER BY ' . $options['orderby'] . ' ' . $options['order'] . ' ' . $filter);

		$return = array();
		while ($row = $this->DB->fetch_row()) {
			$return[$row['id']] = $row;
		}

		return $return;
	}

	/**
	 * Get an array of online members.
	 *
	 * By default, the userlist is ordered by an internal timestamp of IPB based on what users to in the forums.
	 * You may change the order using the $options array:<br />
	 * <b>'order_by'</b> - one of <var>member_name</var>, <var>member_id</var>, <var>running_time</var>, <var>location</var><br />
	 * <b>'order'</b> - ASC or DESC
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param bool $detailed Whether to return more data or not :)
	 * @param array $options Array with order setting
	 * @return array Online Members
	 * @see list_members(), get_active_count()
	 * @since 1.1.1 $options array to change output order
	 */
	function list_online_members($detailed = FALSE, $options=array('order_by'=>'running_time','order'=>'DESC')) {
		$cutoff = $GLOBALS['ibforums']->vars['au_cutoff'] ? $GLOBALS['ibforums']->vars['au_cutoff'] : "15";
		$timecutoff = time() - ($cutoff * 60);
# MOD: 1.1.1 optional order attribute (but req. by SSIplus+)
		foreach (array_keys($options) as $k) {
			$options[$k] = $this->makesafe($options[$k]);
		}
		$fields = array('member_name'=>'ASC', 'member_id'=>'ASC', 'running_time'=>'DESC', 'location'=>'ASC');

		if ( in_array($options['order_by'], array_keys($fields) ) ) {
			$order_by = sprintf('s.%s %s', $options['order_by'], $fields[$options['order_by']]);
			// second sort order
			if ($options['order_by'] == 'location') {
				$order_by .= ', s.running_time DESC';
			}
		} else {
			$order_by = 's.running_time DESC';
		}

		if ($detailed) {
# MOD: 1.1.1 use order key for cache
			if ($cache = $this->get_cache('list_online_members', 'detail'.$order_by)) {
				return $cache;
			} else {
				// We need to grab it all...
# MOD: 1.1.1 added ORDER BY clause
				$this->DB->query ("SELECT s.*, m.*, g.*, cf.* FROM acf_sessions s LEFT JOIN acf_members m ON (s.member_id=m.id) LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) LEFT JOIN acf_pfields_content cf ON (cf.member_id=m.id) WHERE s.member_id != '0' AND s.running_time > '" . $timecutoff . "' ORDER BY ".$order_by);
				$online = array();

				while ($row = $this->DB->fetch_row()) {
					$online[$row['member_id']] = $row;
				}

# MOD: 1.1.1 use order key for cache
				$this->save_cache('list_online_members', 'detail'.$order_by, $online);
				return $online;
			}
		} else {
			if ($cache = $this->get_cache('list_online_members', 'nodetail')) {
				return $cache;
			} else {
				// Only Member IDs. This is for quick searching of whether
				// members are online. Like by using in_array.
				$this->DB->query ("SELECT member_id FROM acf_sessions s WHERE s.member_id != '0' AND s.running_time > '" . $timecutoff . "'");
				$online = array();

				while ($row = $this->DB->fetch_row()) {
					$online[$row['member_id']] = $row['member_id'];
				}

				$this->save_cache('list_online_members', 'nodetail', $online);
				return $online;
			}
		}
	}

	/**
	 * Returns the active user count.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Active User Count
	 * @see list_members(), list_online_members()
	 */
	 function get_active_count() {
		if ($cache = $this->get_cache('get_active_count', '1')) {
			return $cache;
		} else {
			// Init
			$count = array('total' => '0', 'anon' => '0', 'guests' => '0', 'members' => '0');

			$cutoff = $GLOBALS['ibforums']->vars['au_cutoff'] ? $GLOBALS['ibforums']->vars['au_cutoff'] : "15";
			$timecutoff = time() - ($cutoff * 60);

			$this->DB->query ("SELECT member_id, login_type FROM acf_sessions WHERE running_time > '".$timecutoff."'");

			while ($row = $this->DB->fetch_row()) {
				// Add up members
				if ($row['login_type'] == '1') {
					++$count['anon'];
				} else {
					if ($row['member_id'] == '0') {
						++$count['guests'];
					} else {
						++$count['members'];
					}
				}
			}

			$count['total'] = $count['anon'] + $count['guests'] + $count['members'];
# why is "get_online_members" cached?
			$this->save_cache('get_online_members', 'detail', $count);
			return $count;
		}
	}

	/**
	 * Returns members born on the given day of a month.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $day Optional. Current day is used if left as an empty string or zero.
	 * @param integer $month Optional. Current month is used if left as an empty string or zero.
	 * @return array Birthday Members
	 * @see list_members(), list_online_members()
	 */
	function get_birthday_members($day = 0, $month = 0) {
		if ((int)$day<=0) {
			$day = date('j');
		}
		if ((int)$month<=0) {
			$month = date ('n');
		}

		$this->DB->query("SELECT m.*, g.*, cf.* FROM acf_members m LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) LEFT JOIN acf_pfields_content cf ON (cf.member_id=m.id) WHERE m.bday_day='" . intval($day) . "' AND m.bday_month='" . intval($month) . "'");

		$return = array();
		$thisyear = date ('Y');
		while ($row = $this->DB->fetch_row()) {
			$row['age'] = $thisyear - $row['bday_year'];
			$return[] = $row;
		}

		return $return;
	}

	// -----------------------------------------------
	// PRIVATE MESSAGE FUNCTIONS
	// Functions to read, send, and interact with the
	// PM and contacts system.
	// -----------------------------------------------
    /**#@+
	 * @group PrivateMessage
	 */
	/**
	 * Gets total number of PMs.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return int Total Messages Count
	 */
	function get_num_total_pms() {
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$this->DB->query ("SELECT msg_total FROM acf_members WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
		if ($messages = $this->DB->fetch_row()) {
			return $messages['msg_total'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Gets number of new PMs.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return int New Unread Messages Count
	 */
	function get_num_new_pms() {
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$this->DB->query ("SELECT new_msg FROM acf_members WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
		if ($messages = $this->DB->fetch_row()) {
			return $messages['new_msg'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Lists PMs in a folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $folder Keyname of Inbox folder, 'in', 'sent'
	 * @return array Information of PMs in folder.
	 * @see get_pm_folders()
	 */
	function list_pms($folder = 'in') {
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$pms = array();

		$this->DB->query ("SELECT m.*, s.name, r.name AS recipient_name FROM acf_messages m LEFT JOIN acf_members s ON (m.from_id=s.id) LEFT JOIN acf_members r ON (m.recipient_id=r.id) WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND vid='" . $folder . "' ORDER BY msg_date DESC");
		if ($this->DB->get_num_rows()) {
			while ($row = $this->DB->fetch_row()) {
				$pms[] = $row;
			}

			return $pms;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns information on a Personal Message.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id PM record ID
	 * @param integer $markread Default: 0=keep unread, 1=mark read
	 * @param integer $convert Default: 1 convert BBCode
	 * @return array Information of a PM
	 */
	function get_pm_info($id, $markread = '0', $convert = '1') {
		if (!$id) {
			return FALSE;
		}
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$pminfo = array();

		$this->DB->query ("SELECT m.*, s.name, r.name AS recipient_name FROM acf_messages m LEFT JOIN acf_members s ON (m.from_id=s.id) LEFT JOIN acf_members r ON (m.recipient_id=r.id) WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND msg_id='" . intval($id) . "'");
		if ($this->DB->get_num_rows()) {
			if ($row = $this->DB->fetch_row()) {
				if ($markread AND !$row['read_state']) {
					$this->DB->query ("UPDATE acf_messages SET read_state='1', read_date='" . time() . "' WHERE msg_id='" . $id . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					if ($row['vid'] == 'in') {
						$this->DB->query ("UPDATE acf_members SET new_msg=new_msg-1 WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' AND new_msg > 0");
					}
				}

				if ($convert) {
					$row['message'] = $GLOBALS['parser']->convert(array('TEXT' => $row['message'], 'CODE' => 1, 'SMILIES' => 1));
				}

				return $row;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Sends a PM.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $to_id MEmber ID tor eceive the message
	 * @param string $title Message title
	 * @param string $message Message body
	 * @param array $cc Array of ID for carbon copies (CC)
	 * @param integer $sentfolder Default: 0=do not save message in Sent folder, 1=save message
	 * @return bool Success.
	 * @see save_pm();
	 */
	function write_pm($to_id, $title, $message, $cc = array(), $sentfolder = '0') {
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
		if (!$to_id) {
			$this->sdkerror($this->lang['sdk_pm_no_recipient']);
			return FALSE;
		}
		if (!$title OR strlen($title) < 2) {
			$this->sdkerror($this->lang['sdk_pm_title']);
			return FALSE;
		}
		if (!$message OR strlen($message) < 2) {
			$this->sdkerror($this->lang['sdk_pm_message']);
			return FALSE;
		}

		$sendto = array();

		$this->DB->query("SELECT m.name, m.id, m.view_pop, m.mgroup, m.email_pm, m.language, m.email, m.msg_total, g.g_use_pm, g.g_max_messages FROM acf_groups g, acf_members m WHERE m.id='" . intval($to_id) . "' AND g.g_id=m.mgroup");
		if ($row = $this->DB->fetch_row()) {
			// Just incase
			if (!$row['id']) {
				$this->sdkerror($this->lang['sdk_pm_mem_notexist']);
				return FALSE;
			}
			// Permissions Check
			if ($row['g_use_pm'] != '1') {
				$this->sdkerror($this->lang['sdk_pm_mem_disallowed']);
				return FALSE;
			}
			// Space Check
			if ($row['msg_total'] >= $row['g_max_messages'] AND $row['g_max_messages'] > 0) {
				$this->sdkerror($this->lang['sdk_pm_mem_full']);
				return FALSE;
			}
			// Block Check
			if ($this->is_pmblocked($GLOBALS['ibforums']->member['id'], intval($to_id))) {
				$this->sdkerror($this->lang['sdk_pm_mem_blocked']);
				return FALSE;
			}
			// CC Users
			$ccusers = array();

			if ($GLOBALS['ibforums']->member['g_max_mass_pm']) {
				if (is_array($cc) AND count($cc) > 0) {
					if (count($cc) > $GLOBALS['ibforums']->member['g_max_mass_pm']) {
						$this->sdkerror($this->lang['sdk_pm_cclimit']);
						return FALSE;
					}

					foreach ($cc AS $i) {
						// Check CC user stuff
						// I really should clean up the code here, it uses alot of queries in some cases, which isn't good. Should really merge this with the main sending message code instead of replicating stuff for CCs.
						$this->DB->query("SELECT m.name, m.id, m.view_pop, m.mgroup, m.email_pm, m.language, m.email, m.msg_total, g.g_use_pm, g.g_max_messages FROM acf_groups g, acf_members m WHERE m.id='" . intval($to_id) . "' AND g.g_id=m.mgroup");
						if ($ccrow = $this->DB->fetch_row()) {
							// Permissions Check
							if ($ccrow['g_use_pm'] != '1') {
								$this->sdkerror($this->lang['sdk_pm_rec_disallowed']);
								return FALSE;
							}
							// Space Check
							if ($ccrow['msg_total'] >= $ccrow['g_max_messages'] AND $ccrow['g_max_messages'] > 0) {
								$this->sdkerror($this->lang['sdk_pm_rec_full']);
								return FALSE;
							}
							// Block Check
							if ($this->is_pmblocked($GLOBALS['ibforums']->member['id'], intval($to_id))) {
								$this->sdkerror($this->lang['sdk_pm_rec_blocked']);
								return FALSE;
							}
						}

						$ccusers[] = intval($i);
					}
				}
			}
			// Actually send it
			$ccusers[] = intval($to_id);

			foreach ($ccusers as $recipient) {
				$DBstring = $GLOBALS['std']->compile_db_string(array('member_id' => $recipient,
						'msg_date' => time(),
						'read_state' => '0',
						'title' => $title,
						'message' => $GLOBALS['std']->remove_tags($message),
						'from_id' => $GLOBALS['ibforums']->member['id'],
						'vid' => 'in',
						'recipient_id' => $recipient,
						'tracking' => '0',
						));
				// Insert
				$this->DB->query ('INSERT INTO acf_messages (' . $DBstring['FIELD_NAMES'] . ') VALUES (' . $DBstring['FIELD_VALUES'] . ')');
				unset($this->DBstring);

				$this->DB->query("UPDATE acf_members SET msg_total = msg_total + 1, new_msg = new_msg + 1, msg_from_id='" . $GLOBALS['ibforums']->member['id'] . "', show_popup='1', msg_msg_id='" . $this->DB->get_insert_id() . "' WHERE id='" . $recipient . "'");
			}

			if ($sentfolder) {
				$DBstring = $GLOBALS['std']->compile_db_string(array('member_id' => $GLOBALS['ibforums']->member['id'],
						'msg_date' => time(),
						'read_state' => '0',
						'title' => 'Sent: ' . $title,
						'message' => $GLOBALS['std']->remove_tags($message),
						'from_id' => $GLOBALS['ibforums']->member['id'],
						'vid' => 'sent',
						'recipient_id' => $recipient,
						'tracking' => '0',
						));

				$this->DB->query ('INSERT INTO acf_messages (' . $DBstring['FIELD_NAMES'] . ') VALUES (' . $DBstring['FIELD_VALUES'] . ')');
			}

			return TRUE;
		} else {
			$this->sdkerror($this->lang['sdk_pm_mem_notexist']);
			return FALSE;
		}
	}

	/**
	 * Saves a PM to the sent folder without sending it.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $to_id Member ID to receive the message
	 * @param string $title Message title
	 * @param string $message Message body
	 * @param array $cc Array of ID for carbon copies (CC)
	 * @return bool Success.
	 * @see write_pm();
	 */
	function save_pm($to_id, $title, $message, $cc = array()) {
		// Similar to Write PM but code modified for saving
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
		if (!$to_id) {
			$this->sdkerror($this->lang['sdk_pm_no_recipient']);
			return FALSE;
		}
		if (!$title OR strlen($title) < 2) {
			$this->sdkerror($this->lang['sdk_pm_title']);
			return FALSE;
		}
		if (!$message OR strlen($message) < 2) {
			$this->sdkerror($this->lang['sdk_pm_message']);
			return FALSE;
		}

		$sendto = array();

		$this->DB->query("SELECT m.name, m.id, m.view_pop, m.mgroup, m.email_pm, m.language, m.email, m.msg_total, g.g_use_pm, g.g_max_messages FROM acf_groups g, acf_members m WHERE m.id='" . intval($to_id) . "' AND g.g_id=m.mgroup");
		if ($row = $this->DB->fetch_row()) {
			// Just incase
			if (!$row['id']) {
				$this->sdkerror($this->lang['sdk_pm_mem_notexist']);
				return FALSE;
			}
			// Permissions Check
			if ($row['g_use_pm'] != '1') {
				$this->sdkerror($this->lang['sdk_pm_mem_disallowed']);
				return FALSE;
			}
			// Space Check
			if ($row['msg_total'] >= $row['g_max_messages'] AND $row['g_max_messages'] > 0) {
				$this->sdkerror($this->lang['sdk_pm_rec_full']);
				return FALSE;
			}
			// Block Check
			if ($this->is_pmblocked($GLOBALS['ibforums']->member['id'], intval($to_id))) {
				$this->sdkerror($this->lang['sdk_pm_rec_blocked']);
				return FALSE;
			}
			// CC Users
			$ccusers = array();

			if ($GLOBALS['ibforums']->member['g_max_mass_pm']) {
				if (is_array($cc) AND count($cc) > 0) {
					if (count($cc) > $GLOBALS['ibforums']->member['g_max_mass_pm']) {
						$this->sdkerror($this->lang['sdk_pm_cclimit']);
						return FALSE;
					}

					foreach ($cc AS $i) {
						// Check CC user stuff
						// I really should clean up the code here, it uses alot of queries in some cases, which isn't good. Should really merge this with the main sending message code instead of replicating stuff for CCs.
						$this->DB->query("SELECT m.name, m.id, m.view_pop, m.mgroup, m.email_pm, m.language, m.email, m.msg_total, g.g_use_pm, g.g_max_messages FROM acf_groups g, acf_members m WHERE m.id='" . intval($to_id) . "' AND g.g_id=m.mgroup");
						if ($ccrow = $this->DB->fetch_row()) {
							// Permissions Check
							if ($ccrow['g_use_pm'] != '1') {
								$this->sdkerror($this->lang['sdk_pm_rec_disallowed']);
								return FALSE;
							}
							// Space Check
							if ($ccrow['msg_total'] >= $ccrow['g_max_messages'] AND $ccrow['g_max_messages'] > 0) {
								$this->sdkerror($this->lang['sdk_pm_rec_full']);
								return FALSE;
							}
							// Block Check
							if ($this->is_pmblocked($GLOBALS['ibforums']->member['id'], intval($to_id))) {
								$this->sdkerror($this->lang['sdk_pm_rec_blocked']);
								return FALSE;
							}
						}

						$ccusers[] = intval($i);
					}
				}
			}
			// IPB is a total pain in the butt, hence we need to now change the IDs to names, and stick some <br> in it.
			if (is_array($ccusers) AND count($ccusers) > 1) {
				$ccsql = implode('<br>', id2name($ccusers));
			} elseif (is_array($ccusers) AND count($ccusers) == '1') {
				$ccsql = id2name($ccusers['0']);
			} else {
				$ccsql = '';
			}

			$DBstring = $GLOBALS['std']->compile_db_string(array('member_id' => $GLOBALS['ibforums']->member['id'],
					'msg_date' => time(),
					'read_state' => '0',
					'title' => $title,
					'message' => $GLOBALS['std']->remove_tags($message),
					'from_id' => $GLOBALS['ibforums']->member['id'],
					'vid' => 'unsent',
					'recipient_id' => $to_id,
					'cc_users' => $ccsql,
					'tracking' => '0',
					));
			// Insert
			$this->DB->query ('INSERT INTO acf_messages (' . $DBstring['FIELD_NAMES'] . ') VALUES (' . $DBstring['FIELD_VALUES'] . ')');

			return TRUE;
		} else {
			$this->sdkerror($this->lang['sdk_pm_mem_notexist']);
			return FALSE;
		}
	}

	/**
	 * Deletes a Personal Message.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $messageid Message to be deleted
	 * @return bool Success.
	 */
	function delete_pm($messageid) {
		if (!$messageid) {
			return FALSE;
		}

		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$this->DB->query ("SELECT * FROM acf_messages WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND msg_id='" . $messageid . "'");
		if ($row = $this->DB->fetch_row()) {
			$this->DB->query ("DELETE FROM acf_messages WHERE msg_id='" . $messageid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");

			if ($row['vid'] != 'unsent') {
				$this->DB->query ("UPDATE acf_members SET msg_total = msg_total - 1 WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
			}

			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns whether a member has blocked another member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $by Member ID of receiver (the one who blocked)
	 * @param integer $blocked Member ID of sender (the one who is blocked)
	 * @return bool Block Status
	 */
	function is_pmblocked($by, $blocked) {
		$this->DB->query ("SELECT id, allow_msg FROM acf_contacts WHERE contact_id='" . $by . "' AND member_id='" . $blocked . "'");
		if ($cando = $this->DB->fetch_row()) {
			return (bool)$cando['allow_msg'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns number of PMs in a folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folder Folder ID
	 * @return int Number of PMs in Folder
	 */
	function get_num_folder_pms($folder) {
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
		$this->DB->query ("SELECT COUNT(msg_id) AS messages FROM acf_messages WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND vid='" . $folder . "'");
		if ($messages = $this->DB->fetch_row()) {
			return $messages['messages'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns number of unread PMs in a folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folder Folder ID
	 * @return int Number of unread PMs in Folder
	 */
	function get_num_folder_unread_pms($folder) {
		if ($cache = $this->get_cache('get_num_folder_unread_pms', $folder)) {
			return $cache;
		}
		if (!$this->is_loggedin() AND !$GLOBALS['ibforums']->member['g_use_pm']) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		$this->DB->query ("SELECT COUNT(msg_id) AS messages FROM acf_messages WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND vid='" . $folder . "' AND read_state='0'");
		if ($messages = $this->DB->fetch_row()) {
			// Save In Cache and Return
			$this->save_cache('get_num_folder_unread_pms', $folder, $messages['messages']);

			return $messages['messages'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns PM space usage in percentage.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return int PM Space Usage in Percent
	 */
	function get_pm_space_usage() {
		$pms = $this->get_num_total_pms();
		$info = $this->get_advinfo();
		$maximumpms = $info['g_max_messages'];

		$percent = round(($pms / $maximumpms) * 100);
		return $percent;
	}

	/**
	 * Returns the current user's PM folders.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Current user's PM System Folders
	 */
	function get_pm_folders() {
		if ($this->is_loggedin() AND $GLOBALS['ibforums']->member['g_use_pm']) {
			$folders = array();

			$this->DB->query ("SELECT vdirs FROM acf_members WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");

			if ($row = $this->DB->fetch_row()) {
				$row['vdirs'] = $row['vdirs'] ? $row['vdirs'] : 'in:Inbox|sent:Sent Items';
				$i = explode ('|', $row['vdirs']);
				foreach ($i as $j) {
					$k = explode (':', $j);
					$folders[] = $k;
				}

				return $folders;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns whether a PM folder exists for a given member.
	 * If $memberid is ommited, the last known member is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folder Folder ID
	 * @param integer $memberid
	 * @return bool Folder Existance Status
	 */
	function pm_folder_exists ($folder, $memberid = '') {
		// Inbox and Sent Items are Good
		if ($folder == 'in' OR $folder == 'sent') {
			return TRUE;
		}
		// 'unsent' should be an bad folder name anyway, but put this so as not to screw up other functions
		if ($folder == 'unsent') {
			return FALSE;
		}

		$folderids = array();

		if ($memberid) {
			$memberinfo = $this->get_advinfo($memberid);
		} else {
			$memberinfo = $this->get_advinfo();
		}

		$folders = $memberinfo['vdirs'];

		$folderslist = explode ('|', $folders);

		foreach ($folderslist as $i) {
			$j = explode (':', $i);
			$folderids[] = $j['0'];
		}

		if (in_array($folder, $folderids)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns folder name associated with folder id of a member.
	 * If $memberid is ommited, the last known member is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folder Folder ID
	 * @param integer $memberid
	 * @return string Folder Name associated with id
	 */
	function pm_folderid2name($id, $memberid = '') {
		if ($memberid) {
			$memberinfo = $this->get_advinfo($memberid);
		} else {
			$memberinfo = $this->get_advinfo();
		}

		$folders = $memberinfo['vdirs'];
		$list = explode ('|', $folders);

		foreach ($list as $i) {
			$j = explode (':', $i);
			$foldersinfo[$j['0']] = $j['1'];
		}

		if ($foldersinfo[$id]) {
			return $foldersinfo[$id];
		} else {
			return FALSE;
		}
	}

	/**
	 * Creates a personal message folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $name Foldername
	 * @return bool Success
	 */
	function add_pm_folder($name) {
		if ($this->is_loggedin()) {
			// Get Folders
			$folders = $this->get_pm_folders();
			$info = $this->get_advinfo();
			$foldersi = array();

			foreach ($folders as $i) {
				$foldersi[$i['0']] = $i['1'];
			}

			$foldersno = count($folders);
			// Just to check
			if (!$foldersi['dir_' . $foldersno]) {
				$newfolders = $info['vdirs'] . '|dir_' . $foldersno . ':' . $name;
				$this->DB->query ("UPDATE acf_members SET vdirs='" . $newfolders . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
				return 'dir_' . $foldersno;
			} else {
				// Just incase
				while ($foldersno < 100) {
					if (!$foldersi['dir_' . $foldersno]) {
						$newfolders = $info['vdirs'] . '|dir_' . $foldersno . ':' . $name;
						$this->DB->query ("UPDATE acf_members SET vdirs='" . $newfolders . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
						return 'dir_' . $foldersno;
					}

					++$foldersno;
				}

				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Renames a personal message folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folderid Folder ID
	 * @param string $newname well ...
	 * @return bool Success
	 */
	function rename_pm_folder($folderid, $newname) {
		if (!$newname) {
			return FALSE;
		}

		if ($this->is_loggedin()) {
			// Get Folders
			$folders = $this->get_pm_folders();
			$info = $this->get_advinfo();
			$foldersi = array();

			foreach ($folders as $i) {
				$foldersi[$i['0']] = $i['1'];
			}
			// Check it exists
			if ($foldersi[$folderid]) {
				$foldersi[$folderid] = $newname;
				$newf = array();

				foreach ($foldersi as $j => $k) {
					$newf[] = $j . ':' . $k;
				}

				$newfolders = implode ('|', $newf);
				// Rename the Folder
				$this->DB->query ("UPDATE acf_members SET vdirs='" . $newfolders . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");

				return TRUE;
			} else {
				$this->sdkerror($this->lang['sdk_pm_folder_noexist']);
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Empties PMs in a personal message folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folderid
	 * @param integer $keepunread Default: 0=also delete unread msgs, 1=keep unread messages
	 * @return bool Success
	 */
	function empty_pm_folder($folderid, $keepunread = '0') {
		if ($this->is_loggedin()) {
			if ($this->pm_folder_exists($folderid)) {
				if ($keepunread) {
					// Just so we can decrement total
					$this->DB->query ("SELECT COUNT(msg_id) AS messagescount FROM acf_messages WHERE vid='" . $folderid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND read_state='1'");
					$row = $this->DB->fetch_row();
					$del = $row['messagescount'];
					// Delete
					$this->DB->query ("DELETE FROM acf_messages WHERE vid='" . $folderid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND read_state='1'");
					// Update Total
					$this->DB->query("UPDATE acf_members SET msg_total=msg_total-" . intval($del) . " WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");

					return $del;
				} else {
					// Just so we can decrement total
					$this->DB->query ("SELECT COUNT(msg_id) AS messagescount FROM acf_messages WHERE vid='" . $folderid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
					$row = $this->DB->fetch_row();
					$del = $row['messagescount'];
					// Delete
					$this->DB->query ("DELETE FROM acf_messages WHERE vid='" . $folderid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
					// Update Total
					$this->DB->query("UPDATE acf_members SET msg_total=msg_total-" . intval($del) . " WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");

					return $del;
				}
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Removes a personal message folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $folderid
	 * @return bool Success
	 */
	function remove_pm_folder($folderid) {
		// DANGER! DANGER!
		if ($this->is_loggedin()) {
			$folders = $this->get_pm_folders();
			$foldersi = array();

			if ($this->pm_folder_exists($folderid)) {
				// Check if it's Inbox or Sent Items
				if ($folderid != 'in' AND $folderid != 'sent') {
					// Good. Now, try and delete the messages firstly.
					$this->empty_pm_folder ($folderid, 0);
					// Now Delete the Folder
					foreach ($folders as $i) {
						if ($i['0'] != $folderid) {
							$cur = implode ($i, ':');
							$foldersi[$i['0']] = $cur;
						}
					}

					$newvids = implode ('|', $foldersi);

					$this->DB->query ("UPDATE acf_members SET vdirs='" . $newvids . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");

					return TRUE;
				} else {
					$this->sdkerror($this->lang['sdk_pm_folder_norem']);
					return FALSE;
				}
			} else {
				$this->sdkerror($this->lang['sdk_pm_folder_noexist']);
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
	}

	/**
	 * Marks a message read/unread.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $msg_id
	 * @param integer $isread Default: 1=mark read, 0=mark unread
	 * @return bool Success
	 */
	function pm_mark_message ($msg_id, $isread = '1') {
		if ($this->is_loggedin()) {
			if ($isread) {
				$this->DB->query ("UPDATE acf_messages SET read_state='1', read_date='" . time() . "' WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND msg_id='" . intval($msg_id) . "'");
				// Return success
				if ($this->DB->get_affected_rows()) {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				$this->DB->query ("UPDATE acf_messages SET read_state='0', read_date=NULL WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND msg_id='" . intval($msg_id) . "'");
				// Return success
				if ($this->DB->get_affected_rows()) {
					return TRUE;
				} else {
					return FALSE;
				}
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Moves a personal message to another folder.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $messageid Message ID to be moved
	 * @param integer $targetid Target folder ID.
	 * @return bool Success
	 */
	function pm_move_message($messageid, $targetid) {
		if ($this->is_loggedin()) {
			// Grab PM Info
			if ($info = $this->get_pm_info($messageid, 0)) {
				// Check the Dest Folder Exists
				if ($this->pm_folder_exists($targetid)) {
					$this->DB->query ("UPDATE acf_messages SET vid='" . $targetid . "' WHERE msg_id='" . $messageid . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					if ($this->DB->get_affected_rows()) {
						// If you move an unread message from inbox
						if ($info['vid'] == 'in' AND $info['read_state'] == '0') {
							$this->DB->query ("UPDATE acf_members SET new_msg = new_msg - 1 WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
						}
						// And if you move a unread message to the inbox
						else if ($targetid == 'in' AND $info['read_state'] == '0') {
							$this->DB->query ("UPDATE acf_members SET new_msg = new_msg + 1 WHERE id='" . $GLOBALS['ibforums']->member['id'] . "'");
						}

						return TRUE;
					} else {
						$this->sdkerror($this->lang['sdk_pm_msg_no_move']);
						return FALSE;
					}
				} else {
					$this->sdkerror($this->lang['sdk_pm_folder_tnoexist']);
					return FALSE;
				}
			} else {
				$this->sdkerror($this->lang['sdk_pm_msg_no_move']);
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}
	}

	/**
	 * Returns information on the current user's contacts.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Contacts Information
	 */
	function get_contacts_list() {
		if ($this->is_loggedin()) {
			$this->DB->query ("SELECT contact_id, contact_desc, contact_name FROM acf_contacts WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND allow_msg='1'");
			$contacts = array();
			while ($row = $this->DB->fetch_row()) {
				$contacts[$row['contact_id']] = $row;
			}

			return $contacts;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns blocked members information.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Blocked Members Information
	 */
	function get_blocked_list() {
		if ($this->is_loggedin()) {
			$this->DB->query ("SELECT contact_id, contact_desc, contact_name FROM acf_contacts WHERE member_id='" . $GLOBALS['ibforums']->member['id'] . "' AND allow_msg='0'");
			$blocked = array();
			while ($row = $this->DB->fetch_row()) {
				$blocked[$row['contact_id']] = $row;
			}

			return $blocked;
		} else {
			return FALSE;
		}
	}

	/**
	 * Adds a contact.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $userid Member ID to be added
	 * @param string $description Description for the 'Buddy'
	 * @return bool Success
	 */
	function add_contact($userid, $description) {
		if ($this->is_loggedin()) {
			// Check user exists
			if (!$userid OR !$this->get_info(intval($userid))) {
				return FALSE;
			}
			// o_O. Firstly check if there is already an entry.
			$this->DB->query ("SELECT * FROM acf_contacts WHERE contact_id='" . intval($userid) . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
			if ($row = $this->DB->fetch_row()) {
				if ($row['allow_msg'] == '1' AND $row['contact_desc'] == $description) {
					// Clearly no point of doing anything.
					return TRUE;
				} else {
					// Update record
					$this->DB->query ("UPDATE acf_contacts SET allow_msg='1', contact_desc='" . $description . "' WHERE contact_id='" . intval($userid) . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
					return TRUE;
				}
			} else {
				// We can just add an entry because theres nothing there.
				$this->DB->query ("INSERT INTO acf_contacts VALUES ('', '" . intval($userid) . "', '" . $GLOBALS['ibforums']->member['id'] . "', '" . $this->id2name(intval($userid)) . "', '1', '" . $description . "')");
				return TRUE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Blocks a contact.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $userid Member ID to be added
	 * @param string $description Description for the 'Buddy'
	 * @return bool Success
	 */
	function block_contact($userid, $description) {
		if ($this->is_loggedin()) {
			// Check user exists
			if (!$userid OR !$this->get_info(intval($userid))) {
				return FALSE;
			}
			// o_O. Firstly check if there is already an entry.
			$this->DB->query ("SELECT * FROM acf_contacts WHERE contact_id='" . intval($userid) . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
			if ($row = $this->DB->fetch_row()) {
				if ($row['allow_msg'] == '0' AND $row['contact_desc'] == $description) {
					// Clearly no point of doing anything.
					return TRUE;
				} else {
					// Update record
					$this->DB->query ("UPDATE acf_contacts SET allow_msg='0', contact_desc='" . $description . "' WHERE contact_id='" . intval($userid) . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
					return TRUE;
				}
			} else {
				// We can just add an entry because theres nothing there.
				$this->DB->query ("INSERT INTO acf_contacts VALUES ('', '" . intval($userid) . "', '" . $GLOBALS['ibforums']->member['id'] . "', '" . $this->id2name(intval($userid)) . "', '1', '" . $description . "')");
				return TRUE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Removes a contact.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $userid Member ID to be kicked
	 * @return bool Success
	 */
	function remove_contact($userid) {
		// Nice and Easy. Just delete the required entries from DB and return status.
		if ($this->is_loggedin()) {
			$this->DB->query ("DELETE FROM acf_contacts WHERE contact_id='" . intval($userid) . "' AND member_id='" . $GLOBALS['ibforums']->member['id'] . "'");
			if ($this->DB->get_affected_rows()) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}


	// -----------------------------------------------
	// POLL FUNCTIONS
	// -----------------------------------------------
    /**#@+
	 * @group Polls
	 */
	/**
	 * Returns whether a member has voted in the poll in a topic.
	 * If $memberid is ommitted the last known member is used.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @param integer $memberid
	 * @return mixed Poll Vote Date if voted, FALSE otherwise
	 */
	function poll_voted ($topicid, $memberid = '') {
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}
		// Query
		$this->DB->query ("SELECT vote_date FROM acf_voters WHERE tid='" . $topicid . "' AND member_id='" . $memberid . "'");
		if ($row = $this->DB->fetch_row()) {
			return $row['vote_date'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns information on a poll.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @return array Poll Information
	 */
	function get_poll_info ($topicid) {
		if ($cache = $this->get_cache('get_poll_info', $topicid)) {
			return $cache;
		} else {
			// Query
			$this->DB->query ("SELECT p.pid, p.tid, p.start_date, p.choices, p.starter_id, m.name AS starter_name, p.votes, p.forum_id, p.poll_question FROM acf_polls p LEFT JOIN acf_members m ON (p.starter_id=m.id) WHERE p.tid='" . $topicid . "'");

			if ($row = $this->DB->fetch_row()) {
				$choices = unserialize($row['choices']);
				$row['choices'] = array();
				// Make choices more readable and more useful
				foreach ($choices as $i) {
					$row['choices'][$i['0']] = array('option_id' => $i['0'],
						'option_title' => $i['1'],
						'votes' => $i['2'],
						'percentage' => $row['votes'] ? intval(($i['2'] / $row['votes']) * 100) : '0',
						);
				}

				$this->save_cache('get_poll_info', $topicid, $row);

				return $row;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Returns total number of votes in a poll.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @return int Poll Votes
	 */
	function get_poll_total_votes ($topicid) {
		if ($info = $this->get_poll_info($topicid)) {
			return $info['votes'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns Topic ID associated with Poll ID.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $pollid
	 * @return int Topic ID associated with Poll ID
	 */
	function pollid2topicid ($pollid) {
		if (is_array($pollid)) {
			$topics = array();

			foreach ($pollid as $i => $j) {
				$this->DB->query ("SELECT tid FROM acf_polls WHERE pid='" . $j . "' LIMIT 1");
				if ($row = $this->DB->fetch_row()) {
					$topics[$i] = $row['tid'];
				} else {
					$topics[$i] = FALSE;
				}
			}

			return $topics;
		} else {
			$this->DB->query ("SELECT tid FROM acf_polls WHERE pid='" . $pollid . "' LIMIT 1");
			if ($row = $this->DB->fetch_row()) {
				return $row['tid'];
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Casts a vote in a poll.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @param integer $optionid
	 * @return bool Success
	 */
	function vote_poll ($topicid, $optionid) {
		// No Guests
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		if ($this->poll_voted($topicid)) {
			$this->sdkerror($this->lang['sdk_poll_alreadyvoted']);
			return FALSE;
		} else {
			// Insert Vote into Database
			$this->DB->query ("SELECT * FROM acf_polls WHERE tid='" . $topicid . "'");

			if ($row = $this->DB->fetch_row()) {
				$choices = unserialize($row['choices']);

				if (!$choices[$optionid]) {
					$this->sdkerror($this->lang['sdk_poll_invalid_vote']);
					return FALSE;
				}

				++$choices[$optionid]['2'];
				$choices = serialize($choices);

				$this->DB->query ("UPDATE acf_polls SET choices='" . $choices . "', votes=votes+1 WHERE tid='" . $topicid . "'");
				$this->DB->query ("INSERT INTO acf_voters (ip_address, vote_date, tid, member_id, forum_id) VALUES ('" . $SERVER['REMOTE_ADDR'] . "', '" . time() . "', '" . $row['tid'] . "', '" . $GLOBALS['ibforums']->member['id'] . "', '" . $row['forum_id'] . "')");

				return TRUE;
			} else {
				$this->sdkerror($this->lang['sdk_poll_noexist']);
				return FALSE;
			}
		}
	}

	/**
	 * Casts a null vote in a poll.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @return bool Success
	 */
	function nullvote_poll ($topicid) {
		// No Guests Please
		if (!$this->is_loggedin()) {
			$this->sdkerror($this->lang['sdk_membersonly']);
			return FALSE;
		}

		if ($this->poll_voted($topicid)) {
			$this->sdkerror($this->lang['sdk_poll_alreadyvoted']);
			return FALSE;
		} else {
			// Insert Vote into Database
			$this->DB->query ("SELECT * FROM acf_polls WHERE tid='" . $topicid . "'");

			if ($row = $this->DB->fetch_row()) {
				$this->DB->query ("INSERT INTO acf_voters (ip_address, vote_date, tid, member_id, forum_id) VALUES ('" . $SERVER['REMOTE_ADDR'] . "', '" . time() . "', '" . $row['tid'] . "', '" . $GLOBALS['ibforums']->member['id'] . "', '" . $row['forum_id'] . "')");

				return TRUE;
			} else {
				$this->sdkerror($this->lang['sdk_poll_noexist']);
				return FALSE;
			}
		}
	}

	/**
	 * Creates a new poll.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid Topic ID to associate the poll with
	 * @param string $question Question text.
	 * @param array $choices The options to vote for
	 * @return bool Success
	 */
	function new_poll($topicid, $question, $choices = array()) {
		// Check if we can do polls
		$info = $this->get_advinfo();
		if ($info['g_post_polls']) {
			// Check we have a good number of choices :)
			if (is_array($choices) AND count($choices) > 1 AND count($choices) < $GLOBALS['ibforums']->vars['max_poll_choices']) {
				// Check our Topic exists
				if (!$topicinfo = $this->get_topic_info(intval($topicid))) {
					$this->sdkerror($this->lang['sdk_topics_notexist']);
					return FALSE;
				}

				$thechoices = array(); // Init
				$count = '0';

				foreach ($choices as $i) {
					$thechoices[] = array('0' => $count,
						'1' => $this->makesafe($i),
						'2' => '0',
						);

					++$count;
				}
				// Now add it into the polls table
				$this->DB->query ("INSERT INTO acf_polls VALUES ('', '" . intval($topicid) . "', '" . time() . "', '" . serialize($thechoices) . "', '" . $GLOBALS['ibforums']->member['id'] . "', '0', '" . $topicinfo['forum_id'] . "', '" . $this->makesafe($question) . "')");
				// And change the topic's poll status to open
				$this->DB->query ("UPDATE acf_topics SET poll_state='open' WHERE tid='" . intval($topicid) . "'");
				// Return TRUE;
				return TRUE;
			} else {
				$this->sdkerror(sprintf($this->lang['sdk_poll_invalid_opts'], $GLOBALS['ibforums']->vars['max_poll_choices']));
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}
	}

	// -----------------------------------------------
	// FORUM FUNCTIONS
	// Functions relating to forums.
	// -----------------------------------------------
    /**#@+
	 * @group Forums
	 */
	/**
	 * Returns forums readable by the current member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Readable Forum Details
	 */
	function get_member_readable_forums () {
		if ($cache = $this->get_cache('get_member_readable_forums', $GLOBALS['ibforums']->member['id'])) {
			return $cache;
		} else {
			$this->DB->query ('SELECT f.id, f.name, f.description, f.topics, f.posts, f.read_perms, f.category, f.parent_id, c.name AS category_name FROM acf_forums f LEFT JOIN acf_categories c ON (f.category=c.id) ORDER BY f.position');
			$forums = array();
			while ($row = $this->DB->fetch_row()) {
				if ($GLOBALS['std']->check_perms($row['read_perms'])) {
					$row['readable'] = '1';
					$forums[$row['id']] = $row;
				}
			}

			$this->save_cache('get_member_readable_forums', $GLOBALS['ibforums']->member['id'], $forums);

			return $forums;
		}
	}

	/**
	 * Returns whether a forum can be read by
	 * the current member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $forumid
	 * @return bool Forum Is Readable
	 */
	function is_forum_readable ($forumid) {
		$readable = $this->get_member_readable_forums();

		if ($readable[$forumid]['readable'] == '1') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns forums postable in by the current member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Postable Forum Details
	 */
	function get_member_postable_forums () {
		if ($cache = $this->get_cache('get_member_postable_forums', $GLOBALS['ibforums']->member['id'])) {
			return $cache;
		} else {
			$this->DB->query ('SELECT f.id, f.name, f.description, f.topics, f.posts, f.reply_perms, f.category, f.parent_id, c.name AS category_name FROM acf_forums f LEFT JOIN acf_categories c ON (f.category=c.id) ORDER BY f.position');
			$forums = array();
			while ($row = $this->DB->fetch_row()) {
				if ($GLOBALS['std']->check_perms($row['reply_perms'])) {
					$row['postable'] = '1';
					$forums[$row['id']] = $row;
				}
			}

			$this->save_cache('get_member_postable_forums', $GLOBALS['ibforums']->member['id'], $forums);

			return $forums;
		}
	}

	/**
	 * Returns whether a forum can be posted in by
	 * the current member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $forumid
	 * @return bool Forum Is Postable In
	 */
	function is_forum_postable ($forumid) {
		$postable = $this->get_member_postable_forums();

		if ($postable[$forumid]['postable'] == '1') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns forums in which the current member
	 * can start new topics in.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Startable Forum Details
	 */
	function get_member_startable_forums () {
		if ($cache = $this->get_cache('get_member_startable_forums', $GLOBALS['ibforums']->member['id'])) {
			return $cache;
		} else {
			$this->DB->query ('SELECT f.id, f.name, f.description, f.topics, f.posts, f.start_perms, f.category, f.parent_id, c.name AS category_name FROM acf_forums f LEFT JOIN acf_categories c ON (f.category=c.id) ORDER BY f.position');
			$forums = array();
			while ($row = $this->DB->fetch_row()) {
				if ($GLOBALS['std']->check_perms($row['start_perms'])) {
					$row['startable'] = '1';
					$forums[$row['id']] = $row;
				}
			}

			$this->save_cache('get_member_startable_forums', $GLOBALS['ibforums']->member['id'], $forums);

			return $forums;
		}
	}

	/**
	 * Returns whether a forum can start topics in.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $forumid
	 * @return bool Forum Is Startable In.
	 */
	function is_forum_startable ($forumid) {
		$startable = $this->get_member_startable_forums();

		if ($startable[$forumid]['startable'] == '1') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns information on a forum.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $forumid
	 * @return array Forum Information.
	 */
	function get_forum_info ($forumid) {
		if ($cache = $this->get_cache('get_forum_info', $forumid)) {
			return $cache;
		} else {
			$this->DB->query ("SELECT f.* from acf_forums f WHERE f.id='" . $forumid . "'");
			if ($row = $this->DB->fetch_row()) {
				$this->save_cache('get_forum_info', $forumid, $row);
				return $row;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * List Topics in a Forum.
	 *
	 * The following settings can be used to overwrite the default query results.
	 * <br>'order' default: 'desc'
	 * <br>'start' default: '0' start with first record
	 * <br>'limit' default: '15' no. of topics per page
	 * <br>'orderby' default: 'last_post', others see below
	 *
	 * Sort keys: any of 'tid', 'title', 'posts', 'starter_name', 'starter_id', 'start_date', 'last_post', 'views', 'post_date'
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param mixed $forumid A Forum ID, the asterisk '*', or an array with IDs to show
	 * @param array $settings Query settings
	 * @param integer $bypassperms Default: 0=repect board permission, 1=bypass board permission
	 * @return array Topics in Forum.
	 */
	function list_forum_topics($forumid, $settings = array('order' => 'desc', 'limit' => '15', 'start' => '0', 'orderby' => 'last_post'), $bypassperms = '0') {
		// As of SDK 1.0 this function can be used to get topics
		// from multiple forums. So heres the updated thingy :)
		$expforums = array();
		if (is_array($forumid)) {
			foreach ($forumid as $i) {
				if ($this->is_forum_readable(intval($i)) OR $bypassperms) {
					$expforum[] = intval($i);
				}
			}
		} elseif ($forumid == '*') {
			// Get readable forums
			$readable = $this->get_member_readable_forums();
			foreach ($readable as $j => $k) {
				$expforum[] = intval($k['id']);
			}
		} else {
			if ($this->is_forum_readable(intval($forumid)) OR $bypassperms) {
				$expforum[] = intval($forumid);
			}
		}

		if (count($expforum) < 1) {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		} else {
			// What shall I order it by guv?
			$allowedorder = array('tid', 'title', 'posts', 'starter_name', 'starter_id', 'start_date', 'last_post', 'views', 'post_date');

			if (in_array($settings['orderby'], $allowedorder)) {
				$order = $settings['orderby'] . ' ' . (($settings['order'] == 'desc') ? 'DESC' : 'ASC');
			} elseif ($settings['orderby'] == 'random') {
				$order = 'RAND()';
			} else {
				$order = 'last_post ' . (($settings['order'] == 'desc') ? 'DESC' : 'ASC');
			}
			// Grab Posts
			$limit = $settings['limit'] ? intval($settings['limit']) : '15';
			$start = $settings['start'] ? intval($settings['start']) : '0';
			// Forum ID Code
			if ($forumid == '*' AND $bypassperms) {
				$forums = '';
			} else {
				$forums = 't.forum_id IN ('.implode(',', $expforum).')';
			}

			$this->DB->query ('SELECT t.*, p.*, g.g_dohtml AS usedohtml FROM acf_topics t LEFT JOIN acf_posts p ON (t.tid=p.topic_id) LEFT JOIN acf_members m ON (p.author_id=m.id) LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE '.$forums." AND t.approved='1' AND p.new_topic='1' ORDER BY ".$order." LIMIT $start,$limit");

			$return = array();
			while ($row = $this->DB->fetch_row()) {
				// Parse [doHTML] taggy
				$row['post'] = $GLOBALS['parser']->post_db_parse($row['post'], $row['usedohtml']);
				$return[] = $row;
			}
			return $return;
		}
	}

	/**
	 * Creates a new forum and returns it's ID.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $forumname The name of the Forum
	 * @param string $forumdesc The description
	 * @param integer $categoryid The category ID this forum belongs to
	 * @param integer $startperms Group IDs for Start posts permission
	 * @param integer $replyperms Group IDs for Reply-To posts permission
	 * @param integer $readperms Group IDs for Read posts permission
	 * @param integer $uploadperms Group IDs for Fileupload permission
	 * @return long New forum record ID or FALSE on failure
	 */
	function add_forum($forumname, $forumdesc, $categoryid, $startperms, $replyperms, $readperms, $uploadperms) {
		if (!$forumname) {
			$this->sdkerror($this->lang['sdk_forum_noname']);
			return FALSE;
		}

		$forumname = $this->makesafe(trim($forumname));
		$forumname = $this->makesafe(trim($forumdesc));

		$this->DB->query('SELECT MAX(id) as max FROM acf_forums');
		$row = $this->DB->fetch_row();
		$max = $row['max'];

		if ($max < 1) {
			$max = '0';
		}

		++$max;
		// Check Cat Exists. Meow!
		$this->DB->query ("SELECT * FROM acf_categories WHERE id='" . intval($categoryid) . "'");
		if (!$this->DB->fetch_row()) {
			$this->sdkerror($this->lang['sdk_cat_notexist']);
			return FALSE;
		}
		// Permissions
		$permissions = array('start' => $startperms,
			'reply' => $replyperms,
			'read' => $readperms,
			'upload' => $uploadperms,
			);
		$permsfinal = array();
		// Get Groups
		$groups = array();
		$this->DB->query ('SELECT g_id FROM acf_groups');
		while ($groupsr = $this->DB->fetch_row()) {
			$groups[] = $groupsr['g_id'];
		}

		foreach ($permissions as $i => $j) {
			if ($j == '*') {
				// All Groups
				$permsfinal[$i] = '*';
			} else {
				$x = array();
				foreach ($j as $l) {
					if (in_array($l, $groups)) {
						$x[] = intval($l);
					}
				}

				$permsfinal[$i] = implode (',', $x);
			}
		}
		// Finally Add it to the Database
		$DB_string = $this->DB->compile_db_insert_string(array('id' => $max,
				'position' => $max,
				'topics' => 0,
				'posts' => 0,
				'last_post' => '',
				'last_poster_id' => '',
				'last_poster_name' => '',
				'name' => $forumname,
				'description' => $forumdesc,
				'use_ibc' => 1,
				'use_html' => 0,
				'status' => 1,
				'start_perms' => $permsfinal['start'],
				'reply_perms' => $permsfinal['reply'],
				'read_perms' => $permsfinal['read'],
				'upload_perms' => $permsfinal['upload'],
				'password' => '',
				'category' => intval($categoryid),
				'last_id' => '',
				'last_title' => '',
				'sort_key' => 'last_post',
				'sort_order' => 'Z-A',
				'prune' => 30,
				'show_rules' => 0,
				'preview_posts' => 0,
				'allow_poll' => 1,
				'allow_pollbump' => 0,
				'inc_postcount' => 1,
				'parent_id' => '-1',
				'sub_can_post' => 1,
				'quick_reply' => 0,
				));

		$this->DB->query('INSERT INTO acf_forums (' . $DB_string['FIELD_NAMES'] . ') VALUES (' . $DB_string['FIELD_VALUES'] . ')');

		return $this->DB->get_insert_id();
	}

	// -----------------------------------------------
	// POST FUNCTIONS
	// -----------------------------------------------
    /**#@+
	 * @group Posts
	 */
	/**
	 * Adds a new post.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @param string $post Message body
	 * @param integer $disableemos Default: 0=disable emoticons, 1=enable
	 * @param integer $disablesig Default: 0=disable signatures, 1=enable
	 * @param integer $bypassperms Default: 0=repect board permission, 1=bypass permissions
	 * @param string $guestname Name for Guest user, Default: "" (empty string)
	 * @return int New post ID or FALSE on failure
	 */
	function add_post ($topicid, $post, $disableemos = '0', $disablesig = '0', $bypassperms = '0', $guestname = '') {
		$post = $this->makesafe($post);
		// Noms et tous d'invité.
		if ($this->is_loggedin()) {
			$postname = $GLOBALS['ibforums']->member['name'];
		} else {
			if ($guestname) {
				$postname = $GLOBALS['ibforums']->vars['guest_name_pre'] . $this->makesafe($guestname);
			} else {
				$postname = $GLOBALS['ibforums']->member['name'];
			}
		}
		// No Posting
		if ($GLOBALS['ibforums']->member['restrict_post']) {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}
		// Flooding
		if ($GLOBALS['ibforums']->vars['flood_control'] AND $GLOBALS['ibforums']->member['g_avoid_flood'] != '1') {
			if ((time() - $GLOBALS['ibforums']->member['last_post']) < $GLOBALS['ibforums']->vars['flood_control']) {
				$this->sdkerror(sprintf($this->lang['sdk_floodcontrol'], $GLOBALS['ibforums']->vars['flood_control']));
				return FALSE;
			}
		}
		// Check some Topic Stuff
		$this->DB->query ("SELECT t.*, f.* FROM acf_topics t LEFT JOIN acf_forums f ON (t.forum_id=f.id) WHERE t.tid='" . intval($topicid) . "'");
		if ($row = $this->DB->fetch_row()) {
			// Check User can Post to Forum
			if ($this->is_forum_postable($row['forum_id']) OR $bypassperms) {
				// Post Queue
				if ($row['preview_posts'] OR $GLOBALS['ibforums']->member['mod_posts']) {
					$preview = '1';
				} else {
					$preview = '0';
				}
				// What if the topic is locked
				if ($row['state'] != 'open' AND !$GLOBALS['ibforums']->member['g_post_closed']) {
					$this->sdkerror($this->lang['sdk_noperms']);
					return FALSE;
				}
				// Check they can reply
				if ($row['starter_id'] == $GLOBALS['ibforums']->member['id']) {
					if (!$GLOBALS['ibforums']->member['g_reply_own_topics']) {
						$this->sdkerror($this->lang['sdk_noperms']);
						return FALSE;
					}
				} else {
					if (!$GLOBALS['ibforums']->member['g_reply_other_topics']) {
						$this->sdkerror($this->lang['sdk_noperms']);
						return FALSE;
					}
				}

				$time = time();
				// If we're still here, we should be ok to add the post
				$this->DB->query ("INSERT INTO acf_posts (author_id, author_name, use_emo, use_sig, ip_address, post_date, post, queued, topic_id, forum_id) VALUES ('{$GLOBALS['ibforums']->member['id']}', '{$postname}', '" . ($disableemos ? '0' : '1') . "', '" . ($disablesig ? '0' : '1') . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $time . "', '" . addslashes($GLOBALS['parser']->convert(array('TEXT' => $post, 'CODE' => $row['use_ibc'], 'HTML' => $row['use_html'], 'SMILIES' => ($disableemos ? '0' : '1')))) . "', '{$preview}', '{$row['tid']}', '{$row['forum_id']}')");

				$postid = $this->DB->get_insert_id();
				// Update the Topics
				$this->DB->query ("UPDATE acf_topics SET last_poster_id='" . $GLOBALS['ibforums']->member['id'] . "', last_poster_name='" . $postname . "', posts=posts+1, last_post='" . $time . "' WHERE tid='" . intval($topicid) . "'");
				// Finally update the forums
				$this->DB->query ("UPDATE acf_forums SET last_poster_id='" . $GLOBALS['ibforums']->member['id'] . "', last_poster_name='" . $postname . "', posts=posts+1, last_post='" . $time . "', last_title='" . $row['title'] . "', last_id='" . intval($topicid) . "' WHERE id='" . intval($row['forum_id']) . "'");
				// Oh yes, any update the post count for the user
				if ($GLOBALS['ibforums']->member['id'] != '0') {
					if ($row['inc_postcount']) {
						$this->DB->query ("UPDATE acf_members SET posts=posts+1, last_post='" . time() . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					} else {
						$this->DB->query ("UPDATE acf_members SET last_post='" . time() . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					}
				}
				// That's it - I promise ;)
				// Nooo... Wait Stats too
				$this->DB->query ('UPDATE acf_stats SET TOTAL_REPLIES=TOTAL_REPLIES+1');

				return $postid;
			} else {
				$this->sdkerror($this->lang['sdk_noperms']);
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_topics_notexist']);
			return FALSE;
		}
	}

	/**
	 * Returns information on a post.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $postid
	 * @return array Post Information
	 */
	function get_post_info ($postid) {
		// Check for Post Cache
		if ($cache = $this->get_cache('get_post_info', $postid)) {
			return $cache;
		} else {
			$this->DB->query ("SELECT p.*, t.forum_id, t.title AS topic_name, g.g_dohtml AS usedohtml FROM acf_posts p LEFT JOIN acf_topics t ON (p.topic_id=t.tid) LEFT JOIN acf_members m ON (p.author_id=m.id) LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE p.pid='" . $postid . "'");
			if ($row = $this->DB->fetch_row()) {
				// Parse [doHTML] taggy
				$row['post'] = $GLOBALS['parser']->post_db_parse($row['post'], $row['usedohtml']);

				$this->save_cache('get_post_info', $postid, $row);
				return $row;
			} else {
				return FALSE;
			}
		}
	}

	// -----------------------------------------------
	// TOPIC FUNCTIONS
	// -----------------------------------------------
    /**#@+
	 * @group Topics
	 */
	/**
	 * Creates a new topic and returns the new topic ID on success.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $forumid
	 * @param string $title Topic title
	 * @param string $desc Topic description
	 * @param string $post Message body
	 * @param integer $disableemos Default: 0=disable emoticons, 1=enable
	 * @param integer $disablesig Default: 0=disable signatures, 1=enable
	 * @param integer $bypassperms Default: 0=repect board permission, 1=bypass permissions
	 * @param string $guestname Name for Guest user, Default: "" (empty string)
	 * @return long New topic ID or FALSE on failure
	 */
	function new_topic ($forumid, $title, $desc, $post, $disableemos = '0', $disablesig = '0', $bypassperms = '0', $guestname = '') {
		if (!$title) {
			$this->sdkerror($this->lang['sdk_topics_notitle']);
			return FALSE;
		}

		$title = $this->makesafe($title);
		$desc = $this->makesafe($desc);
		$post = $this->makesafe($post);
		// Noms et tous d'invité.
		if ($this->is_loggedin()) {
			$postname = $GLOBALS['ibforums']->member['name'];
		} else {
			if ($guestname) {
				$postname = $GLOBALS['ibforums']->vars['guest_name_pre'] . $this->makesafe($guestname);
			} else {
				$postname = $GLOBALS['ibforums']->member['name'];
			}
		}
		// No Posting
		if ($GLOBALS['ibforums']->member['restrict_post']) {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}
		// Flooding
		if ($GLOBALS['ibforums']->vars['flood_control'] AND $GLOBALS['ibforums']->member['g_avoid_flood'] != '1') {
			if ((time() - $GLOBALS['ibforums']->member['last_post']) < $GLOBALS['ibforums']->vars['flood_control']) {
				$this->sdkerror(sprintf($this->lang['sdk_floodcontrol']), $GLOBALS['ibforums']->vars['flood_control']);
				return FALSE;
			}
		}
		// Check some Forum Stuff
		$this->DB->query ("SELECT * FROM acf_forums WHERE id='" . intval($forumid) . "'");
		if ($row = $this->DB->fetch_row()) {
			// Check User can Post to Forum
			if ($this->is_forum_startable($row['id']) OR $bypassperms) {
				// Queuing
				if ($row['preview_posts'] OR $GLOBALS['ibforums']->member['mod_posts']) {
					$preview = '1';
				} else {
					$preview = '0';
				}

				$time = time();
				// Insert Topic Bopic
				$this->DB->query ("INSERT INTO acf_topics (title, description, state, posts, starter_id, start_date, last_poster_id, last_post, starter_name, last_poster_name, views, forum_id, approved, author_mode, pinned) VALUES ('{$title}', '{$desc}', 'open', '0', '{$GLOBALS['ibforums']->member['id']}', '" . $time . "', '{$GLOBALS['ibforums']->member['id']}', '" . $time . "', '{$postname}', '{$postname}', '0', '{$forumid}', '" . ($preview ? '0' : '1') . "', '1', '0')");

				$topicid = $this->DB->get_insert_id();

				$this->DB->query ("INSERT INTO acf_posts (author_id, author_name, use_emo, use_sig, ip_address, post_date, post, queued, topic_id, forum_id, new_topic, icon_id) VALUES ('{$GLOBALS['ibforums']->member['id']}', '{$postname}', '" . ($disableemos ? '0' : '1') . "', '" . ($disablesig ? '0' : '1') . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $time . "', '" . addslashes($GLOBALS['parser']->convert(array('TEXT' => $post, 'CODE' => $row['use_ibc'], 'HTML' => $row['use_html'], 'SMILIES' => ($disableemos ? '0' : '1')))) . "', '{$preview}', '" . $topicid . "', '{$forumid}', '1', '0')");
				// Finally update the forums
				$this->DB->query ("UPDATE acf_forums SET last_poster_id='" . $GLOBALS['ibforums']->member['id'] . "', last_poster_name='" . $postname . "', topics=topics+1, last_post='" . $time . "', last_title='" . $title . "', last_id='" . $topicid . "' WHERE id='" . intval($forumid) . "'");
				// Oh yes, any update the post count for the user
				if ($GLOBALS['ibforums']->member['id'] != '0') {
					if ($row['inc_postcount']) {
						$this->DB->query ("UPDATE acf_members SET posts=posts+1, last_post='" . time() . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					} else {
						$this->DB->query ("UPDATE acf_members SET last_post='" . time() . "' WHERE id='" . $GLOBALS['ibforums']->member['id'] . "' LIMIT 1");
					}
				}
				// And stats
				$this->DB->query ('UPDATE acf_stats SET TOTAL_TOPICS=TOTAL_TOPICS+1');
				// Return $topicid rather then TRUE as it is more use
				return $topicid;
			} else {
				$this->sdkerror($this->lang['sdk_noperms']);
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_forum_notexist']);
			return FALSE;
		}
	}

	/**
	 * Lists posts in a topic.
	 *
	 * The following settings can be used to overwrite the default query results.
	 * <br>'order' default: 'asc'
	 * <br>'start' default: '0' start with first record
	 * <br>'limit' default: '15' no. of topics per page
	 * <br>'orderby' default: 'post_date', others see below
	 *
	 * Sort keys: any of ''pid', 'author_id', 'author_name', 'post_date', 'post'
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @param array $settings optional query settings
	 * @param integer $bypassperms Default: 0=repect board permission, 1=bypass permissions
	 * @return array Topic Posts.
	 */
	function list_topic_posts($topicid, $settings = array('order' => 'asc', 'limit' => '15', 'start' => '0', 'orderby' => 'post_date'), $bypassperms = '0') {
		/* Our little 1.0 thingy which allows you to export stuff
		from everywhere and complicated wubbly cool stuff. */

		$sqlwhere = '';

		if (is_array($topicid)) {
			// get_topic_info() is too inefficent when we have alot of topic ids.
			$topics = '';
			foreach ($topicid as $i) {
				$i = intval($i);
				if ($topics) {
					$topics .= " OR tid='$i'";
				} else {
					$topics = " tid='$i'";
				}
			}
			// Query
			$getfid = $this->DB->query ('SELECT tid, forum_id FROM acf_topics WHERE ' . $topics);
			// Now we should how topic ids and their forum ids.
			while ($row = $this->DB->fetch_row($getfid)) {
				if ($this->is_forum_readable($row['forum_id']) OR $bypassperms) {
					if (!$sqlwhere) {
						$sqlwhere .= "(topic_id='" . $row['tid'] . "'";
					} else {
						$sqlwhere .= " OR topic_id='" . $row['tid'] . "'";
					}
				}
			}

			if ($sqlwhere) {
				$sqlwhere .= ') AND ';
				$cando = '1';
			} else {
				$this->sdkerror($this->lang['sdk_noperms']);
				return FALSE;
			}
		} elseif ($topicid == '*') {
			if ($bypassperms) {
				// Grab posts from the whole board
				$sqlwhere = '';
				$cando = '1';
			} else {
				// All topics. So we can grab them from all readable forums.
				$readable = $this->get_member_readable_forums();
				foreach ($readable as $j => $k) {
					if (!$sqlwhere) {
						$sqlwhere .= "(forum_id='" . $j . "'";
					} else {
						$sqlwhere .= " OR forum_id='" . $j . "'";
					}
				}

				if ($sqlwhere OR $cando) {
					$sqlwhere .= ') AND ';
					$cando = '1';
				} else {
					$this->sdkerror($this->lang['sdk_noperms']);
					return FALSE;
				}
			}
		} else {
			// Classic Posts from Topic Export
			// Grab Topic Info then check whether forum is readable.
			$topicinfo = $this->get_topic_info($topicid);
			if ($this->is_forum_readable($topicinfo['forum_id']) OR $bypassperms) {
				$sqlwhere = "topic_id='" . intval($topicid) . "' AND ";
				$cando = '1';
			} else {
				$this->sdkerror($this->lang['sdk_noperms']);
				return FALSE;
			}
		}
		// topic_id=''.intval($topicid).'' AND
		if ($cando) {
			// What shall I order it by guv?
			$allowedorder = array('pid', 'author_id', 'author_name', 'post_date', 'post');

			if (in_array($settings['orderby'], $allowedorder)) {
				$order = $settings['orderby'] . ' ' . (($settings['order'] == 'desc') ? 'DESC' : 'ASC');
			} elseif ($settings['orderby'] == 'random') {
				$order = 'RAND()';
			} else {
				$order = 'post_date ' . (($settings['order'] == 'desc') ? 'DESC' : 'ASC');
			}
			// Grab Posts
			$limit = $settings['limit'] ? intval($settings['limit']) : '15';
			$start = $settings['start'] ? intval($settings['start']) : '0';

			$this->DB->query ("SELECT p.*, g.g_dohtml AS usedohtml FROM acf_posts p LEFT JOIN acf_members m ON (p.author_id=m.id) LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE " . $sqlwhere . "p.queued='0' ORDER BY " . $order . " LIMIT " . $start . "," . $limit);

			$return = array();
			while ($row = $this->DB->fetch_row()) {
				// Parse [doHTML] taggy
				$row['post'] = $GLOBALS['parser']->post_db_parse($row['post'], $row['usedohtml']);
				// Add to return array
				$return[] = $row;
			}

			return $return;
		} else {
			$this->sdkerror($this->lang['sdk_noperms']);
			return FALSE;
		}
	}

	/**
	 * Returns information on a topic.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $topicid
	 * @return array Topic Information.
	 */
	function get_topic_info ($topicid) {
		// Check for Post Cache
		if ($cache = $this->get_cache('get_topic_info', $topicid)) {
			return $cache;
		} else {
			$this->DB->query ("SELECT t.*, p.*, g.g_dohtml AS usedohtml FROM acf_topics t LEFT JOIN acf_posts p ON (t.tid=p.topic_id) LEFT JOIN acf_members m ON (p.author_id=m.id) LEFT JOIN acf_groups g ON (m.mgroup=g.g_id) WHERE t.tid='" . intval($topicid) . "' AND p.new_topic='1'");
			if ($row = $this->DB->fetch_row()) {
				// Parse [doHTML] taggy
				$row['post'] = $GLOBALS['parser']->post_db_parse($row['post'], $row['usedohtml']);
				// Save Topic In Cache and Return
				$this->save_cache('get_topic_info', $topicid, $row);
				return $row;
			} else {
				return FALSE;
			}
		}
	}
	// -----------------------------------------------
	// SEARCH FUNCTIONS
	// Search things.
	// -----------------------------------------------
    /**#@+
	 * @group Search
	 */
	/**
	 * Performs a simple search and returns a search id.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param string $string Text to search for (SQL rules apply)
	 * @param string $forums Default: '*' (any), or list of forum IDs
	 * @param integer $dateorder Default: 0, 1=Order by post_date
	 * @return string Search ID or FALSE on failure
	 */
	function simple_search ($string, $forums = '*', $dateorder = 0) {
		// Get readable forums.
		$readable = $this->get_member_readable_forums();

		$search = array(); // Init

		if (is_array($forums)) {
			if (count($readable)) {
				foreach ($readable as $i) {
					if (in_array($i['id'], $forums)) {
						$search[] = $i['id'];
					}
				}
			} else {
				$this->sdkerror($this->lang['sdk_search_noforum']);
				return FALSE;
			}
		} else {
			if (count($readable)) {
				foreach ($readable as $i) {
					$search[] = $i['id'];
				}
			} else {
				$this->sdkerror($this->lang['sdk_search_noforum']);
				return FALSE;
			}
		}
		// We should now have got all the ids of forums to search in $search. One more check.
		if (!count($search)) {
			$this->sdkerror($this->lang['sdk_search_noforum']);
			return FALSE;
		}

		$tosearch = implode (',', $search);
		// We have what we want to search for and the forums
		// And also how we want to order it.
		// Wierd thing
		$this->DB->query('SELECT VERSION() AS version');
		if (!$row = $this->DB->fetch_row()) {
			$this->DB->query("SHOW VARIABLES LIKE 'version'");
			$row = $DB->fetch_row();
		}

		$version = explode('.', preg_replace('/^(.+?)[-_]?/', '\\1', $row['version']));

		$version['0'] = (!isset($version) OR !isset($version['0'])) ? '3' : $version['0'];
		$version['1'] = (!isset($version['1'])) ? '21' : $version['1'];
		$version['2'] = (!isset($version['2'])) ? '0' : $version['2'];

		$version = intval(sprintf('%d%02d%02d', $version['0'], $version['1'], intval($version['2'])));
		// We now have the mysql version in an int for later use.
		if ($version >= '40010') {
			// Remove stuff we cant have
			$string = str_replace(array ('|', '&quot;', '&gt;', '%'), array ('&#124;', '\'', '>', ''), trim($string));
		} else {
			$string = str_replace(array ('%', '_', '|'), array ('\\%', '\\_', '&#124;'), trim(strtolower($string)));
			$string = preg_replace('/\s+(and|or)$/' , '' , $string);
		}

		$string = trim($string);

		$this->DB->query("SELECT COUNT(*) as count FROM acf_posts p WHERE p.forum_id IN ({$tosearch}) AND MATCH(post) AGAINST ('" . $string . "' " . (($version >= '40010') ? 'IN BOOLEAN MODE' : '') . ')');
		$row = $this->DB->fetch_row();
		// No results?
		if ($row['count'] < '1') {
			$this->sdkerror($this->lang['sdk_search_noresults']);
			return FALSE;
		}

		$store = "SELECT MATCH(post) AGAINST ('" . $string . "' " . (($version >= '40010') ? 'IN BOOLEAN MODE' : '') . ") as score, t.tid, t.title, t.posts, t.views, f.category, f.id, f.name, p.post, p.author_id, p.author_name, p.post_date, p.pid FROM acf_posts p LEFT JOIN acf_forums f ON (p.forum_id=f.id) LEFT JOIN acf_topics t on (p.topic_id=t.tid) WHERE p.forum_id IN ({$tosearch}) AND t.title IS NOT NULL AND MATCH(post) AGAINST ('" . $string . "' " . (($version >= '40010') ? 'IN BOOLEAN MODE' : '') . ")";
		// Date order?
		if ($dateorder) {
			$store .= ' ORDER BY p.post_date DESC';
		}
		// Generate a search id
		$searchid = md5(uniqid(microtime(), 1));
		// Insert it into the database
		$this->DB->query ("INSERT INTO acf_search_results (id, search_date, topic_id, topic_max, member_id, ip_address, post_id, query_cache) VALUES('{$searchid}', '" . time() . "', '00', '{$row['count']}', '" . $GLOBALS['ibforums']->member['id'] . "', '" . $GLOBALS['ibforums']->input['IP_ADDRESS'] . "', '00', '" . addslashes($store) . "')");
		// This function really is complicated, huh?
		// Return id.
		return $searchid;
	}

	/**
	 * Returns the search results of a search.
	 *
	 * BBCode is stripped off the results. To hilight the search string use str_replace() in your main script.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $id
	 * @return array Search Results or FALSE on failure
	 */
	function get_search_results($id) {
		$this->DB->query ("SELECT * FROM acf_search_results WHERE id='" . $id . "'");
		if ($row = $this->DB->fetch_row()) {
			$searchinfo = $row;
			$tomato = stripslashes($row['query_cache']);
			$this->DB->query ($tomato);
			$results = array();

			while ($row = $this->DB->fetch_row()) {
				// Apparently we have to strip BBCode and stuff so we
				// look cool.
				$row['post'] = preg_replace('#\[.+?/?\]#', '', $GLOBALS['parser']->unconvert($row['post']));
				// We won't highlight the word or anything because the user
				// can do it in they're script with simple str_replace.
				$results[] = $row;
			}

			$searchinfo['results'] = $results;

			return $searchinfo;
		}

		return FALSE;
	}

	// -----------------------------------------------
	// SKIN FUNCTIONS
	// Functions which do stuff with your skins.
	// -----------------------------------------------
    /**#@+
	 * @group Skins
	 */
	/**
	 * Returns the Skin ID of the skin used by a member.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $memberid
	 * @return array Information on Skin or FALSE on failure
	 */
	function get_skin_id ($memberid = '') {
		if ($memberid) {
			$info = $this->get_advinfo($memberid);
		} else {
			$info = $this->get_advinfo();
		}

		if ($info['skin']) {
			return $info['skin'];
		} else {
			/*
			Thanks to Foxrer who reported the discrepancies
			between uid and sid and Ripper for his research
			into it :) */

			$this->DB->query ("SELECT sid FROM acf_skins WHERE default_set='1'");
			if ($row = $this->DB->fetch_row()) {
				return $row['sid'];
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Gets information on a skin.
	 *
	 * @author Ripper
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $skinid
	 * @return array Information on Skin or FALSE on failure
	 */
	function get_skin_info ($skinid) {
		// Adapted from the original function submitted by ripper
		if ($skinid >= 0) { // If they've specified a skin
			$this->DB->query ("SELECT * FROM acf_skins WHERE sid='" . $skinid . "'");

			if ($row = $this->DB->fetch_row()) {
				return $row;
			} else {
				return FALSE;
			}
		} else { // Or nowt
			return FALSE;
		}
	}

	/**
	 * Changes the current user's skin.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @param integer $skinid
	 * @param integer $userid
	 * @return bool Success.
	 */
	function set_user_skin ($skinid, $userid = '') {
		// Check it exists
		if ($this->get_skin_info($skinid)) {
			// Grab current member id unless specified
			if (!$userid) {
				$userid = $GLOBALS['ibforums']->member['id'];
			}

			if ($this->update_member(array('skin' => $skinid), $userid)) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			$this->sdkerror($this->lang['sdk_skin_notexist']);
			return FALSE;
		}
	}

	/**
	 * Grabs the IDs of all the avaliable skins.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Skin IDs
	 */
	function list_skins () {
		// Grab all skins which aren't hidden
		$this->DB->query ("SELECT sid FROM acf_skins WHERE hidden='0'");
		$skins = array();

		while ($row = $this->DB->fetch_row()) {
			$skins[] = $row['sid'];
		}

		return $skins;
	}

	/**
	 * Pulls and displays CSS from forums depending on user's skin.
	 *
	 * Use the optional $return parameter to store the CSS in a string for
	 * later processing, instead of instantly sending it to the browser.
	 *
	 * @author Scyth <scyth@wewub.com>
	 * @param bool $return Whether to return CSS instead of sending it to the browser.
	 * @param bool $add_tag Whether to wrap the CSS with the STYLE tag.
	 * @return string The Style sheet if $return was set, void else
	 * @since 1.1 $add_tag added, ID attribute for STYLE tag
	 */
	function get_css($return = FALSE, $add_tag=TRUE) {

		$skin = $this->get_skin_info($this->get_skin_id()); // there we have the $skin var now..

		$getcss = $skin['css_id']; // heh css id please

		$this->DB->query ("SELECT * FROM acf_skins WHERE default_set = 1");

		$default = $this->DB->fetch_row();

		if ($getcss == '') { // what if its 0 (guest etc)
			$getcss = $default['css_id']; // make it the default skin - change 13 to match
		}

		// now we have the table.. but now what?
		$this->DB->query ('SELECT * FROM acf_css WHERE cssid = ' . $getcss . '');
		$css = $this->DB->fetch_row();
		// convert <#IMG_DIR#>
		$css = str_replace('<#IMG_DIR#>' , '' . $skin['img_dir'] . '', $css);
		// convert to lead to forums
		$css = str_replace('style_images' , '' . $this->board_url . '/style_images', $css);
		$img_dir = $skin['img_dir'];

		// and here are the awesome style tags (used for later)
		// with an ID to use client side scripting
		if ($add_tag) {
			$style = '<style type="text/css" id="css_'.$getcss.'">' . $css['css_text'] . '</style>';
		} else {
			$style = $css['css_text'];
		}

		if ($return) {
			return $style;
		} else {
			echo $style;
		}
	}

	// -----------------------------------------------
	// STATISTICS FUNCTIONS
	// Functions which retrieve misc. stats on IPB
	// -----------------------------------------------
    /**#@+
	 * @group Statistics
	 */
	/**
	 * Gets board statistics.
	 *
	 * @author Cow <khlo@global-centre.com>
	 * @return array Board Statistics
	 */
	function get_board_stats() {
		// Check for cache
		if ($cache = $this->get_cache('get_board_stats', '1')) {
			return $cache;
		} else {
			$this->DB->query ('SELECT * FROM acf_stats');
			$row = $this->DB->fetch_row();
			// Because I don't like all these capitals and ugly column names
			$stats = array(
				// Totals
				'total_replies' => $row['TOTAL_REPLIES'],
				'total_topics' => $row['TOTAL_TOPICS'],
				'total_members' => $row['MEM_COUNT'],
				// Members
				'newest_member_id' => $row['LAST_MEM_ID'],
				'newest_member_name' => $row['LAST_MEM_NAME'],
				// Most Online Statistics
				'most_online_count' => $row['MOST_COUNT'],
				'most_online_date' => $row['MOST_DATE'],
				);

			$this->save_cache('get_board_stats', '1', $stats);
			return $stats;
		}
	}

	// -----------------------------------------------
	// Old Functions which are now deprecated
	// -----------------------------------------------
    /**#@+
	 * @group Deprecated
	 * @deprecated since 1.0.0
	 */
	/**
	 * Old Function for backward compatibility.
	 * @see sdk_error()
	 * @deprecated since 1.0.0
	 */
	function ipbsdk_error() {
		$this->sdkerror(sprintf($this->_depr_msg, 'ipbsdk_error()', 'sdk_error()'));
		return $this->sdk_error();
	}

	/**
	 * Old Function for backward compatibility.
	 * @see sdk_version()
	 * @deprecated since 1.0.0
	 */
	function ipbsdk_version() {
		$this->sdkerror(sprintf($this->_depr_msg, 'ipbsdk_version()', 'sdk_version()'));
		return $this->sdk_version();
	}

	/**
	 * Old Function for backward compatibility.
	 * @see sdk_info()
	 * @deprecated since 1.0.0
	 */
	function ipbsdk_info() {
		$this->sdkerror(sprintf($this->_depr_msg, 'ipbsdk_info()', 'sdk_info()'));
		return $this->sdk_info();
	}

	/**
	 * Old Function for backward compatibility.
	 * @see sdk_date()
	 * @deprecated since 1.0.0
	 */
	function ipbsdk_date($timestamp = '', $dateformat = '', $noboard = '0', $nomember = '0') {
		$this->sdkerror(sprintf($this->_depr_msg . $this->_depr_new, 'ipbsdk_date()', 'sdk_date()'));
		return $this->sdk_date($timestamp, $dateformat, $noboard, $nomember);
	}

} // class IPBSDK

?>