<?php
/**
 * $Horde: horde/help.php,v 2.41.2.10 2003/01/03 12:57:44 jan Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/base.php';
require_once HORDE_BASE . '/lib/Help.php';

$title = _("Help");
$js_onLoad = null;
$show = strtolower(Horde::getFormData('show', 'index'));
$module = strtolower(basename(Horde::getFormData('module', 'horde')));
$topic = Horde::getFormData('topic');

$help_file          = $registry->getParam('fileroot', $module) . "/locale/$language/help.xml";
$help_file_fallback = $registry->getParam('fileroot', $module) . "/locale/en_US/help.xml";

if ($show == 'index') {
    include HORDE_TEMPLATES . '/help/index.inc';
} else {
    include HORDE_TEMPLATES . '/common-header.inc';
    if ($show == 'menu') {
        include HORDE_TEMPLATES . '/help/menu.inc';
    } else {
        include HORDE_TEMPLATES . '/help/header.inc';

        $help = new Help(HELP_SOURCE_FILE, array($help_file, $help_file_fallback));
        if (($show == 'entry') && !empty($topic)) {
            $help->lookup($topic);
            $help->display();
        } else {
            $topics = $help->topics();
            foreach ($topics as $id => $title) {
                $link = Horde::url($registry->getParam('webroot', 'horde') . '/help.php');
                $link = Horde::addParameter($link, 'show=entry');
                $link = Horde::addParameter($link, 'module=' . $module);
                $link = Horde::addParameter($link, 'topic=' . $id);
                Horde::plink($link, '', 'helpitem');
                echo $title . "</a><br />\n";
            }
        }
        $help->cleanup();

        include HORDE_TEMPLATES . '/help/footer.inc';
    }
}

require HORDE_TEMPLATES . '/common-footer.inc';
