<?php
/**
 * IPB SDK - Version 1.0
 * Default Configuration File.
 *
 * This should be edited to provide the default settings for your
 * SDK installation. These can be changed at runtime.
 *
 * Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Code (c) 2003-2004 IPB SDK Development Team
 * http://ipbsdk.sourceforge.net
 *
 * @package 	IPBSDK
 * @author 		IPB SDK Development Team
 * @version     04/26/2004
 * @copyright 	2003-2004 IPB SDK Development Team
 */

/**
 * The full qualified filesystem path to the folder of your IPB installation.
 * You must add a trailing slash.
 *
 * <b>Default path:</b> $_SERVER['DOCUMENT_ROOT'] . "/forum/"<br>
 * <b>Example path:</b> "/home/public_html/community/forums/"
 *
 * @global string
 * @see DOCUMENT_ROOT
 */
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/board/';

/**
 * The full qualified URL to your board without '/index.php'.
 * You must not add a trailing slash.
 *
 * <b>Default URL:</b> 'http://' . $_SERVER['HTTP_HOST'] . '/forum';
 * <b>Example URL:</b> "http://www.mydomain.com/community/forums";
 *
 * @global string
 */
$board_url = 'http://'.$_SERVER['HTTP_HOST'].'/board';

/**
 * The Default SDK Language Pack.
 *
 * Language packs should be named lang_ipbsdk_XX.php where 'XX' is the
 * language and be situated in the lib/ folder.
 * By default, this uses the "en" (English) language pack.
 *
 * @global string
 * @see IPBSDK::set_language()
 */
$sdklang = "en";

/**
 * The version of Invision Power Board you are using.
 *
 * This should be set to 110 for Version 1.1.*, 120 for Version 1.2.*,
 * or 130 for Version 1.3.*
 *
 * @global string
 */
$board_version = '130';


/**
 * Enable Caching of SQL Queries.
 *
 * It is strongly recommended you keep this on unless you want to use alot of SQL Queries :)
 *
 * @global string
 */
$allow_caching = '1';

?>
