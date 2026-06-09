<?php
/*
 * $Horde: horde/lib/Horde.php,v 1.118.2.50 2003/08/04 16:10:43 slusarz Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

// Horde Framework Actions
/** @constant NOOP The null action/noop. */
define('NOOP', 0);
/** @constant NO_ACTION The null action/noop. */
define('NO_ACTION', 0);

/** @constant HORDE_UPDATE_PREFS Update preferences. */
define('HORDE_UPDATE_PREFS', 1);
/** @constant UPDATE_PREFS Update preferences. */
define('UPDATE_PREFS', 1);

/** @constant HORDE_LOGIN The login action. */
define('HORDE_LOGIN', 50);

/** @constant HORDE_IMPORT Importing data action. */
define('HORDE_IMPORT', 51);

/** @constant HORDE_IMPORT Exporting data action. */
define('HORDE_EXPORT', 52);

/** @constant HORDE_SEND_PROBLEM_REPORT Send the submitted problem report. */
define('HORDE_SEND_PROBLEM_REPORT', 53);

/** @constant HORDE_CANCEL_PROBLEM_REPORT Cancel a problem report. */
define('HORDE_CANCEL_PROBLEM_REPORT', 54);


// Horde Message Types
/** @constant HORDE_MESSAGE An informational message. */
define('HORDE_MESSAGE', 0);

/** @constant HORDE_SUCCESS A success message. */
define('HORDE_SUCCESS', 1);

/** @constant HORDE_WARNING A warning message. */
define('HORDE_WARNING', 2);

/** @constant HORDE_ERROR Unrecoverable failure. */
define('HORDE_ERROR', 4);


/**
 * Removes given elements at request shutdown.
 * If called with a filename will delete that file at request shutdown.
 * If called with a directory will remove that directory and all files in
 * that directory at request shutdown.
 * If called with no arguments, unlink all elements registered.
 * The first time it is called, it initializes the array and registers itself
 * as a shutdown function - no need to do so manually.
 * The second parameter allows the unregistering of previously registered
 * elements.
 *
 * @access public
 *
 * @param optional string $filename   The filename to be deleted at the end of
 *                                    the request.
 * @param optional boolean $register  If true, then register the element for
 *                                    deletion, otherwise, unregister it.
 */
function _fileCleanup($filename = false, $register = true)
{
    static $dirs, $files;

    /* Initialization of variables and shutdown functions. */
    if (!isset($files) || !is_array($files)) {
        $dirs = array();
        $files = array();
        register_shutdown_function('_fileCleanup');
    }

    if ($register) {
        if (!$filename) {
            foreach ($files as $file => $val) {
                /* Delete file */
                if ($val && @file_exists($file)) {
                    @unlink($file);
                }
            }
            foreach ($dirs as $dir => $val) {
                /* Delete directories */
                if ($val && @file_exists($dir)) {
                    /* Make sure directory is empty. */
                    $dir_class = dir($dir);
                    while (false !== ($entry = $dir_class->read())) {
                        if ($entry != '.' && $entry != '..') {
                            @unlink($dir . '/' . $entry);
                        }
                    }
                    $dir_class->close();
                    @rmdir($dir);
                }
            }
        } else {
            if (@is_dir($filename)) {
                $dirs[$filename] = true;
            } else {
                $files[$filename] = true;
            }
        }
    } else {
        $dirs[$filename] = false;
        $files[$filename] = false;
    }
}

/**
 * The Horde:: class provides the functionality shared by all Horde
 * applications.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Horde {

    /**
     * Add a message to the Horde message stack.
     *
     * @access public
     *
     * @param string $message The text description of the message.
     * @param int    $type    The type of message: HORDE_ERROR,
     *                        HORDE_WARNING, HORDE_SUCCESS, or HORDE_MESSAGE.
     */
    function raiseMessage($message, $type = HORDE_MESSAGE)
    {
        global $hordeMessageStack;
        $hordeMessageStack[] = array('type' => $type, 'message' => $message);
    }

    /**
     * Log a message to the global Horde log backend.
     *
     * @access public
     * @param mixed $message     Either a string or a PEAR_Error object.
     * @param string $file       What file was the log function called from (__FILE__) ?
     * @param integer $line      What line was the log function called from (__LINE__) ?
     * @param integer $priority  (optional) The priority of the message. One of:
     *                           LOG_EMERG
     *                           LOG_ALERT
     *                           LOG_CRIT
     *                           LOG_ERR
     *                           LOG_WARNING
     *                           LOG_NOTICE
     *                           LOG_INFO
     *                           LOG_DEBUG
     */
    function logMessage($message, $file, $line, $priority = LOG_INFO)
    {
        static $logcheck;
        global $conf;

        if (!$conf['log']['enabled']) {
            return;
        }

        if ($priority > $conf['log']['priority']) {
            return;
        }

        if (!isset($logcheck)) {
            // Try to make sure that we can log messages somehow.
            if (!array_key_exists('log', $conf) ||
                !array_key_exists('type', $conf['log']) ||
                !array_key_exists('name', $conf['log']) ||
                !array_key_exists('ident', $conf['log']) ||
                !array_key_exists('params', $conf['log'])) {
                Horde::fatal(new PEAR_Error('Horde is not correctly configured to log error messages. You must configure at least a text file log in horde/config/horde.php.'), __FILE__, __LINE__, false);
            }
            $logcheck = true;
        }

        include_once 'Log.php';
        $logger = &Log::singleton($conf['log']['type'], $conf['log']['name'],
                                  $conf['log']['ident'], $conf['log']['params']);

        if (!$logger) {
            Horde::fatal(new PEAR_Error('An error has occurred. Furthermore, Horde encountered an error attempting to log this error. Please check your Horde logging configuration in horde/config/horde.php.'), __FILE__, __LINE__, false);
        }

        if (PEAR::isError($message)) {
            $userinfo = $message->getUserInfo();
            $message = $message->getMessage();
            if (!empty($userinfo)) {
                if (is_array($userinfo)) {
                    $userinfo = implode(', ', $userinfo);
                }
                $message .= ': ' . $userinfo;
            }
        }

        $app = array_key_exists('registry', $GLOBALS) ? $GLOBALS['registry']->getApp() : 'horde';
        $message = '[' . $app . '] ' . $message . ' [on line ' . $line . ' of "' . $file . '"]';

        /* Make sure to log in the system's locale. */
        $locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        $logger->log($message, $priority);

        /* Restore original locale. */
        setlocale(LC_TIME, $locale);

        return true;
    }

    /**
     * Abort with a fatal error, displaying debug information to the
     * user.
     *
     * @access public
     *
     * @param object PEAR_Error $error  An error object with debug information.
     * @param integer $file             The file in which the error occured.
     * @param integer $line             The line on which the error occured.
     * @param optional boolean $log     Log this message via Horde::logMesage()?
     */
    function fatal($error, $file, $line, $log = true)
    {
        include_once HORDE_BASE . '/lib/Text.php';

        $errortext = _("<b>A fatal error has occurred:</b>") . "<br /><br />\n";
        if (is_object($error) && method_exists($error, 'getMessage')) {
            $errortext .= $error->getMessage() . "<br /><br />\n";
        }
        $errortext .= sprintf(_("[line %s of %s]"), $line, $file);

        if ($log) {
            $errortext .= "<br /><br />\n";
            $errortext .= _("Details have been logged for the administrator.");
        }

        // Log the fatal error via Horde::logMessage() if requested.
        if ($log) {
            Horde::logMessage($error, $file, $line, LOG_EMERG);
        }

        // Hardcode a small stylesheet so that this doesn't depend on
        // anything else.
        echo <<< HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Horde :: Fatal Error</title>
<style type="text/css">
<!--
body { font-family: Geneva,Arial,Helvetica,sans-serif; font-size: 12px; background-color: #222244; color: #ffffff; }
.header { color: #ccccee; background-color: #444466; font-family: Verdana,Helvetica,sans-serif; font-size: 12px; }
-->
</style>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="0">
<tr><td class="header" align="center">$errortext</td></tr>
</table>
</body>
</html>
HTML;

        exit;
    }

    /**
     * Check for the existence of the given function.
     *
     * @access public
     *
     * @param string  $function  The name of the function to test.
     * @param boolean $fatal     (optional) Exit fatally if $function doesn't
     *                           exist.
     * @param boolean $complaint (optional) Display an error message if
     *                           $function doesn't exist.
     *
     * @return boolean The availability of $function.
     */
    function functionCheck($function, $fatal = false, $complaint = '')
    {
        if (function_exists($function)) {
            return true;
        } else {
            if (!empty($complaint)) {
                echo "<h2>$complaint</h2>\n";
            }
            if ($fatal) {
                exit;
            }
            return false;
        }
    }

    /**
     * Return a session-id-ified version of $uri.
     *
     * @access public
     *
     * @param string  $uri                   The URI to be modified.
     * @param boolean $full                  Generate a full
     *                                       (http://server/path/) URL.
     * @param boolean $always_append_session Tack on the session ID even if
     *                                       cookies are present.
     *
     * @return string The url with the session id appended
     */
    function url($uri, $full = false, $always_append_session = false)
    {
        $protocol = 'http';

        if ($full) {
            global $conf, $registry;

            /* Store connection parameters in local variables. */
            $server_port = $registry->getParam('server_port');
            $server_name = $registry->getParam('server_name');

            if ($conf['use_ssl'] == 1) {
                $protocol = 'https';
            } else if ($conf['use_ssl'] == 2) {
                if (Horde::usingSSLConnection()) {
                    $protocol = 'https';
                }
            }

            /* If using non-standard ports, add the port to the URL. */
            if ((($protocol == 'http') && ($server_port != 80)) ||
                (($protocol == 'https') && ($server_port != 443))) {
                $server_name .= ':' . $server_port;
            }

            /* Store the webroot in a local variable. */
            $webroot = $registry->getParam('webroot');

            $url = $protocol . '://' . $server_name;
            if (substr($uri, 0, 1) != '/') {
                if (substr($webroot, -1) == '/') {
                    $url .= $webroot . $uri;
                } else {
                    $url .= $webroot . '/' . $uri;
                }
            } else {
                $url .= $uri;
            }
        } else {
            $url = $uri;
        }

        if ($always_append_session ||
            !array_key_exists(session_name(), $_COOKIE)) {
            $url = Horde::addParameter($url, urlencode(session_name()) . '=' . session_id());
        }

        return ($full ? $url : htmlentities($url));
    }

    /**
     * Add a name=value pair to an URL, taking care of whether there
     * are existing parameters and whether to use ? or & as the glue.
     *
     * @access public
     *
     * @param string $url       The URL to modify
     * @param string $parameter The name=value pair to add.
     *
     * @return string The modified URL.
     *
     * @since Horde 2.1
     */
    function addParameter($url, $parameter)
    {
        if (!empty($parameter) && strstr($url, $parameter) === false) {
            if (substr($parameter, 0, 1) == '?') {
                $parameter = substr($parameter, 1);
            }

            $pos = strpos($url, '?');
            if ($pos !== false) {
                $url = substr_replace($url, $parameter . ini_get('arg_separator.output'),
                                      $pos + 1, 0);
            } else {
                $url .= '?' . $parameter;
            }
        }
        return $url;
    }

    /**
     * Removes a name=value pair from a URL.
     *
     * @access public
     *
     * @param string $url       The URL to modify.
     * @param array $parameter  The array of parameters to remove.
     *
     * @return string  The modified URL.
     *
     * @since Horde 2.2
     */
    function removeParameter($url, $parameter = array())
    {
        foreach ($parameter as $value) {
            $url = preg_replace("/" . $value . "\=\w+\&?(?:amp;)?/", '', $url);
        }

        /* If there are no more parameters left, or the last parameter was
           removed, remove the trailing '?' or '&'. */
        return rtrim($url, '&?');
    }

    /**
     * Return a session-id-ified version of $uri, using the current
     * application's webroot setting.
     *
     * @access public
     *
     * @param string  $uri                   The URI to be modified.
     * @param boolean $full                  Generate a full
     *                                       (http://server/path/) URL.
     * @param boolean $always_append_session Tack on the session ID even if
     *                                       cookies are present.
     *
     * @return string The url with the session id appended
     */
    function applicationUrl($uri, $full = false, $always_append_session = false)
    {
        global $registry;

        /* Store the webroot in a local variable. */
        $webroot = $registry->getParam('webroot');

        if ($full) {
            return Horde::url($uri, $full, $always_append_session);
        } elseif (substr($webroot, -1) == '/') {
            return Horde::url($webroot . $uri, $full, $always_append_session);
        } else {
            return Horde::url($webroot . '/' . $uri, $full, $always_append_session);
        }
    }

    /**
     * Return an anchor tag with the relevant parameters
     *
     * @access public
     *
     * @param string $url     The full URL to be linked to
     * @param string $status  An optional JavaScript mouse-over string
     * @param string $class   The CSS class of the link
     * @param string $target  The window target to point this link too
     * @param string $onclick JavaScript action for the 'onclick' event.
     * @param string $title   The link title (tooltip)
     *
     * @return string The full <a href> tag.
     */
    function link($url, $status = '', $class = '', $target = '', $onclick = '', $title = '')
    {
        $ret = "<a href=\"$url\"";
        if (!empty($onclick)) {
            $ret .= " onclick=\"$onclick\"";
        }
        if (!empty($status)) {
            $ret .= ' onmouseout="window.status=\'\';" onmouseover="window.status=\'' .
                @htmlspecialchars(addslashes($status), ENT_QUOTES, Lang::getCharset()) . '\'; return true;"';
        }
        if (!empty($class)) {
            $ret .= " class=\"$class\"";
        }
        if (!empty($target)) {
            $ret .= " target=\"$target\"";
        }
        if (!empty($title)) {
            $ret .= ' title="' . @htmlspecialchars($title, ENT_QUOTES, Lang::getCharset()) . '"';
        }

        return "$ret>";
    }

    /**
     * Print an anchor tag with the relevant parameters
     *
     * @access public
     *
     * @param string $url     The full URL to be linked to
     * @param string $status  An optional JavaScript mouse-over string
     * @param string $class   The CSS class of the link
     * @param string $target  The window target to point this link too
     * @param string $onclick JavaScript action for the 'onclick' event.
     * @param string $title   The link title (tooltip)
     */
    function plink($url, $status = '', $class = '', $target = '', $onclick = '', $title = '')
    {
       echo Horde::link($url, $status, $class, $target, $onclick, $title);
    }

    /**
     * Print a session-id-ified version of the URI.
     *
     * @access public
     *
     * @param string  $uri                   the URI to be modified
     * @param boolean $full                  Generate a full
     *                                       (http://server/path/) URL.
     * @param boolean $always_append_session Tack on the session ID even if
     *                                       cookies are present.
     */
    function purl($uri, $full = false, $always_append_session = false)
    {
        echo Horde::url($uri, $full, $always_append_session);
    }

    /**
     * Return a session-id-ified version of $PHP_SELF.
     *
     * @access public
     *
     * @param string $query_string (optional) include the query string?
     */
    function selfURL($query_string = false)
    {
        $url = $_SERVER['PHP_SELF'];

        if ($query_string && !empty($_SERVER['QUERY_STRING'])) {
            $url .= '?' . $_SERVER['QUERY_STRING'];
        }

        return Horde::url($url);
    }

    /**
     * Print a session-id-ified version of $PHP_SELF.
     *
     * @access public
     *
     * @param $query_string (optional) include the query string?
     */
    function pselfURL($query_string = false)
    {
        echo Horde::selfURL($query_string);
    }

    /**
     * Return a hidden form input containing the session name and id.
     *
     * @access public
     */
    function formInput()
    {
        return '<input type="hidden" name="' . session_name() . '" value="' . session_id() . '" />';
    }

    /**
     * Print a hidden form input containing the session name and id.
     *
     * @access public
     */
    function pformInput()
    {
        echo Horde::formInput();
    }

    /**
     * Construct a correctly-pathed link to an image
     *
     * @access public
     *
     * @param          string $src  The image file.
     * @param optional string $attr Any additional attributes for the image tag.
     * @param optional string $dir  The root graphics directory.
     *
     * @return string The full image tag.
     */
    function img($src, $attr = '', $dir = null)
    {
        /* If no directory has been specified, get it from the registry. */
        if ($dir === null) {
            global $registry;
            $dir = $registry->getParam('graphics');
        }

        $img = '<img';
        $img .= ' src="' . (empty($dir) ? '' : $dir . '/') . $src . '"';
        $img .= ' border="0"';
        if (!empty($attr)) {
            $img .= ' ' . $attr;
            if (preg_match('/alt=([\'"])([^\1]*)\1/i', $attr, $match)) {
                $img .= ' title="'. $match[2] . '"';
            }
        }
        if (empty($attr) || !strstr($attr, 'alt')) {
            $img .= ' alt=""';
        }
        $img .= ' />';

        return $img;
    }

    /**
     * Construct a correctly-pathed link to an image
     *
     * @access public
     *
     * @param          string $src  The image file.
     * @param optional string $attr Any additional attributes for the image tag.
     * @param optional string $dir  The root graphics directory.
     */
    function pimg($src, $attr = null, $dir = null)
    {
        echo Horde::img($src, $attr, $dir);
    }

    /**
     * If magic_quotes_gpc is in use, run stripslashes() on $var.
     *
     * @access public
     *
     * @param  string $var  The string to un-quote, if necessary.
     *
     * @return string       $var, minus any magic quotes.
     */
    function dispelMagicQuotes(&$var)
    {
        static $magic_quotes;

        if (!isset($magic_quotes)) {
            $magic_quotes = get_magic_quotes_gpc();
        }

        if ($magic_quotes) {
            if (!is_array($var)) {
                $var = stripslashes($var);
            } else {
                array_walk($var, array('Horde', 'dispelMagicQuotes'));
            }
        }

        return $var;
    }

    /**
     * Get a form variable from GET or POST data, stripped of magic
     * quotes if necessary. If the variable is somehow set in both the
     * GET data and the POST data, the value from the POST data will
     * be returned and the GET value will be ignored.
     *
     * @access public
     *
     * @param string $var       The name of the form variable to look for.
     * @param string $default   (optional) The value to return if the
     *                          variable is not there.
     *
     * @return string     The cleaned form variable, or $default.
     */
    function getFormData($var, $default = null)
    {
        return ($val = Horde::getPost($var)) !== null
            ? $val : Horde::getGet($var, $default);
    }

    /**
     * Get a form variable from GET data, stripped of magic quotes if
     * necessary. This function will NOT return a POST variable.
     *
     * @access public
     *
     * @param string $var       The name of the form variable to look for.
     * @param string $default   (optional) The value to return if the
     *                          variable is not there.
     *
     * @return string     The cleaned form variable, or $default.
     *
     * @since Horde 2.2
     */
    function getGet($var, $default = null)
    {
        return (array_key_exists($var, $_GET))
            ? Horde::dispelMagicQuotes($_GET[$var])
            : $default;
    }

    /**
     * Get a form variable from POST data, stripped of magic quotes if
     * necessary. This function will NOT return a GET variable.
     *
     * @access public
     *
     * @param string $var       The name of the form variable to look for.
     * @param string $default   (optional) The value to return if the
     *                          variable is not there.
     *
     * @return string     The cleaned form variable, or $default.
     *
     * @since Horde 2.2
     */
    function getPost($var, $default = null)
    {
        return (array_key_exists($var, $_POST))
            ? Horde::dispelMagicQuotes($_POST[$var])
            : $default;
    }

    /**
     * Determine the location of the system temporary directory.
     * If a specific setting cannot be found, it defaults to /tmp
     *
     * @access public
     *
     * @return string   A directory name which can be used for temp files.
     *                  Returns false if one could not be found.
     */
    function getTempDir()
    {
        $tmp_locations = array('/tmp', '/var/tmp', 'c:\temp', 'c:\windows\temp', 'c:\winnt\temp');

        /* If one has been specifically set, then use that */
        if (!empty($GLOBALS['conf']['tmpdir'])) {
            $tmp = $GLOBALS['conf']['tmpdir'];
        }

        /* Next, try PHP's upload_tmp_dir directive. */
        if (empty($tmp)) {
            $tmp = ini_get('upload_tmp_dir');
        }

        /* Otherwise, try to determine the TMPDIR environment
           variable. */
        if (empty($tmp)) {
            $tmp = getenv('TMPDIR');
        }

        /* If we still cannot determine a value, then cycle through a
         * list of preset possibilities. */
        while (empty($tmp) && sizeof($tmp_locations)) {
            $tmp_check = array_shift($tmp_locations);
            if (@is_dir($tmp_check)) {
                $tmp = $tmp_check;
            }
        }

        /* If it is still empty, we have failed, so return false;
         * otherwise return the directory determined. */
        return empty($tmp) ? false : $tmp;
    }

    /**
     * Create a temporary filename for the lifetime of the script, and
     * (optionally) register it to be deleted at request shutdown.
     *
     * @access public
     *
     * @param string $prefix            Prefix to make the temporary name more
     *                                  recognizable.
     * @param optional boolean $delete  Delete the file at the end of the
     *                                  request?
     * @param optional string $dir      Directory to create the temporary file
     *                                  in.
     *
     * @return string   Returns the full path-name to the temporary file.
     *                  Returns false if a temp file could not be created.
     */
    function getTempFile($prefix = 'Horde', $delete = true, $dir = false)
    {
        if (!$dir || !is_dir($dir)) {
            $tmp_dir = Horde::getTempDir();
        } else {
            $tmp_dir = $dir;
        }

        if (empty($tmp_dir)) {
            return false;
        }

        $tmp_file = tempnam($tmp_dir, $prefix);

        /* If the file was created, then register it for deletion and return */
        if (empty($tmp_file)) {
            return false;
        } else {
            if ($delete) {
                _fileCleanup($tmp_file);
            }
            return $tmp_file;
        }
    }

    /**
     * Create a temporary directory in the system's temporary directory.
     *
     * @access public
     *
     * @param optional boolean $delete  Delete the temporary directory at the
     *                                  end of the request?
     *
     * @return string       The pathname to the new temporary directory.
     *                      Returns false if directory not created.
     *
     * @since Horde 2.2
     */
    function createTempDir($delete = true)
    {
        $temp_dir = Horde::getTempDir();
        if (empty($temp_dir)) return false;

        /* Get the first 8 characters of a random string to use as a temporary
           directory name. */
        do {
            $temp_dir .= '/' . substr(md5(uniqid(rand())), 0, 8);
        } while (file_exists($temp_dir));

        $old_umask = umask(0000);
        if (!mkdir($temp_dir, 0700)) {
            $temp_dir = false;
        } else {
            if ($delete) {
                _fileCleanup($temp_dir);
            }
        }
        umask($old_umask);

        return $temp_dir;
    }

    /**
     * Determine if we are using a Secure (SSL) connection.
     *
     * @access public
     *
     * @return boolean      True if using SSL, false if not.
     */
    function usingSSLConnection()
    {
        return ((array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') ||
                getenv('SSL_PROTOCOL_VERSION'));
    }

    /**
     * Start output compression, if requested.
     *
     * @access public
     *
     * @since Horde 2.2
     */
    function compressOutput()
    {
        static $started;

        if (isset($started)) {
            return;
        }

        global $browser, $conf;

        /* Netscape =< 4 is so buggy with compression that we just turn it
           completely off for those browsers. */
        if ($conf['compress_pages'] &&
            (ini_get('zlib.output_compression') != 1) &&
            (($browser->getBrowser() != 'mozilla') ||
             ($browser->getMajor() > 4))) {
            /* Compress output if requested. */
            ob_start('ob_gzhandler');
        }

        $started = true;
    }

    /**
     * Destroy any existing session on login and make sure to use a
     * new session ID, to avoid session fixation issues. Should be
     * called before checking a login.
     */
    function getCleanSession()
    {
        Horde::logMessage("clean session", __FILE__, __LINE__);
        Auth::clearAuth();
        @session_destroy();

        // Make sure to force a completely new session ID.
        if (version_compare(phpversion(), '4.3.3') !== -1) {
            session_regenerate_id();
        } else {
            if (function_exists('posix_getpid')) {
                $new_session_id = md5(microtime() . posix_getpid());
            } else {
                $new_session_id = md5(uniqid(mt_rand(), true));
            }
            session_id($new_session_id);
        }

        // Restart the session, including setting up the session
        // handler.
        Horde::setupSessionHandler();
        @session_start();
    }

    /**
     * If there is a custom session handler, set it up now.
     *
     * @access public
     *
     * @since Horde 2.2.4
     */
    function setupSessionHandler()
    {
        global $conf, $registry;

        ini_set('session.use_trans_sid', 0);
        session_set_cookie_params($conf['session_timeout'], $registry->getParam('cookie_path', 'horde'), $registry->getParam('cookie_domain', 'horde'));
        session_name(urlencode($conf['session_name']));
        session_cache_limiter($conf['cache_limiter']);

        $type = !empty($conf['sessionhandler']['type']) ? $conf['sessionhandler']['type'] : 'none';

        if ($type == 'external') {
            $calls = $conf['sessionhandler']['params'];
            session_set_save_handler($calls['open'],
                                     $calls['close'],
                                     $calls['read'],
                                     $calls['write'],
                                     $calls['destroy'],
                                     $calls['gc']);
        } elseif ($type != 'none') {
            global $_session_handler;
            require_once HORDE_BASE . '/lib/SessionHandler.php';
            $_session_handler = &SessionHandler::singleton($conf['sessionhandler']['type']);
            if (!empty($_session_handler) &&
                !PEAR::isError($_session_handler)) {
                ini_set('session.save_handler', 'user');
                session_set_save_handler(array($_session_handler, 'open'),
                                         array($_session_handler, 'close'),
                                         array($_session_handler, 'read'),
                                         array($_session_handler, 'write'),
                                         array($_session_handler, 'destroy'),
                                         array($_session_handler, 'gc'));
            } else {
                Horde::fatal(PEAR::raiseError('Horde is unable to correctly start the custom session handler.'), __FILE__, __LINE__, false);
            }
        }
    }

}
