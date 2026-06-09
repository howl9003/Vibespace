<?php
/*
 * Horde Configuration File
 *
 * This file contains global configuration settings for Horde.  Values may be
 * safely edited by hand.  Use horde.php.dist as a reference.
 *
 * Default user preferences are defined in 'prefs.php'.
 *
 * Strings should be enclosed in 'quotes'.
 * Integers should be given literally (without quotes).
 * Boolean values may be 'true' or 'false'.
 *
 * $Horde: horde/config/horde.php.dist,v 1.47.2.36 2003/10/14 16:52:31 slusarz Exp $
 */

/**
 ** General Horde Settings
 **/

// The value to set error_reporting() to. Valid values are: E_ERROR,
// E_WARNING, E_PARSE, E_NOTICE, E_CORE_ERROR, E_CORE_WARNING,
// E_ALL. See http://www.php.net/manual/function.error-reporting.php
// for more information.
//$conf['debug_level'] = E_ALL;

// If we need to perform a long operation, what should we set
// max_execution_time to (in seconds)? 0 means no limit; however, a
// value of 0 will cause a warning if you are running in safe
// mode. See http://www.php.net/manual/function.set-time-limit.php for
// more information.
$conf['max_exec_time'] = 0;

// What name should we use for the session that Horde apps share? If
// you want to share sessions with other applications on your
// webserver, you will need to make sure that they are using the same
// session name.
$conf['session_name'] = 'Horde';

// What caching level should we use for the session? DO NOT CHANGE
// THIS UNLESS YOU _REALLY_ KNOW WHAT YOU ARE DOING. Setting this to
// anything other than 'nocache' will almost certainly result in
// severely broken script behavior.
$conf['cache_limiter'] = 'nocache';

// How long should sessions last? 0 means that the session ends when
// the user closes their browser. Set other values with care - see
// http://www.php.net/manual/en/function.session-set-cookie-params.php.
$conf['session_timeout'] = 0;

// Determines how we generate full URLs (for location headers and
// such). Possible values are:
//   0 - Assume that we are not using SSL and never generate https URLS.
//   1 - Assume that we are using SSL and always generate https URLS.
//   2 - Attempt to auto-detect, and generate URLs appropriately.
$conf['use_ssl'] = 2;

// If this option is set to true, and you have the php zlib extension,
// pages over a certain size will be compressed and sent to the
// browser as gzip-encoded data in order to save bandwidth. There is a
// CPU-usage penalty to pay for this, but the decrease in page size
// can be dramatic (70k to under 10k for a full mailbox page), and is
// more than worth it over anything but an extremely fast link.
//
// However, some versions of Internet Explorer 6 have displayed some
// buggy behavior when it comes to compressed web pages. See:
// http://lists.horde.org/archives/imp/Week-of-Mon-20030407/031952.html         
// In Horde 2.x, there is no method to turn off compression for individual
// browser types so the only method to ensure that this behavior will
// not be seen is to completely turn compression off.
$conf['compress_pages'] = true;

// What umask should we run with? This will affect the permissions on
// any temporary files that are created. This value is an integer
// (specify it WITHOUT quotes).
$conf['umask'] = 077;

// If you want to use a temporary directory other than the system
// default or the one specified in php's upload_tmp_dir value, enter
// it here.
$conf['tmpdir'] = null;


/**
 ** Horde Authentication
 **/

// If you want to use IMP with Horde and don't want to let the users
// login twice (once for Horde and once for IMP) you can setup Horde
// to let IMP do the authentication stuff. Just uncomment the
// auth/login/logout lines in registry.php instead of configuring the
// following settings.

// What backend should we use for authenticating users to Horde? Valid
// options are currently 'imap', 'ldap', 'mcal', 'sql', 'ftp', 'smb',
// 'krb5' and 'radius'.
$conf['auth']['driver'] = '';

// An array holding any parameters that the Auth object will need to
// function correctly.
$conf['auth']['params'] = array();

// For IMAP, this is the server name, port, protocol, etc.
// Protocol is one of 'imap/notls' (or only 'imap' if you have a
// c-client version 2000c or older), 'imap/ssl', or imap/ssl/novalidate-cert
// (for a self-signed certificate).  Default ports are 143 for imap and
// 993 for imap over ssl.
// $conf['auth']['params']['dsn'] = '{imap.example.com:143/imap}INBOX';

// See $conf['prefs']['params'] further down for an example how to
// setup a SQL backend. But you must use the horde_users SQL table for
// the authentication driver. A SQL script can be found in
// horde/scripts/db/auth.sql.

// For kerberos (krb5) logins, see horde/lib/Auth/krb5.php for
// instructions.

// For RADIUS (radius) logins, see horde/lib/Auth/radius.php for
// instructions.

/**
 ** Horde Logging
 **/

// Should Horde log errors and other useful information?
$conf['log']['enabled'] = true;

// What log driver should we use? Valid values are 'file', 'mcal',
// 'sql', and 'syslog'.
$conf['log']['type'] = 'file';

// What is the name of the log? For the 'file' driver, this is the
// path to a text file; for mcal, it would be the name of a calendar,
// and for sql it would be the table name to use. For the 'syslog'
// driver it is the facility as a _constant_ (with no quotes), e.g.:
// ... = LOG_LOCAL0;
$conf['log']['name'] = '/tmp/horde.log';

// What level of messages should we log? The values are LOG_EMERG,
// LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO,
// and LOG_DEBUG. Each level logs itself and all those that come
// before it: LOG_ALERT would only log alerts and emergencies, but
// LOG_DEBUG would log everything.
$conf['log']['priority'] = LOG_NOTICE;

// What identifier should we use in the logs?
$conf['log']['ident'] = 'HORDE';

// Any additonal configuration information, like an MCAL or database
// username and password.
$conf['log']['params'] = array();


/**
 ** Preference System Settings
 **/

// What preferences driver should we use? Valid values are 'none'
// (meaning use system defaults and don't save any user preferences),
// 'session' (preferences only persist during the login), 'ldap',
// and 'sql'.
$conf['prefs']['driver'] = 'none';

// Any parameters that the preferences driver needs. This includes
// database or ldap server, username/password to connect with, etc.
$conf['prefs']['params'] = array();

// This is an example configuration for a MySQL preference backend.
// The SQL script to setup the preference database is placed in
// horde/scripts/db/prefs.sql.
// $conf['prefs']['params']['phptype'] = 'mysql';
// $conf['prefs']['params']['hostspec'] = 'localhost';
// $conf['prefs']['params']['username'] = 'horde';
// $conf['prefs']['params']['password'] = '*****';
// $conf['prefs']['params']['database'] = 'horde';
// $conf['prefs']['params']['table'] = 'horde_prefs';

// This is an example configuration for an LDAP preference backend.
// The schemas needed for ldap are in horde/scripts/ldap.  For more
// information see sources and comments in horde/lib/Prefs/ldap.php.
//$conf['prefs']['driver'] = 'ldap';
//$conf['prefs']['params']['hostspec'] = 'localhost';
//$conf['prefs']['params']['port'] = '389';
//$conf['prefs']['params']['basedn'] = 'dc=example,dc=org';
//$conf['prefs']['params']['uid'] = 'mail';
/*
 * The following is valid but would only be necessary if users
 * do NOT have permission to modify their own ldap accounts.
 */
//$conf['prefs']['params']['rootdn'] = 'cn=Manager,dc=example,dc=org';
//$conf['prefs']['params']['username'] = 'Manager';
//$conf['prefs']['params']['password'] = 'password';


/**
 ** Cache System Settings
 **/

// If you want to enable the Horde Cache, select a driver here.
// This is used to speed up portions of Horde by storing
// commonly processed objects to disk.
// Valid values are 'none' (don't cache any objects),
//                  'file' (store objects in filesystem)
$conf['cache']['driver'] = 'none';
// $conf['cache']['driver'] = 'file';

// Any parameters that the caching driver needs.
$conf['cache']['params'] = array();
// $conf['cache']['params']['dir'] = '/var/cache/horde';


/**
 ** Mailer
 **/

// What method should we use for sending mail? Valid options are
// currently 'sendmail' and 'smtp'.
$conf['mailer']['type'] = 'sendmail';

// An array holding any parameters that the Mail object will need to
// function correctly.
$conf['mailer']['params'] = array();

// For sendmail, this is mainly the 'sendmail_path option. Additionally, we
// want to use the '-oi' argument so that sendmail does not interpret a
// single '.' in the body of a message as the end of input.
// $conf['mailer']['params'] = array(
//     'sendmail_path' => '/usr/lib/sendmail',
//     'sendmail_args' => '-oi'
// );

// The 'smtp' driver normally will require a server name. Additional
// parameters can also be defined - see below for more details.
// $conf['mailer']['params'] = array(
//     /* The server to connect to. */
//     'host' => 'smtp.example.com',
//     /* The port to connect to. DEFAULT: 25 */
//     'port' => 25,
//     /* The local hostname/domain. DEFAULT: localhost */
//     'localhost' => 'localhost',
//     /* Use SMTP authentication?  DEFAULT: No (false) */
//     'auth' => false,
//     /* The username to use for SMTP authentication. */
//     'username' => null,
//     /* The password to use for SMTP authentication. */
//     'password' => null,
// );


/**
 ** Virtual File Storage
 **/

// If a VFS (virtual filesystem) backend is required, which one should
// we use? Options are 'file' and 'sql'.
$conf['vfs']['type'] = 'file';

// What configuration parameters should be set for the VFS system? For
// the 'file' driver, the only parameter is 'vfsroot' - where on the
// real filesystem should Horde use as root of the virtual
// filesystem. For the 'sql' driver, see the examples for the 'prefs'
// section, above.
$conf['vfs']['params']['vfsroot'] = '/tmp';


/**
 ** Custom Session Handler
 **/

// If we are defining a custom session handler, what sessionhandler
// driver should we use? Valid options are 'none', 'external', 'dbm',
// 'mysql', 'sapdb', and 'sql'. In the case of 'none' the default php
// (file-based) session handler is used. 'external' is a way to get
// Horde to gracefully handle a session handler that you've already
// defined elsewhere. See below ($conf['sessionhandler']['params'])
// for what you need to configure for the 'external' driver.
//
// NOTE for database-based session handlers: if you access your
// database through ODBC, you will almost certainly need to change
// PHP's default value for odbc.defaultlrl (this is a php.ini
// setting). The default is 4096, which is too small (your session
// data will be chopped off), and setting it to 0 DOES NOT work - that
// doesn't mean no limit, for some reason. odbc.defaultlrl = 32768
// seems to work pretty well for me (using MSSQL-2000).
//
// PLEASE NOTE: CUSTOM SESSION HANDLERS WILL NOT WORK WITH IMP AS THE
// AUTHENTICATION HANDLER (see config/registry.php) *UNLESS* YOU ARE USING
// IMP 3.2 OR GREATER!
$conf['sessionhandler']['type'] = 'none';

// Any parameters that the session handler driver needs.
//
// The 'external' driver expects this to be a hash, with 6 entries:
//
// $conf['sessionhandler']['params']['open']    = 'your session open() function';
// $conf['sessionhandler']['params']['close']   = 'your session close() function';
// $conf['sessionhandler']['params']['read']    = 'your session read() function';
// $conf['sessionhandler']['params']['write']   = 'your session write() function';
// $conf['sessionhandler']['params']['destroy'] = 'your session destroy() function';
// $conf['sessionhandler']['params']['gc']      = 'your session gc() function';
//

// Database or other drivers might require configuration parameters
// here.
// Required/optional parameters can be found at the top of the individual
// driver files located in horde/lib/SessionHandler/.
// $conf['sessionhandler']['params'] = array();
// $conf['sessionhandler']['params']['phptype'] = 'mysql';
// $conf['sessionhandler']['params']['hostspec'] = 'localhost';
// $conf['sessionhandler']['params']['username'] = 'horde';
// $conf['sessionhandler']['params']['password'] = '*****';
// $conf['sessionhandler']['params']['database'] = 'horde';


/**
 ** Problem Reporting
 **/

// Should we display a problem reporting link in Horde application
// menus?
$conf['problems']['enabled'] = false;

// If so, where should problem report emails be sent?
$conf['problems']['email'] = 'webmaster@example.com';


/**
 ** User Capabilities and Constraints
 **/

// Should we display help links to the user?
$conf['user']['online_help'] = false;


/**
 ** Stylesheets
 **/

// If this is true, then we will allow the browser to cache generated
// stylesheets, saving us from generating the stylesheet on every page
// request, but meaning that users will need to do a manual refresh to
// see any stylesheet changes.
$conf['css']['cached'] = true;


/**
 ** Menu settings
 **/

// Should we use DHTML to display a floating menu of Horde appliation
// links, instead of a frame?
$conf['menu']['floating_bar'] = false;
