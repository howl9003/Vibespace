<?php
/**
 * $Horde: horde/javascript.php,v 1.1.2.9 2003/01/16 17:02:32 chuck Exp $
 *
 * Copyright 2000-2003 Charles J. Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

@define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/Horde.php';
require_once HORDE_BASE . '/lib/Registry.php';

$registry = &Registry::singleton();

// Figure out if we've been inlined, or called directly.
$send_headers = strstr($_SERVER['PHP_SELF'], 'javascript.php');

$app = Horde::getFormData('app');
$file = basename(Horde::getFormData('file'));
if (!empty($app) && !empty($file)) {
    $script_file = $registry->getParam('templates', $app) . '/javascript/' . $file;
    if (@file_exists($script_file)) {
        @session_cache_limiter('public, max-age=86400');
        $registry->pushApp($app);

        ob_start();
        require $script_file;
        $script = ob_get_contents();
        ob_end_clean();

        if ($send_headers) {
            $mod_gmt = gmdate('D, d M Y H:i:s', filemtime($script_file)) . ' GMT';
            $mod_exp = gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT';
            header('Pragma: public');
            header('Expires: ' . $mod_exp);
            header('Last-Modified: ' . $mod_gmt);
            header('Cache-Control: public, max-age=86400');
            header('Content-Type: text/javascript');
            header('Content-Length: ' . strlen($script));
        }

        echo $script;
    }
}
