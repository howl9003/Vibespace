<?php
// $Horde: horde/lib/Lang.php,v 1.13.2.14 2003/01/17 10:22:14 jan Exp $

/**
 * The Lang:: class provides common methods for handling language detection
 * and selection.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Lang {

    /**
     * Selects the most preferred language for the current client session.
     *
     * @return string        The selected language abbreviation.
     * @access public
     */
    function select()
    {
        global $nls, $prefs;

        $lang = Horde::getFormData('new_lang');

        /* First, check if language pref is locked and if so set it to its value */
        if (isset($prefs) && $prefs->isLocked('language')) {
            $language = $prefs->getValue('language');
        /* Check if the user selected a language from the login screen */
        } elseif (!empty($lang)) {
            $language = $lang;
        /* Check if we have a language set in a cookie */
        } elseif (isset($_SESSION['horde_language'])) {
            $language = $_SESSION['horde_language'];

        /* Try browser-accepted languages, then default. */
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

            /* The browser supplies a list, so return the first valid one. */
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $lang) {
                $lang = Lang::_map(trim($lang));
                if (Lang::isValid($lang)) {
                    $language = $lang;
                    break;
                } elseif (Lang::isValid(Lang::_map(substr($lang, 0, 2)))) {
                    $language = Lang::_map(substr($lang, 0, 2));
                    break;
                }
            }
        }

        /* No dice auto-detecting, so give them the server default. */
        if (!isset($language)) {
            $language = $nls['defaults']['language'];
        }

        return basename($language);
    }

    /**
     * Sets the language.
     *
     * @param string $lang          (optional) The language abbriviation
     * @access public
     */
    function setLang($lang = null)
    {
        if (@file_exists(HORDE_BASE . '/config/lang.php')) {
            include_once HORDE_BASE . '/config/lang.php';
        } else {
            include_once HORDE_BASE . '/config/lang.php.dist';
        }
        if (empty($lang) || !Lang::isValid($lang)) {
            $lang = Lang::select();
        }
        $GLOBALS['language'] = $lang;
        putenv('LANG=' . $lang);
        putenv('LANGUAGE=' . $lang);
        setlocale(LC_ALL, $lang);
    }

    /**
     * Sets the gettext domain.
     *
     * @param string $app           The application name
     * @param string $directory     The directory where the application's
     *                              LC_MESSAGES directory resides
     * @param string $charset       The charset
     *
     * @since Horde 2.1
     */
    function setTextdomain($app, $directory, $charset)
    {
        bindtextdomain($app, $directory);
        textdomain($app);
        if (function_exists('bind_textdomain_codeset')) {
            bind_textdomain_codeset($app, $charset);
        }
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . $charset);
        }
    }

    /**
     * Alias for Lang::setTextdomain().
     *
     * @deprecated since Horde 2.1. Replaced by Lang::setTextdomain().
     * @see Lang::setTextdomain()
     */
    function setDomain($app, $directory, $charset)
    {
        Lang::setTextdomain($app, $directory, $charset);
    }

    /**
     * Determines whether the supplied language is valid.
     *
     * @param string $language         The abbreviated name of the language.
     *
     * @return  boolean         True if the language is valid, false if it's
     *                          not valid or unknown.
     * @access public
     */
    function isValid($language)
    {
        return !empty($GLOBALS['nls']['languages'][$language]);
    }

    /**
     * Maps languages with common two-letter codes (such as nl) to the
     * full gettext code (in this case, nl_NL). Returns the language
     * unmodified if it isn't an alias.
     *
     * @param string $language   The language code to map.
     * @return string            The mapped language code.
     * @access private
     */

    function _map($language)
    {
        $aliases = &$GLOBALS['nls']['aliases'];

        // First check if the untranslated language can be found
        if (!empty($aliases[$language])) {
            return $aliases[$language];
        }

        // Translate the $language to get broader matches
        // eg. de-DE should match de_DE
        $trans_lang = str_replace('-', '_', $language);
        $lang_parts = explode('_', $trans_lang);
        $trans_lang = strtolower($lang_parts[0]);
        if (isset($lang_parts[1])) $trans_lang .= '_' . strtoupper($lang_parts[1]);

        // See if we get a match for this
        if (!empty($aliases[$trans_lang])) {
            return $aliases[$trans_lang];
        }

        // If we get that far down, the language cannot be found.
        // Return $trans_lang
        return $trans_lang;
    }

    /**
     * Return the charset for the current language.
     *
     * @return string The character set that should be used with the
     * current locale settings.
     *
     * @since Horde 2.1
     */
    function getCharset()
    {
        return !empty($GLOBALS['nls']['charsets'][$GLOBALS['language']]) ? $GLOBALS['nls']['charsets'][$GLOBALS['language']] : $GLOBALS['nls']['defaults']['charset'];
    }

}
