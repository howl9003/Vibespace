<?php
// $Horde: chora/lib/Chora.php,v 1.15.2.12 2003/09/05 01:14:58 chuck Exp $

require_once CHORA_BASE . '/lib/constants.php';
require_once CHORA_BASE . '/lib/CVSLib.php';

/* Variable we wish to propagate across web pages
 *  sbt = Sort By Type (name, age, author, etc)
 *  ha  = Hide Attic Files
 *  ord = Sort order
 *
 * Obviously, defaults go into $defaultActs :)
 * TODO: defaults of 1 will not get propagated correctly - avsm
 * XXX: Rewrite this propagation code, since it sucks - avsm
 */

$defaultActs = array('sbt'   => $conf['options']['defaultsort'],
                     'sa'    => 0,
                     'login' => 0,
                     'ord'   => CVSLIB_SORT_ASCENDING,
                     'ws'    => 1);

/* Use the last cvsroot used as the default value if the user has that
 * preference. */
$remember_last_file = $prefs->getValue('remember_last_file');
if ($remember_last_file) {
    $last_file = $prefs->getValue('last_file') ? $prefs->getValue('last_file') : null;
    $last_cvsroot = $prefs->getValue('last_cvsroot') ? $prefs->getValue('last_cvsroot') : null;
}

if ($remember_last_file && !empty($last_cvsroot) &&
    is_array(@$cvsroots[$last_cvsroot])) {
    $defaultActs['rt'] = $last_cvsroot;
} else {
    foreach ($cvsroots as $key => $val) {
        if (isset($val['default']) || !isset($defaultActs['rt'])) {
            $defaultActs['rt'] = $key;
        }
    }
}

/* See if any have been passed as GET variables, and if
 * so, assign them into the acts array */
while (list($key,) = each($defaultActs)) {
    $acts[$key] = Horde::getFormData($key, $defaultActs[$key]);
}

if (!isset($cvsroots[$acts['rt']])) {
    fatal(404, 'Malformed URL');
}

$cvsrootopts = $cvsroots[$acts['rt']];
$cvsroot = $cvsrootopts['location'];

$conf['paths']['cvsRoot'] = $cvsrootopts['location'];
$conf['paths']['cvsusers'] = "$cvsroot/". @$cvsrootopts['cvsusers'];
$conf['paths']['introText'] = CHORA_BASE . '/config/' . @$cvsrootopts['intro'];
$conf['options']['introTitle'] = @$cvsrootopts['title'];
$conf['options']['cvsRootName'] = $cvsrootopts['name'];

$CVS = new CVSLib();

/**
 * Output an error page with relevant HTTP error headers
 *
 * @param errcode The HTTP error number and text
 * @param errmsg The verbose error message to be displayed
 */
function fatal($errcode, $errmsg)
{
    global $registry, $conf, $hordeMessageStack, $browser, $prefs;
    header("Status: $errcode");

    /* If we hit a 40x, then don't store the bad file in the preference */
    if (preg_match('|40[43]|', $errcode) && is_object($prefs)) {
        $prefs->setValue('last_file', '');
        $prefs->store();
    }

    include CHORA_TEMPLATES . '/common-header.inc';
    include CHORA_BASE . '/menu.php';
    Horde::raiseMessage($errcode, HORDE_ERROR);
    Horde::raiseMessage($errmsg, HORDE_ERROR);
    include CHORA_BASE . '/status.php';
    include CHORA_TEMPLATES . '/common-footer.inc';
    exit;
}

/**
 * Given a return object from a CVSLib call, make sure
 * that it's not a CVSLib_Error object.
 * @param e Return object from a CVSLib call
 */
function checkError($e)
{
    if (is_object($e) && ($e->id() == CVSLIB_ERROR)) {
        fatal($e->error_header(), $e->error_body());
    }
}

$f = Horde::getFormData('f', '');
$where = $f;

/* Override $where with PATH_INFO if appropriate */
if ($conf['options']['use_path_info'] && isset($_SERVER['PATH_INFO'])) {
    $where = $_SERVER['PATH_INFO'];
}

/* Location relative to the CVSROOT */
$where = stripslashes($where);
$where = preg_replace("|^/|", '', $where);
$where = preg_replace("|\.\.|", '', $where);
$where = preg_replace('|/$|', '', $where);

/* Location of this script (e.g. /chora/cvs.php) */
$scriptName = preg_replace('|^/?|', '/', $_SERVER['REQUEST_URI']);
$scriptName = preg_replace('|/$|', '', $scriptName);

/* Store last file/repository viewed, and set 'where' to last_file if
 * necessary. */
if ($remember_last_file) {
    /* We store last_cvsroot and last_file only when we have already
     * displayed at least one page. */
    if ($acts['login'] == 2) {
        $prefs->setValue('last_cvsroot', $acts['rt']);
        $prefs->setValue('last_file', $where);
        $prefs->store();
    }
    /* We are displaying the first page. */
    if ($acts['login'] == 1) {
        $where = $last_file;
        $acts['login'] = 2;
    }
}

$fullname = "$cvsroot/$where";

if (!@is_dir($cvsroot)) {
    fatal("500 Internal Error", "CVSROOT not found! This could be a misconfiguration by the server administrator, or the server could be having temporary problems. Please try again later.");
}


/**
 * Chora Base Class.
 *
 * @author Anil Madhavapeddy <avsm@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @package chora
 */
class Chora {

    function whereMenu()
    {
        global $where;

        $bar = '';
        $wherePath = '';

        foreach (explode('/', $where) as $dir) {
            $wherePath .= "/$dir";
            if (!empty($dir) && ($dir != 'Attic')) {
                $bar .= '/ <a href="' . Chora::url('cvs', $wherePath) . '">'. Text::htmlallspaces($dir) . '</a>';
            }
        }
        return $bar;
    }

    /*
     * Return an array with the names of any of the variables we
     * need to keep, that are different from the defaults
     *
     * @ret Array containing names/vals of differing variables
     */
    function differingVars()
    {
        global $acts, $defaultActs;
        reset($acts);
        $ret = array();
        while (list($key, $val) = each($acts)) {
            if ($val != $defaultActs[$key]) {
                $ret[$key] = $val;
            }
        }
        return $ret;
    }

    /**
     * Generate a series of HIDDEN input forms based on the
     * GET parameters which are different from the defaults
     *
     * @param except Array of exceptions to never output
     * @return A set of INPUT tags with the different variables
     */
    function generateHiddens($except = array())
    {
        global $acts;
        $toOut = Chora::differingVars();
        $ret = Horde::formInput() . "\n";
        while (list($key, $val) = each($toOut)) {
            if (is_array($except) && !in_array($key, $except)) {
                $ret .= "<input type=\"hidden\" name=\"$key\" value=\"$val\" />\n";
            }
        }
        return $ret;
    }

    /**
     * Convert a commit-name into whatever the user wants
     * @param commit name
     * @return transformed name
     */
    function showAuthorName($name, $fullname = false)
    {
        global $CVS;

        if ($CVS->parseCVSUsers() && is_array($CVS->cvsusers) && isset($CVS->cvsusers[$name])) {
            return '<a href="mailto:'.$CVS->cvsusers[$name]['mail']. '">' .
            ($fullname ? $CVS->cvsusers[$name]['name'] : $name) .
            '</a>' . ($fullname ? " <i>($name)</i>" : '');
        } else {
            return $name;
        }
    }

    /**
     * Generate a URL that links into Chora.
     * @param script Name of the Chora script to link into
     * @param uri Any PATH_INFO portion that should be included
     * @param args Key/value pair of any GET parameters to append
     * @param anchor Anchor entity name
     */
    function url($script, $uri = '', $args = array(), $anchor = '')
    {
        global $registry, $conf;

        $url = $registry->getParam('webroot') . '/' . $script . '.php';
        $uri = rawurlencode($uri);

        $arglist = array_merge(Chora::differingVars(), $args);

        if ($conf['options']['use_path_info']) {
            $url .= '/' . $uri;
        } else {
            $arglist['f'] = $uri;
        }

        if (!isset($_COOKIE[session_name()])) {
            $arglist[urlencode(session_name())] = session_id();
        }

        $argarr = array();
        foreach ($arglist as $key => $val) {
            if (!empty($val) || $val === 0) {
                $val = htmlspecialchars($val);
                $argarr[] = "$key=$val";
            }
        }

        if (sizeof($argarr) > 0) {
            $url = "$url?" . implode('&', $argarr);
            $glue = '&';
        } else {
            $glue = '?';
        }

        if (!empty($anchor)) {
            $url .= "#$anchor";
        }

        $url = preg_replace('|/\?|', '?', $url);
        $url = preg_replace('|%2F|', '/', $url);
        $url = preg_replace('|/+|', '/', $url);
        $url = str_replace('&', '&amp;', $url);

        return $url;
    }

    /**
     * Turn text into HTML.
     *
     * @access public
     *
     * @param string $input  An url-decoded string, \n-separated for lines.
     *
     * @return string  The converted HTML.
     */
    function toHTML($text)
    {
        $charset = Lang::getCharset();

        /* Tack on spaces so that we can count on whitespace coming before
           and after URLs and email addresses. */
        $text = ' ' . $text . ' ';

        /* Do in-lining of http://xxx.xxx to link, xxx@xxx.xxx to email,
           part one. */
        global $registry;
        /* Make sure the original message doesn't contain any capital <A>
           or </A> tags so we can assume we generated them. */
        $text = str_replace('</A>', '</a>', $text);
        $text = str_replace('<A', '<a', $text);

        $text = Text::linkUrls($text, true);
        $text = @preg_replace('|(\[\s+)*([Mm][Aa][Ii][Ll][Tt][Oo]):(\s?)([^\s\?(?(1)\])"<]*)(\??)([^\s"<]*[\w+#?/&=])?|e',
                              "'\\2:\\3<A href=\"' . str_replace('&amp;', '&', \$registry->link('mail/compose', array('to' => '\\4'), '&\\6')) . '\" onmouseover=\"status=\'' . @htmlspecialchars(addslashes(sprintf(_(\"Compose Message (%s)\"), '\\4')), ENT_QUOTES, $charset) . '\'; return true;\" onmouseout=\"status=\'\';\">\\4\\5\\6</A>'", $text);

        /* For level TEXT_HTML_MICRO, TEXT_HTML_NOHTML, start with
           htmlspecialchars(). */
        $text = @htmlspecialchars($text, ENT_QUOTES, $charset);

        /* Do in-lining of http://xxx.xxx to link, xxx@xxx.xxx to email,
           part two. */
        $text = str_replace('&lt;A href=&quot;', '<a href="', $text);
        $text = str_replace('&quot; target=&quot;_blank&quot;&gt;', '" target="_blank">', $text);
        $text = str_replace('&quot; onmouseover=&quot;', '" onmouseover="', $text);
        $text = str_replace('&quot; onmouseout=&quot;', '" onmouseout="', $text);
        $text = str_replace('&quot;&gt;','">', $text);
        $text = str_replace('\');&quot;&gt;', '\');">', $text);
        /* Only reconvert capital /A tags - the ones we generated. */
        $text = str_replace('&lt;/A&gt;', '</a>', $text);

        /* Do the blank-line ---> <br /> substitution.
           Everybody gets this; if you don't want even that, just save
           the htmlspecialchars() version of the input. */
        $text = nl2br($text);

        return trim($text);
    }

    /**
     * Generate a list of repositories available from this installation
     * of Chora.
     * @return XHTML code representing links to the repositories
     */
    function repositories()
    {
        global $cvsroot, $cvsroots, $defaultActs;

        $arr = array();
        foreach ($cvsroots as $key=>$val) {
            if ($cvsroot != $val['location']) {
                $arg = array('rt' => (($defaultActs['rt'] == $key) ? '' : $key));
                $arr[] = '<b><a href="' . Chora::url('cvs', '', $arg) . '">' .
                         $val['name'] . '</a></b>';
            }
        }

        if (sizeof($arr)) {
            return _("Other Repositories") . ': ' . implode(' , ', $arr);
        } else {
            return "";
        }
    }

    /**
     * Check if the given item is restricted from being shown.
     * @return boolean whether or not the item is allowed to be displayed
     **/
    function isRestricted($item)
    {
        global $conf, $cvsroots, $cvsroot;
        static $restricted;

        if (!isset($restricted)) {
            $restricted = array();
            if (isset($conf['restrictions']) && is_array($conf['restrictions'])) {
                $restricted = $conf['restrictions'];
            }

            foreach ($cvsroots as $key => $val) {
                if ($cvsroot == $val['location']) {
                    if (isset($val['restrictions']) && is_array($val['restrictions'])) {
                        $restricted = array_merge($restricted, $val['restrictions']);
                        break;
                    }
                }
            }
        }

        if (!empty($restricted) && is_array($restricted) && count($restricted)) {
            for ($i = 0; $i < count($restricted); $i++) {
                if (preg_match('|' . str_replace('|', '\|', $restricted[$i]) . '|', $item)) {
                    return true;
                }
            }
        }

        return false;
    }

}
