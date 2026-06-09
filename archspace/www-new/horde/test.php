<?php
/**
 * $Horde: horde/test.php,v 1.44.2.23 2003/07/10 20:20:49 slusarz Exp $
 *
 * Copyright 2002-2003 Brent J. Nordquist <bjn@horde.org>
 * Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

@session_start();
/* Register a session. */
if (!isset($_SESSION['horde_test_count'])) {
    $horde_test_count = 0;
    session_register('horde_test_count');
}

$horde_test_count = &$_SESSION['horde_test_count'];

/* We want to be as verbose as possible here. */
error_reporting(E_ALL);

/* Set character encoding. */
header('Content-type: text/html; charset=utf-8');
header('Vary: Accept-Language');

function testErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
    global $pear, $newpear, $pearmail, $pearlog, $peardb,
           $pearsocket, $peardate, $pearhtml, $unkerr;
    if (preg_match('/PEAR\.php/', $errmsg)) {
        $pear = false;
    } elseif (preg_match('/RFC822\.php/', $errmsg)) {
        $pearmail = false;
    } elseif (preg_match('/Log\.php/', $errmsg)) {
        $pearlog = false;
    } elseif (preg_match('/DB\.php/', $errmsg)) {
        $peardb = false;
    } elseif (preg_match('/Socket\.php/', $errmsg)) {
        $pearsocket = false;
    } elseif (preg_match('/Calc\.php/', $errmsg)) {
        $peardate = false;
    } elseif (preg_match('/Common\.php/', $errmsg) || preg_match('/Select\.php/', $errmsg)) {
        $pearhtml = false;
    } else {
        $unkerr = "$errmsg ($filename:$linenum)";
    }
}

function status($foo) {
    if ($foo) {
        echo '<font color="green"><b>Yes</b></font>';
    } else {
        echo '<font color="red"><b>No</b></font>';
    }
}

/* If gettext is not loaded, define a dummy _() function so that
 * including registry.php (which contains gettext strings) won't cause
 * a fatal error, causing test.php to return a blank page. */
if (!function_exists('_')) {
    function _($s)
    {
        return $s;
    }
}

/* Horde versions */
$versions = array();
$testphp = array();
require_once './lib/version.php';
$versions['horde'] = HORDE_VERSION;

/* Application versions */
$this->applications = array();
@include_once './config/registry.php';
foreach ($this->applications as $module => $details) {
    if (is_readable($details['fileroot'] . '/lib/version.php')) {
        include_once $details['fileroot'] . '/lib/version.php';
        eval('$defined = defined(\'' . strtoupper($module) . '_VERSION\');');
        if ($defined) {
            eval('$ver = ' . strtoupper($module) . '_VERSION;');
            $versions[$module] = $ver;
            if ($module != 'horde' && @is_readable($details['fileroot'] . '/test.php')) {
                $testphp[$module] = $details['webroot'] . '/test.php';
            }
        }
    }
}

/* Parse PHP version */
function split_php_version($version)
{
    // First pick off major version, and lower-case the rest.
    if (strlen($version) >= 3 && $version[1] == '.') {
        $phpver['major'] = substr($version, 0, 3);
        $version = substr(strtolower($version), 3);
    } else {
        $phpver['major'] = $version;
        $phpver['class'] = 'unknown';
        return $phpver;
    }
    if ($version[0] == '.') {
        $version = substr($version, 1);
    }
    // Next, determine if this is 4.0b or 4.0rc; if so, there is no minor,
    // the rest is the subminor, and class is set to beta.
    $s = strspn($version, '0123456789');
    if ($s == 0) {
        $phpver['subminor'] = $version;
        $phpver['class'] = 'beta';
        return $phpver;
    }
    // Otherwise, this is non-beta;  the numeric part is the minor,
    // the rest is either a classification (dev, cvs) or a subminor
    // version (rc<x>, pl<x>).
    $phpver['minor'] = substr($version, 0, $s);
    if ((strlen($version) > $s) && ($version[$s] == '.' || $version[$s] == '-')) {
        $s++;
    }
    $phpver['subminor'] = substr($version, $s);
    if ($phpver['subminor'] == 'cvs' || $phpver['subminor'] == 'dev' || substr($phpver['subminor'], 0, 2) == 'rc') {
        unset($phpver['subminor']);
        $phpver['class'] = 'dev';
    } else {
        if (!$phpver['subminor']) {
            unset($phpver['subminor']);
        }
        $phpver['class'] = 'release';
    }
    return $phpver;
}

/* Display PHP version bullets */
function show_php_version($phpver)
{
    echo '    <li>PHP Major Version: ' . $phpver['major'] . "</li>\n";
    if (isset($phpver['minor'])) {
        echo '    <li>PHP Minor Version: ' . $phpver['minor'] . "</li>\n";
    }
    if (isset($phpver['subminor'])) {
        echo '    <li>PHP Subminor Version: ' . $phpver['subminor'] . "</li>\n";
    }
    echo '    <li>PHP Version Classification: ' . $phpver['class'] . "</li>\n";
}

/* PHP version-parsing regression test; early PHP version formats were only */
/* roughly consistent, thus the need to test a wide range. Lately they've */
/* been better. */
if (false) {
    $phpversions = array('4.0B1', '4.0B2-1', '4.0B2', '4.0B3-RC2', '4.0b3-RC3', '4.0b3-RC4', '4.0b3-RC5', '4.0b3', '4.0b4-rc1', '4.0b4', '4.0b4pl1', '4.0RC1', '4.0RC2', '4.0.0', '4.0.1', '4.0.2-dev', '4.0.2', '4.0.3RC1', '4.0.3RC2', '4.0.3', '4.0.3pl1', '4.0.4RC3', '4.0.4RC5', '4.0.4RC6', '4.0.4', '4.0.4pl1-RC1', '4.0.4pl1', '4.0.5RC1', '4.0.5-dev', '4.0.6RC1', '4.0.6', '4.0.7RC1', '4.0.7', '4.1.0RC1', '4.1.0');
    foreach ($phpversions as $version) {
        echo "    <li>PHP Version: $version</li>\n";
        $phpver = split_php_version($version);
        show_php_version($phpver);
        echo '<br/>';
    }
}

/* PHP Version */
$phpver = split_php_version(phpversion());

/* PHP module capabilities */
$ftp = extension_loaded('ftp');
$gettext = extension_loaded('gettext');
$imap = extension_loaded('imap');
$ldap = extension_loaded('ldap');
$mcal = extension_loaded('mcal');
$mcrypt = extension_loaded('mcrypt');
$mysql = extension_loaded('mysql');
$pgsql = extension_loaded('pgsql');
$xml = extension_loaded('xml');
$domxml = extension_loaded('domxml');

/* PHP Settings */
$magic_quotes_runtime = !get_magic_quotes_runtime();
$file_uploads = ini_get('file_uploads');
$safe_mode = !ini_get('safe_mode');
$trans_sid = !ini_get('session.use_trans_sid');

/* PEAR */
$pear = true;
$pearmail = true;
$pearlog = true;
$peardb = true;
$pearsocket = true;
$peardate = true;
$pearhtml = true;
$unkerr = '';
set_error_handler('testErrorHandler');
include 'PEAR.php';
include 'Mail/RFC822.php';
include 'Log.php';
include 'DB.php';
include 'Net/Socket.php';
include 'Date/Calc.php';
include 'HTML/Common.php';
if ($pearhtml) {
    include 'HTML/Select.php';
}
restore_error_handler();

/* Check the version of the pear database API. */
if ($peardb) {
    $peardbversion = '0';
    $peardbversion = @DB::apiVersion();
    if ($peardbversion < 2) {
        $peardb = false;
    }
}

/* Test for existence of PEAR::registerShutdownFunc() */
$pear_methods = get_class_methods('PEAR');
$newpear = is_array($pear_methods) && in_array('registershutdownfunc', $pear_methods);

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">';

/* Handle special modes */
if (isset($_GET['mode'])) {
    switch ($_GET['mode']) {
    case 'phpinfo':
        phpinfo();
        exit;
        break;

    case 'unregister':
        $_SESSION['horde_test_count'] = null;
        session_unregister('horde_test_count');
        ?>
        <html>
        <body bgcolor="white" text="black">
        <font face="Helvetica, Arial, sans-serif" size="2">
        The test session has been unregistered.<br>
        <a href="test.php">Go back</a> to the test.php page.<br>
        <?php
        exit;
        break;

    default:
        break;
    }
} else {
?>

<html>
<head>
<title>Horde: System Capabilities Test</title>
<style type="text/css">
<!--
body { font-family: Geneva,Arial,Helvetica,sans-serif; font-size: 10pt; }
td { font-family: Geneva,Arial,Helvetica,sans-serif; font-size: 10pt; }
h1 { font-size: 12pt; color: black; font-family: Verdana,Geneva,Arial,Helvetica,sans-serif; }
-->
</style>
</head>

<body bgcolor="#ffffff" text="#000000">

<table border="0" cellpadding="2" cellspacing="0" width="100%">
<tr><td>

<h1>Horde Versions</h1>
<ul>
<?php
foreach ($versions as $module => $ver) {
    if (!empty($testphp[$module])) {
        $testpath = $testphp[$module];
    } else {
        $testpath = '';
    }
    $module = ucfirst($module);
    if ($module == 'Imp')
        $module = 'IMP';
    if ($testpath) {
        echo "<li>$module: $ver (<a href=\"$testpath\">run $module tests</a>)</li>\n";
    } else {
        echo "<li>$module: $ver</li>\n";
    }
}
?>
</ul>

<h1>PHP Version</h1>
<ul>
    <li><a href="test.php?mode=phpinfo">View phpinfo() screen</a></li>
    <li>PHP Version: <?php echo phpversion(); ?></li>
<?php
    show_php_version($phpver);
    if ($phpver['major'] < '4.0') {
        echo '        <li><font color="red">You need to upgrade to PHP4. PHP3 will not work.</font></li>';
        $requires = 1;
    } elseif ($phpver['class'] == 'beta' || $phpver['class'] == 'unknown') {
        echo '        <li><font color="red">This is a beta/prerelease version of PHP4. You need to upgrade to a release version.</font></li>';
        $requires = 1;
    } elseif ($phpver['major'] == '4.0') {
        echo '        <li><font color="red">This version of PHP is not supported. You need to upgrade to a more recent version.</font></li>';
        $requires = 1;
    } elseif ($phpver['major'] == '4.1' || $phpver['major'] == '4.2' || $phpver['major'] == '4.3' || $phpver['major'] == '4.4') {
        if ($phpver['major'] == '4.1' && $phpver['minor'] < '2') {
            $insecure = 1;
        }
        echo '        <li><font color="green">You are running a supported version of PHP.</font></li>';
    } else {
        echo '        <li><font color="orange">Wow, a mystical version of PHP from the future. Let <a href="mailto:dev@lists.horde.org">dev@lists.horde.org</a> know what version you have so we can fix this script.</font></li>';
    }
    if (!empty($requires)) {
        echo '        <li>Horde requires PHP 4.1.0.</li>';
    }
    if (!empty($insecure)) {
        echo '        <li><font color="orange">This version of PHP contains a serious security vulnerability in its upload code.</font> You should apply the patch or upgrade to 4.1.2 or later as soon as possible.</li>';
    }
    echo '</ul>';
?>

<h1>PHP Module Capabilities</h1>
<ul>
    <li>DOM XML Support: <?php status($domxml); ?></li>
    <li>FTP Support: <?php status($ftp); ?></li>
    <li>Gettext Support: <?php status($gettext); ?></li>
    <?php if (!$gettext) { ?>
    <li><font color="red"><b>Horde will not run without gettext support. Compile php <code>--with-gettext</code> before continuing.</b></font></li>
    <?php exit; } ?>
    <li>IMAP Support: <?php status($imap) ?></li>
    <li>LDAP Support: <?php status($ldap); ?></li>
    <li>MCAL Support: <?php status($mcal); ?></li>
    <li>Mcrypt Support: <?php status($mcrypt); ?></li>
    <li>MySQL Support: <?php status($mysql); ?></li>
    <li>PostgreSQL Support: <?php status($pgsql); ?></li>
    <li>XML Support: <?php status($xml); ?></li>
</ul>

<h1>Miscellaneous PHP Settings</h1>
<ul>
    <li>magic_quotes_runtime disabled: <?php echo status($magic_quotes_runtime); ?></li>
    <?php if (!$magic_quotes_runtime) { ?>
    <li><font color="red"><b>magic_quotes_runtime may cause problems with database inserts, etc. Turn it off.</b></font></li>
    <?php } ?>
    <li>file_uploads enabled: <?php echo status($file_uploads) ?></li>
    <?php if (!$file_uploads) { ?>
    <li><font color="orange"><b>file_uploads must be enabled for some features like sending emails with IMP.</b></font></li>
    <?php } ?>
    <li>safe_mode disabled: <?php echo status($safe_mode) ?></li>
    <?php if (!$safe_mode) { ?>
    <li><font color="orange"><b>If safe_mode is enabled, Horde cannot set enviroment variables, which means Horde will be unable to translate the user interface into different languages.</b></font></li>
    <?php } ?>
    <li>trans_sid disabled: <?php echo status($trans_sid) ?></li>
    <?php if (!$trans_sid) { ?>
    <li><font color="orange"><b>Horde will work with session.trans_sid turned on, but you may see double session-ids in your URLs, and if the session name in php.ini differs from the session name configured in Horde, you may get two session ids and see other odd behavior. The URL-rewriting that trans_sid does also tends to break XHTML compliance.</b></font></li>
    <?php } ?>
</ul>

<h1>PHP Sessions</h1>
<?php $horde_test_count++; ?>
<ul>
    <li>Session counter: <?php echo $horde_test_count; ?></li>
    <li>To unregister the session: <a href="test.php?mode=unregister">click here</a></li>
</ul>

<h1>PEAR</h1>
<ul>
    <li>PEAR - <?php status($pear); ?></li>
    <?php if (!$pear) { ?>
        <li><font color="red">Check your PHP include_path setting to make sure it has the PEAR library directory.</font></li>
    <?php } ?>
    <li>Recent PEAR - <?php status($newpear); ?></li>
    <?php if ($pear && !$newpear) { ?>
        <li><font color="red">This version of PEAR is not recent enough. See the <a href="http://www.horde.org/pear/">Horde PEAR page</a> for details.</font></li>
    <?php } ?>
    <li>Mail - <?php status($pearmail); ?></li>
    <?php if ($pear && !$pearmail) { ?>
        <li><font color="red">Make sure you are using a recent version of PEAR which includes the Mail_RFC822 class.</font></li>
    <?php } ?>
    <li>Log - <?php status($pearlog); ?></li>
    <?php if ($pear && !$pearlog) { ?>
        <li><font color="red">Make sure you are using a version of PEAR which includes the Log classes, or that you have installed the Log package seperately. See the INSTALL file for instructions on installing Log.</font></li>
    <?php } ?>
    <li>DB - <?php status($peardb); ?></li>
    <?php if ($pear && !$peardb) {
              if ($peardbversion) { ?>
                  <li><font color="red">Your version of DB is not recent enough.</font></li>
              <?php } else { ?>
                  <li><font color="red">You will need DB if you're using SQL drivers for preferences, contacts (Turba), etc.</font></li>
              <?php }
          } ?>
    <li>Net_Socket - <?php status($pearsocket); ?></li>
    <?php if ($pear && !$pearsocket) { ?>
        <li><font color="red">Make sure you are using a version of PEAR which includes the Net_Socket class, or that you have installed the Net_Socket package seperately. See the INSTALL file for instructions on installing Net_Socket.</font></li>
    <?php } ?>
    <li>Date - <?php status($peardate); ?></li>
    <?php if ($pear && !$peardate) { ?>
        <li><font color="red">Horde requires the Date_Calc class for Kronolith to calculate dates.</font></li>
    <?php } ?>
    <li>HTML_Common/HTML_Select - <?php status($pearhtml); ?></li>
    <?php if ($pear && !$pearhtml) { ?>
        <li><font color="red">Horde requires the HTML_Common and HTML_Select classes only for Kronolith 1.0 to display forms correctly.</font></li>
    <?php } ?>
    <?php if ($unkerr) { ?>
        <li><font color="red">Unknown error:</font> <?php echo $unkerr; ?></li>
    <?php } ?>
</ul>

<p align="left">
<a href="http://validator.w3.org/check/referer"><img src="http://validator.w3.org/images/vxhtml10" alt="Valid XHTML 1.0!" height="31" width="88" border="0" hspace="5" /></a>
</p>

</td></tr>
</table>

<?php } ?>

</body>
</html>
