#!/usr/bin/php -q
<?php
/**
 * Translation helper application for the Horde framework.
 *
 * For usage information call it like:
 * ./translation.php help
 *
 * $Horde: horde/po/translation.php,v 1.27.2.10 2003/08/31 17:52:50 jan Exp $
 */

function writeln($text = '', $pre = false)
{
    if ($pre) {
        print "\n$text";
    } else {
        print "$text\n";
    }
}

function bold($text)
{
    return $GLOBALS['bold'] . $text . $GLOBALS['normal'];
}

function red($text)
{
    return $GLOBALS['red'] . $text . $GLOBALS['normal'];
}

function green($text)
{
    return $GLOBALS['green'] . $text . $GLOBALS['normal'];
}

function footer()
{
    writeln();
    writeln(_("Please report any bugs to i18n@lists.horde.org."));
    exit;
}

function usage()
{
    global $options;

    if (count($options[1]) &&
        ($options[1][0] == 'help' && !empty($options[1][1]) ||
        !empty($options[1][0]) && in_array($options[1][0], array('commit', 'compendium', 'extract', 'init', 'make', 'merge')))) {
        if ($options[1][0] == 'help') {
            $cmd = $options[1][1];
        } else {
            $cmd = $options[1][0];
        }
        writeln(_("Usage:") . ' translation.php [options] ' . $cmd . ' [command-options]');
        if (!empty($cmd)) {
            writeln();
            writeln(_("Command options:"));
        }
        switch ($cmd) {
            case 'commit':
            case 'commit-help':
                writeln(_("  -l, --locale=ll_CC     Use this locale."));
                writeln(_("  -m, --module=MODULE    Commit translations only for this (Horde) module."));
                writeln(_("  -M, --message=MESSAGE  Use this commit message instead of the default ones."));
                writeln(_("  -n, --new              This is a new translation, commit also CREDITS,\n                         CHANGES and lang.php.dist."));
                break;
            case 'compendium':
                writeln(_("  -a, --add=FILE        Add this PO file to the compendium. Useful to\n                        include a compendium from a different branch to\n                        the generated compendium."));
                writeln(_("  -d, --directory=DIR   Create compendium in this directory."));
                writeln(_("  -l, --locale=ll_CC    Use this locale."));
                break;
            case 'extract':
                writeln(_("  -m, --module=MODULE  Generate POT file only for this (Horde) module."));
                break;
            case 'init':
                writeln(_("  -l, --locale=ll_CC    Use this locale."));
                writeln(_("  -m, --module=MODULE   Create a PO file only for this (Horde) module."));
                break;
            case 'make':
                writeln(_("  -l, --locale=ll_CC     Use only this locale."));
                writeln(_("  -m, --module=MODULE    Build MO files only for this (Horde) module."));
                writeln(_("  -c, --compendium=FILE  Merge new translations to this compendium file\n                         instead of the default one (compendium.po in the\n                         horde/po directory."));
                writeln(_("  -n, --no-compendium    Don't merge new translations to the compendium."));
                break;
            case 'make-help':
            case 'update-help':
                writeln(_("  -l, --locale=ll_CC     Use only this locale."));
                writeln(_("  -m, --module=MODULE    Update help files only for this (Horde) module."));
                break;
            case 'merge':
                writeln(_("  -l, --locale=ll_CC     Use this locale."));
                writeln(_("  -m, --module=MODULE    Merge PO files only for this (Horde) module."));
                writeln(_("  -c, --compendium=FILE  Use this compendium file instead of the default\n                         one (compendium.po in the horde/po directory)."));
                writeln(_("  -n, --no-compendium    Don't use a compendium."));
                break;
            case 'update':
                writeln(_("  -l, --locale=ll_CC     Use this locale."));
                writeln(_("  -m, --module=MODULE    Update only this (Horde) module."));
                writeln(_("  -c, --compendium=FILE  Use this compendium file instead of the default\n                         one (compendium.po in the horde/po directory)."));
                writeln(_("  -n, --no-compendium    Don't use a compendium."));
                break;
        }
    } else {
        writeln(_("Usage:") . ' translation.php [options] command [command-options]');
        writeln(str_repeat(' ', strlen(_("Usage:"))) . ' translation.php [help|-h|--help] [command]');
        writeln();
        writeln(_("Helper application to create and maintain translations for the Horde\nframework and its applications.\nFor an introduction read the file README in this directory."));
        writeln();
        writeln(_("Commands:"));
        writeln(_("  help        Show this help message."));
        writeln(_("  compendium  Rebuild the compendium file. Warning: This overwrites the\n              current compendium."));
        writeln(_("  extract     Generate PO template (.pot) files."));
        writeln(_("  init        Create one or more PO files for a new locale. Warning: This\n              overwrites the existing PO files of this locale."));
        writeln(_("  merge       Merge the current PO file with the current PO template file."));
        writeln(_("  update      Run extract and merge sequent."));
        writeln(_("  update-help Extract all new and changed entries from the English XML help\n              file and merge them with the existing ones."));
        writeln(_("  make        Build binary MO files from the specified PO files."));
        writeln(_("  make-help   Mark all entries in the XML help file being up-to-date and\n              prepare the file for the next execution of update-help. You\n              should only run make-help AFTER update-help and revising the\n              help file."));
        writeln(_("  commit      Commit translations to the CVS server."));
        writeln(_("  commit-help Commit help files to the CVS server."));
    }
    writeln();
    writeln(_("Options:"));
    writeln(_("  -b, --base=/PATH  Full path to the (Horde) base directory that should be\n                    used."));
    writeln(_("  -d, --debug       Show error messages from the executed binaries."));
    writeln(_("  -h, --help        Show this help message."));
    writeln(_("  -t, --test        Show the executed commands but don't run anything."));
}

function check_binaries()
{
    global $green, $normal;

    writeln(_("Searching gettext binaries..."));
    foreach (array('gettext', 'msgcat', 'msgcomm', 'msgfmt', 'msginit', 'msgmerge', 'xgettext') as $binary) {
        print $binary . '... ';
        $GLOBALS[$binary] = exec('which ' . $binary, $out, $ret);
        if ($ret == 0) {
            writeln(sprintf(_("%sfound:%s %s"), $green, $normal, $GLOBALS[$binary]));
        } else {
            writeln(red(_("not found")));
            footer();
        }
    }
    writeln();

    $out = '';
    exec($GLOBALS['gettext'] . ' --version', $out, $ret);
    $split = explode(' ', $out[0]);
    print('gettext version: ' . $split[count($split) - 1]);
    $version = explode('.', $split[count($split) - 1]);
    if ($version[0] == 0 && $version[1] < 11) {
        writeln();
        writeln(sprintf(_("%sWarning:%s This program was only tested on gettext 0.11. There is no\nguarantee that it will work with your version of gettext."), $red, $normal));
    } else {
        writeln(green(' ' . _("OK")));
    }

    print(_("PHP support in xgettext: "));
    $sh = $GLOBALS['xgettext'] . ' --help';
    putenv('LANG=en');
    $xget_help = `$sh`;
    putenv('LANG=' . $GLOBALS['language']);
    if (strstr($xget_help, 'PHP')) {
        writeln(green(_("Yes")));
        $GLOBALS['php_support'] = true;
    } else {
        writeln(red(_("No")) . _(", trying C++ instead"));
        $GLOBALS['php_support'] = false;
    }
    writeln();
}

function xtract()
{
    global $cmd_options, $apps, $dirs, $debug, $test;

    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'm':
            case '--module':
                $module = $option[1];
                break;
        }
    }
    if ($GLOBALS['php_support']) {
        $language = 'PHP';
    } else {
        $language = 'C++';
    }
    $curdir = `pwd`;
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        print(sprintf(_("Extracting from %s... "), $apps[$i]));
        if ($apps[$i] == 'horde') {
            $sh = 'cd ' . HORDE_BASE . '; ' .
                  $GLOBALS['xgettext'] . ' --language=' . $language . ' --keyword=_ --sort-output --copyright-holder="Horde Project" -o ' . $dirs[$i] . '/po/' . $apps[$i] . '.pot' .
                  ' `find . -maxdepth 1 -name \'*\\.php\'`' .
                  ' `find admin lib templates util -name \'*\\.php\' -o -name \'*\\.inc\' -o -name \'*\\.js\' -type f`' .
                  ' `find config -name \'*\\.dist\' -type f`';
            if (@file_exists(HORDE_BASE . '/po/translation.php')) {
                $sh .= ' po/translation.php';
            }
            if (!$debug) {
                $sh .= ' 2> /dev/null';
            }
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            if (!$test) exec($sh);
        } else {
            $sh = 'cd ' . $dirs[$i] . '; ' .
                  $GLOBALS['xgettext'] . ' --language=' . $language . ' --keyword=_ --sort-output --copyright-holder="Horde Project" -o ' . $dirs[$i] . '/po/' . $apps[$i] . '.pot' .
                  ' `find . -name \'*\\.php\' -o -name \'*\\.inc\' -o -name \'*\\.js\' -type f | grep -v /config/`' .
                  ' `find config -name \'*\\.dist\' -type f`' .
                  ($debug ? '' : ' 2> /dev/null');
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            if (!$test) exec($sh);
        }
        writeln(green(_("done")));
    }
    chdir(trim($curdir));
}

function merge()
{
    global $cmd_options, $apps, $dirs, $debug, $test;

    $compendium = ' --compendium=' . HORDE_BASE . '/po/compendium.po';
    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
            case 'c':
            case '--compendium':
                $compendium = ' --compendium=' . $option[1];
                break;
            case 'n':
            case '--no-compendium':
                $compendium = '';
                break;
        }
    }
    if (!isset($lang)) {
        writeln(red(_("Error: ") . _("No locale specified.")));
        writeln();
        usage();
        footer();
    }
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        writeln(sprintf(_("Merging translation for module %s..."), $apps[$i]));
        $dir = $dirs[$i] . '/po/';
        $sh = $GLOBALS['msgmerge'] . ' --update -v' . $compendium . ' ' . $dir . $lang . '.po ' . $dir . $apps[$i] . '.pot';
        if ($debug || $test) {
            writeln(_("Executing:"));
            writeln($sh);
        }
        if (!$test) exec($sh);
        writeln();
    }
}

function compendium()
{
    global $cmd_options, $dirs, $debug, $test;

    $dir = HORDE_BASE . '/po/';
    $add = '';
    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'd':
            case '--directory':
                $dir = $option[1];
                break;
            case 'a':
            case '--add':
                $add = ' ' . $option[1];
                break;
        }
    }
    if (!isset($lang)) {
        writeln(red(_("Error: ") . _("No locale specified.")));
        writeln();
        usage();
        footer();
    }
    print(sprintf(_("Merging all %s.po files to the compendium... "), $lang));
    $pofiles = array();
    for ($i = 0; $i < count($dirs); $i++) {
        $pofile = $dirs[$i] . '/po/' . $lang . '.po';
        if (file_exists($pofile)) {
            $pofiles[] = $pofile;
        }
    }
    if (!empty($dir) && substr($dir, -1) != '/') {
        $dir .= '/';
    }
    $sh = $GLOBALS['msgcat'] . ' --sort-output ' . implode(' ', $pofiles) . $add . ' > ' . $dir . 'compendium.po ' . ($debug ? '' : ' 2> /dev/null');
    if ($debug || $test) {
        writeln();
        writeln(_("Executing:"));
        writeln($sh);
    }
    if ($test) {
        $ret = 0;
    } else {
        exec($sh, $out, $ret);
    }
    if ($ret == 0) {
        writeln(green(_("done")));
    } else {
        writeln(red(_("failed")));
    }
}

function init()
{
    global $cmd_options, $apps, $dirs, $debug, $test;

    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
        }
    }
    if (empty($lang)) { $lang = getenv('LANG'); }
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        $package = ucfirst($apps[$i]);
        $package_u = strtoupper($apps[$i]);
        @include $dirs[$i] . '/lib/version.php';
        $version = eval('return(defined("' . $package_u . '_VERSION") ? ' . $package_u . '_VERSION : "???");');
        print(sprintf(_("Initializing module %s... "), $apps[$i]));
        if (!@file_exists($dirs[$i] . '/po/' . $apps[$i] . '.pot')) {
            writeln(red(_("failed")));
            writeln(sprintf(_("%s not found. Run 'translation extract' first."), $dirs[$i] . '/po/' . $apps[$i] . '.pot'));
            continue;
        }
        $sh = 'CURDIR=`pwd`; cd ' . $dirs[$i] . '/po; ' .
              $GLOBALS['msginit'] . ' --no-translator -i ' . $apps[$i] . '.pot ' . (!empty($lang) ? ' -o ' . $lang . '.po --locale=' . $lang : '') . ($debug ? '' : ' 2> /dev/null') .
              '; cd $CURDIR';
        if (!empty($lang)) {
            $pofile = $dirs[$i] . '/po/' . $lang . '.po';
            $sh .= "; sed 's/PACKAGE package/$package package/' $pofile " .
                   "| sed 's/PACKAGE VERSION/$package $version/' " .
                   "| sed 's/messages for PACKAGE/messages for $package/' " .
                   "| sed 's/Language-Team: none/Language-Team: i18n@lists.horde.org/' " .
                   "> $pofile.tmp";
        }
        if ($debug || $test) {
            writeln(_("Executing:"));
            writeln($sh);
        }
        if ($test) {
            $ret = 0;
        } else {
            exec($sh, $out, $ret);
        }
        $sh = "mv $pofile.tmp $pofile";
        if ($debug || $test) {
            writeln();
            writeln(_("Executing:"));
            writeln($sh);
        }
        if ($test) {
            $ret = 0;
        } else {
            exec($sh);
        }
        if ($ret == 0) {
            writeln(green(_("done")));
        } else {
            writeln(red(_("failed")));
        }
    }
}

function make()
{
    global $cmd_options, $apps, $dirs, $debug, $test, $bold, $normal;

    $compendium = HORDE_BASE . '/po/compendium.po';
    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
            case 'c':
            case '--compendium':
                $compendium = $option[1];
                break;
            case 'n':
            case '--no-compendium':
                $compendium = '';
                break;
        }
    }
    $horde = array_search('horde', $dirs);
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        writeln(sprintf(_("Building MO files for module %s%s%s..."), $bold, $apps[$i], $normal));
        if (empty($lang)) {
            $sh = "find $dirs[$i]/po -name \*.po -a -not -name messages.po -a -not -name compendium.po | sed 's;$dirs[$i]/po/;;g' | sed 's/.po//g'";
            if ($debug) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            $langs = explode("\n", trim(`$sh`));
        } else {
            if (!@file_exists($dirs[$i] . '/po/' . $lang . '.po')) {
                writeln(_("Skipped..."));
                writeln();
                continue;
            }
            $langs = array($lang);
        }
        foreach ($langs as $locale) {
            writeln(sprintf(_("Building locale %s%s%s... "), $bold, $locale, $normal));
            $dir = $dirs[$i] . '/locale/' . $locale . '/LC_MESSAGES';
            if (!is_dir($dir)) {
                $dir1 = substr($dir, 0, strrpos($dir, '/'));
                foreach (array($dir1, $dir) as $d) {
                    if ($debug) {
                        writeln(sprintf(_("Making directory %s"), $d));
                    }
                    if (!@mkdir($d)) {
                        writeln(red(_("Warning: ")) . sprintf(_("Could not create locale directory for locale %s:"), $locale));
                        writeln($d);
                        writeln();
                        continue;
                    }
                }
            }
            $pofile = $dirs[$i] . '/po/' . $locale . '.po';
            $sh = "cat $pofile | tr -d '\\r' > $pofile.tmp";
            if ($debug || $test) {
                writeln();
                writeln(_("Executing:"));
                writeln($sh);
            }
            if (!$test) {
                exec($sh);
            }
            $sh = "mv $pofile.tmp $pofile";
            if ($debug || $test) {
                writeln();
                writeln(_("Executing:"));
                writeln($sh);
            }
            if (!$test) {
                exec($sh);
            }
            $sh = $GLOBALS['msgfmt'] . ' --check ' . $pofile . ' 2>&1';
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            if ($test) {
                $ret = 0;
            } else {
                exec($sh, $out, $ret);
            }
            if ($ret != 0) {
                writeln(red(_("Warning: ")) . _("an error has occured:"));
                writeln(implode("\n", $out));
                writeln();
                continue;
            }
            $sh = $GLOBALS['msgfmt'] . ' --statistics --check -o ' . $dir . '/' . $apps[$i] . '.mo ';
            if ($apps[$i] != 'horde') {
                $horde_po = $dirs[$horde] . '/po/' . $locale . '.po';
                if (!@is_readable($horde_po)) {
                    writeln(red(_("Warning: ")) . sprintf(_("the Horde PO file for the locale %s does not exist:"), $locale));
                    writeln($horde_po);
                    writeln();
                    $sh .= $dirs[$i] . '/po/' . $locale . '.po';
                } else {
                    $sh = $GLOBALS['msgcomm'] . " --more-than=0 --sort-output $pofile $horde_po | $sh -";
                }
            } else {
                $sh .= $pofile;
            }
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            $out = '';
            if ($test) {
                $ret = 0;
            } else {
                exec($sh, $out, $ret);
            }
            if ($ret == 0) {
                writeln(green(_("done")));
            } else {
                writeln(red(_("failed")));
                writeln(implode("\n", $out));
            }
            if (count($langs) > 1) {
                continue;
            }
            if (!empty($compendium)) {
                print(sprintf(_("Merging the PO file for %s%s%s to the compendium... "), $bold, $apps[$i], $normal));
                if (!empty($dir) && substr($dir, -1) != '/') {
                    $dir .= '/';
                }
                $sh = $GLOBALS['msgcat'] . " --sort-output $compendium $pofile > $compendium.tmp";
                if (!$debug) {
                    $sh .= ' 2> /dev/null';
                }
                if ($debug || $test) {
                    writeln();
                    writeln(_("Executing:"));
                    writeln($sh);
                }
                $out = '';
                if ($test) {
                    $ret = 0;
                } else {
                    exec($sh, $out, $ret);
                }
                $sh = "mv $compendium.tmp $compendium";
                if ($debug || $test) {
                    writeln();
                    writeln(_("Executing:"));
                    writeln($sh);
                }
                if ($test) {
                    $ret = 0;
                } else {
                    exec($sh);
                }
                if ($ret == 0) {
                    writeln(green(_("done")));
                } else {
                    writeln(red(_("failed")));
                }
            }
            writeln();
        }
    }
}

function commit($help_only = false)
{
    global $cmd_options, $apps, $dirs, $debug, $test;

    $docs = false;
    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
            case 'n':
            case '--new':
                $docs = true;
                break;
            case 'M':
            case '--message':
                $msg = $option[1];
                break;
        }
    }
    $files = array();
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        if (empty($lang)) {
            if ($help_only) {
                $sh = "find $dirs[$i]/locale -name help.xml | sed 's;" . HORDE_BASE . "/;;'";
            } else {
                $sh = "find $dirs[$i]/po -name \*.po -a -not -name messages.po -a -not -name compendium.po | sed 's;" . HORDE_BASE . "/;;'; find $dirs[$i]/locale -type d -not -name CVS -maxdepth 1 -mindepth 1 | sed 's;" . HORDE_BASE . "/;;'";
            }
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            $files = array_merge($files, explode("\n", trim(`$sh`)));
        } else {
            if (!@file_exists($dirs[$i] . '/po/' . $lang . '.po')) { continue; }
            if ($help_only) {
                $file = str_replace(HORDE_BASE . '/', '', "$dirs[$i]/locale/$lang/help.xml");
                if (!@file_exists($file)) continue;
                $files[] = $file;
            } else {
                $files[] = str_replace(HORDE_BASE . '/', '', $dirs[$i] . '/po/' . $lang . '.po');
                $files[] = str_replace(HORDE_BASE . '/', '', $dirs[$i] . '/locale/' . $lang);
            }
        }
        if ($docs && !$help_only) {
            $files[] = str_replace(HORDE_BASE . '/', '', $dirs[$i] . '/docs');
            if ($apps[$i] == 'horde') {
                $horde_conf = $dirs[array_search('horde', $dirs)] . '/config/';
                $files[] = str_replace(HORDE_BASE . '/', '', $horde_conf . 'lang.php.dist');
            }
        }
    }
    if (count($files)) {
        if ($docs) {
            writeln(_("Adding new files to repository:"));
            $sh = 'CURDIR=`pwd`; cd ' . HORDE_BASE . '; cvs add';
            foreach ($files as $file) {
                if (strstr($file, 'locale') || strstr($file, '.po')) {
                    $sh .= " $file";
                    writeln($file);
                }
            }
            $sh .= '; cvs add';
            foreach ($files as $file) {
                if (strstr($file, 'locale')) {
                    if ($help_only) {
                        $sh .= " $file/*.xml";
                        writeln("$file/*.xml");
                    } else {
                        $sh .= " $file/*.xml $file/LC_MESSAGES";
                        writeln("$file/*.xml\n$file/LC_MESSAGES ");
                    }
                }
            }
            if (!$help_only) {
                $sh .= '; cvs add';
                foreach ($files as $file) {
                    if (strstr($file, 'locale')) {
                        $sh .= " $file/LC_MESSAGES/*.mo";
                        writeln("$file/LC_MESSAGES/*.mo");
                    }
                }
            }
            $sh .= '; cd $CURDIR';
            writeln();
            if ($debug || $test) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            if (!$test) system($sh);
            writeln();
        }
        writeln(_("Committing:"));
        writeln(implode(' ', $files));
        if (!empty($lang)) {
            $lang = ' ' . $lang;
        }
        if (empty($msg)) {
            if ($docs) {
                $msg = "Add$lang translation.";
            } elseif ($help_only) {
                $msg = "Update$lang help file.";
            } else {
                $msg = "Update$lang translation.";
            }
        }
        $sh = 'CURDIR=`pwd` && cd ' . HORDE_BASE . ' && cvs commit -m "' . $msg . '" ' . implode(' ', $files) . '; cd $CURDIR';
        if ($debug || $test) {
            writeln(_("Executing:"));
            writeln($sh);
        }
        if (!$test) system($sh);
    }
}

function update_help()
{
    global $cmd_options, $dirs, $apps, $debug, $test, $last_error_msg;

    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
        }
    }
    $files = array();
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        if (!is_dir("$dirs[$i]/locale")) continue;
        if (empty($lang)) {
            $sh = "find $dirs[$i]/locale -name help.xml";
            if ($debug) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            $files = explode("\n", trim(`$sh`));
        } else {
            $files = array("$dirs[$i]/locale/$lang/help.xml");
        }
        $file_en  = $dirs[$i] . '/locale/en_US/help.xml';
        if (!@file_exists($file_en)) {
            writeln(wordwrap(red(_("Warning: ")) . sprintf(_("There doesn't yet exist a help file for %s."), bold($apps[$i]))));
            writeln();
            continue;
        }
        foreach ($files as $file_loc) {
            $locale = substr($file_loc, 0, strrpos($file_loc, '/'));
            $locale = substr($locale, strrpos($locale, '/') + 1);
            if ($locale == 'en_US') continue;
            if (!@file_exists($file_loc)) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("The %s help file for %s doesn't yet exist. Creating a new one."), bold($locale), bold($apps[$i]))));
                $dir_loc = substr($file_loc, 0, -9);
                if (!is_dir($dir_loc)) {
                    if ($debug || $test) {
                        writeln(sprintf(_("Making directory %s"), $dir_loc));
                    }
                    if (!$test && !@mkdir($dir_loc)) {
                        writeln(red(_("Warning: ")) . sprintf(_("Could not create locale directory for locale %s:"), $locale));
                        writeln($dir_loc);
                        writeln();
                        continue;
                    }
                }
                if ($debug || $test) {
                    writeln(wordwrap(sprintf(_("Copying %s to %s"), $file_en, $file_loc)));
                }
                if (!$test && !@copy($file_en, $file_loc)) {
                    writeln(red(_("Warning: ")) . sprintf(_("Could not copy %s to %s"), $file_en, $file_loc));
                }
                writeln();
                continue;
            }
            writeln(sprintf(_("Updating %s help file for %s."), bold($locale), bold($apps[$i])));
            $fp = fopen($file_loc, 'r');
            $line = fgets($fp);
            fclose($fp);
            if (!strstr($line, '<?xml')) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("The help file %s didn't start with <?xml"), $file_loc)));
                writeln();
                continue;
            }
            $encoding = '';
            if (preg_match('/encoding=(["\'])([^\\1]+)\\1/', $line, $match)) {
                $encoding = $match[2];
            }
            $doc_en = domxml_open_file($file_en);
            if (!is_object($doc_en)) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("There was an error opening the file %s. Try running translation.php with the flag -d to see any error messages from the xml parser."), $file_en)));
                writeln();
                continue 2;
            }
            $doc_loc = domxml_open_file($file_loc);
            if (!is_object($doc_loc)) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("There was an error opening the file %s. Try running translation.php with the flag -d to see any error messages from the xml parser."), $file_loc)));
                writeln();
                continue;
            }
            $doc_new  = domxml_new_doc('1.0');
            $help_en  = $doc_en->document_element();
            $help_loc = $doc_loc->document_element();
            $help_new = $help_loc->clone_node();
            $entries_loc = array();
            $entries_new = array();
            $count_uptodate = 0;
            $count_new      = 0;
            $count_changed  = 0;
            $count_unknown  = 0;
            foreach ($doc_loc->get_elements_by_tagname('entry') as $entry) {
                $entries_loc[$entry->get_attribute('id')] = $entry;
            }
            foreach ($doc_en->get_elements_by_tagname('entry') as $entry) {
                $id = $entry->get_attribute('id');
                if (array_key_exists($id, $entries_loc)) {
                    if ($entries_loc[$id]->has_attribute('md5') &&
                        md5($entry->get_content()) != $entries_loc[$id]->get_attribute('md5')) {
                        $comment = $doc_loc->create_comment(" English entry:\n" . str_replace('--', '&#45;&#45;', $doc_loc->dump_node($entry)));
                        $entries_loc[$id]->append_child($comment);
                        $entry_new = $entries_loc[$id]->clone_node(true);
                        $entry_new->set_attribute('state', 'changed');
                        $count_changed++;
                    } else {
                        if (!$entries_loc[$id]->has_attribute('state')) {
                            $comment = $doc_loc->create_comment(" English entry:\n" . str_replace('--', '&#45;&#45;', $doc_loc->dump_node($entry)));
                            $entries_loc[$id]->append_child($comment);
                            $entry_new = $entries_loc[$id]->clone_node(true);
                            $entry_new->set_attribute('state', 'unknown');
                            $count_unknown++;
                        } else {
                            $entry_new = $entries_loc[$id]->clone_node(true);
                            $count_uptodate++;
                        }
                    }
                } else {
                    $entry_new = $entry->clone_node(true);
                    $entry_new->set_attribute('state', 'new');
                    $count_new++;
                }
                $entries_new[] = $entry_new;
            }
            $doc_new->append_child($doc_new->create_comment(' $' . 'Horde$ '));
            foreach ($entries_new as $entry) {
                $help_new->append_child($entry);
            }
            writeln(wordwrap(sprintf(_("Entries: %d total, %d up-to-date, %d new, %d changed, %d unknown"),
                                     $count_uptodate + $count_new + $count_changed + $count_unknown,
                                     $count_uptodate, $count_new, $count_changed, $count_unknown)));
            $doc_new->append_child($help_new);
            $output = $doc_new->dump_mem(true, $encoding);
            if ($debug || $test) {
                writeln(wordwrap(sprintf(_("Writing updated help file to %s."), $file_loc)));
            }
            if (!$test) {
                $fp = fopen($file_loc, 'w');
                $line = fwrite($fp, $output);
                fclose($fp);
            }
            writeln(sprintf(_("%d bytes written."), strlen($output)));
            writeln();
        }
    }
}

function make_help()
{
    global $cmd_options, $dirs, $apps, $debug, $test;

    foreach ($cmd_options[0] as $option) {
        switch ($option[0]) {
            case 'h':
                usage();
                footer();
            case 'l':
            case '--locale':
                $lang = $option[1];
                break;
            case 'm':
            case '--module':
                $module = $option[1];
                break;
        }
    }
    $files = array();
    for ($i = 0; $i < count($dirs); $i++) {
        if (!empty($module) && $module != $apps[$i]) { continue; }
        if (!is_dir("$dirs[$i]/locale")) continue;
        if (empty($lang)) {
            $sh = "find $dirs[$i]/locale -name help.xml";
            if ($debug) {
                writeln(_("Executing:"));
                writeln($sh);
            }
            $files = explode("\n", trim(`$sh`));
        } else {
            $files = array("$dirs[$i]/locale/$lang/help.xml");
        }
        $file_en  = $dirs[$i] . '/locale/en_US/help.xml';
        if (!@file_exists($file_en)) {
            continue;
        }
        foreach ($files as $file_loc) {
            $locale = substr($file_loc, 0, strrpos($file_loc, '/'));
            $locale = substr($locale, strrpos($locale, '/') + 1);
            if ($locale == 'en_US') continue;
            writeln(sprintf(_("Updating %s help file for %s."), bold($locale), bold($apps[$i])));
            $fp = fopen($file_loc, 'r');
            $line = fgets($fp);
            fclose($fp);
            if (!strstr($line, '<?xml')) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("The help file %s didn't start with <?xml"), $file_loc)));
                writeln();
                continue;
            }
            $encoding = '';
            if (preg_match('/encoding=(["\'])([^\\1]+)\\1/', $line, $match)) {
                $encoding = $match[2];
            }
            $doc_en   = domxml_open_file($file_en);
            if (!is_object($doc_en)) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("There was an error opening the file %s. Try running translation.php with the flag -d to see any error messages from the xml parser."), $file_en)));
                writeln();
                continue 2;
            }
            $doc_loc  = domxml_open_file($file_loc);
            if (!is_object($doc_loc)) {
                writeln(wordwrap(red(_("Warning: ")) . sprintf(_("There was an error opening the file %s. Try running translation.php with the flag -d to see any error messages from the xml parser."), $file_loc)));
                writeln();
                continue;
            }
            $help_loc = $doc_loc->document_element();
            $md5_en   = array();
            $count_all = 0;
            $count     = 0;
            foreach ($doc_en->get_elements_by_tagname('entry') as $entry) {
                $md5_en[$entry->get_attribute('id')] = md5($entry->get_content());
            }
            foreach ($doc_loc->get_elements_by_tagname('entry') as $entry) {
                foreach ($entry->child_nodes() as $child) {
                    if ($child->node_type() == XML_COMMENT_NODE && strstr($child->node_value(), 'English entry')) {
                        $entry->remove_child($child);
                    }
                }
                $count_all++;
                $id = $entry->get_attribute('id');
                if (!array_key_exists($id, $md5_en)) {
                    writeln(wordwrap(red(_("Warning: ")) . sprintf(_("No entry with the id '%s' exists in the original help file."), $id)));
                } else {
                    $entry->set_attribute('md5', $md5_en[$id]);
                    $entry->set_attribute('state', 'uptodate');
                    $count++;
                }
            }
            $output = $doc_loc->dump_mem(true, $encoding);
            if (!$test) {
                $fp = fopen($file_loc, 'w');
                $line = fwrite($fp, $output);
                fclose($fp);
            }
            writeln(sprintf(_("%d of %d entries marked as up-to-date"), $count, $count_all));
            writeln();
        }
    }
}

@set_time_limit(0);
ob_implicit_flush(true);
ini_set('html_errors', false);

$term = getenv('TERM');
if ($term) {
    if (preg_match('/^(xterm|vt220|linux)/', $term)) {
        $bold   = "\x1b[1m";
        $normal = "\x1b[0m";
        $red    = "\x1b[01;31m";
        $green  = "\x1b[01;32m";
    } elseif (preg_match('/^vt100/', $term)) {
        $bold   = "\x1b[1m";
        $normal = "\x1b[0m";
        $red    = '';
        $green  = '';
    } else {
        $bold = $normal = $red = $green = '';
    }
} else {
    $bold = $normal = $red = $green = '';
}

$language = getenv('LANG');
if (empty($language)) {
    $language = getenv('LANGUAGE');
}

$HORDE_BASE = dirname(__FILE__) . '/..';
if (!empty($language)) {
    require $HORDE_BASE . '/config/lang.php.dist';
    require_once $HORDE_BASE . '/lib/Lang.php';
    $tmp = explode('.', $language);
    $language = $tmp[0];
    $language = Lang::_map(trim($language));
    if (!Lang::isValid($language)) {
        $language = Lang::_map(substr($language, 0, 2));
    }
    if (Lang::isValid($language)) {
        setlocale(LC_ALL, $language);
        bindtextdomain('horde', $HORDE_BASE . '/locale');
        textdomain('horde');
        if (array_key_exists(1, $tmp)) {
            bind_textdomain_codeset('horde', $tmp[1]);
        }
    }
}

print($bold);
writeln(_("---------------------------"));
writeln(_("Horde translation generator"));
writeln(_("---------------------------"));
print($normal);
writeln();

/* Sanity checks */
if (!extension_loaded('gettext')) {
    writeln(red('Gettext extension not found!'));
    footer();
}

writeln(_("Loading libraries..."));
print 'Console_Getopt... ';
@include 'Console/Getopt.php';
if (class_exists('Console_Getopt')) {
    writeln(green(_("OK")));
} else {
    writeln(red(_("Console_Getopt not found.")), true);
    writeln();
    writeln(_("Make sure that you have PEAR installed and in your include path."));
    writeln('include_path: ' . ini_get('include_path'));
    footer();
}
writeln();

/* Commandline parameters */
$args    = Console_Getopt::readPHPArgv();
$options = Console_Getopt::getopt($args, 'b:dht', array('base=', 'debug', 'help', 'test'));
if (PEAR::isError($options) && $args[0] == $_SERVER['PHP_SELF']) {
    array_shift($args);
    $options = Console_Getopt::getopt($args, 'b:dht', array('base=', 'debug', 'help', 'test'));
}
if (PEAR::isError($options)) {
    writeln(red(_("Getopt Error: ") . str_replace('Console_Getopt:', '', $options->getMessage())));
    writeln();
    usage();
    footer();
}
if (empty($options[1][0])) {
    writeln(red(_("Error: ") . ("No command specified.")));
    writeln();
    usage();
    footer();
}
$debug = false;
$test  = false;
foreach ($options[0] as $option) {
    switch ($option[0]) {
        case 'b':
        case '--base':
            if (substr($option[1], -1) == '/') {
                $option[1] = substr($option[1], 0, -1);
            }
            define('HORDE_BASE', $option[1]);
            break;
        case 'd':
        case '--debug':
            $debug = true;
            break;
        case 't':
        case '--test':
            $test = true;
            break;
        case 'h':
        case '--help':
            usage();
            footer();
    }
}
if (!$debug) {
    ini_set('error_reporting', false);
}
if (!defined('HORDE_BASE')) {
    define('HORDE_BASE', $HORDE_BASE);
}
if ($options[1][0] == 'help') {
    usage();
    footer();
}
$options_list = array(
    'commit'     => array('hl:m:nM:', array('module=', 'locale=', 'new', 'message=')),
    'commit-help'=> array('hl:m:nM:', array('module=', 'locale=', 'new', 'message=')),
    'compendium' => array('hl:d:a:', array('locale=', 'directory=', 'add=')),
    'extract'    => array('hm:', array('module=')),
    'init'       => array('hl:m:', array('module=', 'locale=')),
    'merge'      => array('hl:m:c:n', array('module=', 'locale=', 'compendium=', 'no-compendium')),
    'make'       => array('hl:m:c:n', array('module=', 'locale=', 'compendium=', 'no-compendium')),
    'make-help'  => array('hl:m:', array('module=', 'locale=')),
    'update'     => array('hl:m:c:n', array('module=', 'locale=', 'compendium=', 'no-compendium')),
    'update-help'=> array('hl:m:', array('module=', 'locale='))
);
$options_arr = $options[1];
$cmd         = array_shift($options_arr);
if (array_key_exists($cmd, $options_list)) {
    $cmd_options = Console_Getopt::getopt($options_arr, $options_list[$cmd][0], $options_list[$cmd][1]);
    if (PEAR::isError($cmd_options)) {
        writeln(red(_("Error: ") . str_replace('Console_Getopt:', '', $cmd_options->getMessage())));
        writeln();
        usage();
        footer();
    }
}

/* Searching applications */
check_binaries();

writeln(_("Searching Horde applications..."));
$sh = 'find ' . HORDE_BASE . ' -name po -type d -maxdepth 2 | sed -e \'s;/po$;;\'';
if ($debug) {
    writeln(_("Executing:"));
    writeln($sh);
}
$dirlist = trim(`$sh`);
$dirs = explode("\n", $dirlist);
sort($dirs);
if ($debug) {
    writeln(_("Found directories:"));
    writeln(implode("\n", $dirs));
}

$applist = str_replace(HORDE_BASE . '/', '', str_replace(HORDE_BASE . "\n", '', $dirlist));
$apps = explode("\n", $applist);
sort($apps);
$apps = array_merge('horde', $apps);
writeln(wordwrap(sprintf(_("Found applications: %s"), implode(', ', $apps))));
writeln();

switch ($cmd) {
    case 'commit':
        commit();
        break;
    case 'commit-help':
        commit(true);
        break;
    case 'compendium':
        compendium();
        break;
    case 'extract':
        xtract();
        break;
    case 'init':
        init();
        break;
    case 'make':
        make();
        break;
    case 'make-help':
        make_help();
        break;
    case 'merge':
        merge();
        break;
    case 'update':
        xtract();
        writeln();
        merge();
        break;
    case 'update-help':
        update_help();
        break;
    default:
        writeln(red(_("Error: ")) . sprintf(_("Unknown command: %s"), $cmd));
        writeln();
        usage();
        footer();
}

footer();
