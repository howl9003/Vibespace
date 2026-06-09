<?php
/*
 * $Horde: horde/css.php,v 1.7.2.11 2003/02/06 00:49:24 jan Exp $
 *
 * Copyright 2000-2003 Charles J. Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */


@define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/Horde.php';
require_once HORDE_BASE . '/lib/Registry.php';
require_once HORDE_BASE . '/lib/Browser.php';

$registry = &Registry::singleton();
$browser = new Browser();

// Figure out if we've been inlined, or called directly.
$send_headers = strstr($_SERVER['PHP_SELF'], 'css.php');

// Set initial $mtime of this script.
$mtime = getlastmod();

if (@file_exists(HORDE_BASE . '/config/horde.php')) {
    include HORDE_BASE . '/config/horde.php';
} else {
    $conf['css']['cached'] = false;
}


if (Horde::getFormData('inherit') !== 'no') {
    if (@file_exists(HORDE_BASE . '/config/html.php')) {
        if ($conf['css']['cached']) {
            $hmtime = filemtime(HORDE_BASE . '/config/html.php');
            if ($hmtime > $mtime) {
                $mtime = $hmtime;
            }
        }
        include HORDE_BASE . '/config/html.php';
    } else {
        @include HORDE_BASE . '/config/html.php.dist';
    }
}

$app = Horde::getFormData('app');
if (!empty($app)) {
    $conf_file = $registry->applicationFilePath('%application%/config/conf.php', $app);
    if (@file_exists($conf_file)) {
        include $conf_file;
    }

    $css_file = $registry->applicationFilePath('%application%/config/html.php', $app);
    if (@file_exists($css_file)) {
        if ($conf['css']['cached']) {
            $amtime = filemtime($css_file);
            if ($amtime > $mtime) {
                $mtime = $amtime;
            }
        }
        include $css_file;
    }
}

if ($send_headers) {
    if ($conf['css']['cached']) {
        $mod_gmt = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
        header('Last-Modified: ' . $mod_gmt);
        header('Cache-Control: public, max-age=86400');
    } else {
        header('Expires: -1');
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }
    header('Content-Type: text/css; charset=iso-8859-1');
}

if (is_array($css)) {
    foreach ($css as $class => $params) {
        echo "$class{";
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                echo "$key:$val;";
            }
        }
        echo '} ';
    }
}
