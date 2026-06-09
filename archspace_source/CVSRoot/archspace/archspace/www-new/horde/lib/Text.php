<?php
/*
 * $Horde: horde/lib/Text.php,v 1.6.2.17 2003/12/09 18:59:21 slusarz Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

define('TEXT_HTML_PASSTHRU', 0);
define('TEXT_HTML_SYNTAX', 1);
define('TEXT_HTML_REDUCED', 2);
define('TEXT_HTML_MICRO', 3);
define('TEXT_HTML_NOHTML', 4);
define('TEXT_HTML_NOHTML_NOBREAK', 5);

/**
 * The Text:: class provides common methods for manipulating text.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Text {

    /**
     * Filter the given text based on the words found in $words.
     *
     * @param string $text         The text to filter.
     * @param string $words_file   Filename containing the words to replace.
     * @param string $replacement  The replacement string.
     *
     * @return string  The filtered version of $text.
     */
    function filter($text, $words_file, $replacement)
    {
        if (@is_readable($words_file)) {
            /* Read the file and iterate through the lines. */
            $lines = file($words_file);
            foreach ($lines as $line) {
                /* Strip whitespace and comments. */
                $line = trim($line);
                $line = preg_replace('|#.*$|', '', $line);

                /* Filter the text. */
                if (!empty($line)) {
                    $text = preg_replace("/(\b(\w*)$line\b|\b$line(\w*)\b)/i",
                                         $replacement, $text);
                }
            }
        }

        return $text;
    }

    /**
     * Fixes incorrect wrappings which split double-byte gb2312 characters
     *
     * @param string $text          String containing wrapped gb2312 characters
     * @param $break_char          Character used to break lines.
     *
     * @return string                  String containing fixed text.
     *
     * @since Horde 2.2
     */
    function trim_gb2312($str, $break_char = "\n")
    {
        $lines = explode($break_char, $str);

        for ($i = 0; $i < count($lines) - 1; $i++) {
                $line = $lines[$i];
                $len = strlen($line);

                /* parse double-byte gb2312 characters */
                for ($c = 0; $c < $len - 1; $c++) {
                        if (ord($line{$c}) & 128) {
                                if (ord($line{$c + 1}) & 128) $c++;
                        }
                }

                /* If the last character of the current line is the first byte
                   of a double-byte character, move it to the start of the
                   next line. */
                if (($c == $len - 1) && (ord($line[$c]) & 128)) {
                        $lines[$i] = substr($line, 0, -1);
                        $lines[$i + 1] = $line[$c] . $lines[$i + 1];
                }
        }
        return implode($break_char, $lines);
    }

    /**
     * Wraps the text of a message.
     *
     * @param string $text        String containing the text to wrap.
     * @param integer $length     Wrap $text at this number of characters.
     * @param string $break_char  Character to use when breaking lines.
     *
     * @return string  String containing the wrapped text.
     */
    function wrap($text, $length = 80, $break_char = "\n", $charset = "")
    {
        $paragraphs = explode("\n", $text);

        $charset = strtolower($charset);
        switch ($charset) {
            case "gb2312":
                for ($i = 0; $i < count($paragraphs); $i++) {
                    $paragraphs[$i] = wordwrap($paragraphs[$i], $length, $break_char, 1);
                    $paragraphs[$i] = Text::trim_gb2312($paragraphs[$i], $break_char);
                }
                break;
            default:
                for ($i = 0; $i < count($paragraphs); $i++) {
                    $paragraphs[$i] = wordwrap(rtrim($paragraphs[$i]), $length, $break_char);
                }
                break;
        }
        return implode($break_char, $paragraphs);
    }

    /**
     * Turns all URLs in the text into hyperlinks.
     *
     * @param string $text               The text to be transformed.
     * @param optional boolean $capital  Sometimes it's useful to generate <A>
     *                                   and </A> so you can know which tags
     *                                   you just generated.
     * @param optional string $class     The CSS class the links should be
     *                                   displayed with.
     *
     * @return string  The linked text.
     */
    function linkUrls($text, $capital = false, $class = '')
    {
        if ($capital) {
            $a = 'A';
            $text = str_replace('</A>', '</a>', $text); // make sure that the original message doesn't contain any capital </A> tags, so we can assume we generated them
            $text = str_replace('<A', '<a', $text);     // ditto for open <A> tags
        } else {
            $a = 'a';
        }
        if (!empty($class)) {
            $class = ' class="' . $class . '"';
        }

        /* Get all possible URLs and store their position in the text. */
        preg_match_all('|(\w+)://([^\s"<]*[\w+#?/&=])|', $text, $matches, PREG_SET_ORDER);

        /* Loop through the text replacing all the matched URLs. */
        $offset = 0;
        foreach ($matches as $match) {
            $offset = strpos($text, $match[0], $offset);
            $url = Horde::addParameter(Horde::url($GLOBALS['registry']->getWebroot('horde') . '/util/go.php', false, -1), 'url=' . urlencode($match[0]));
            $new = '<' . $a . ' href="' . $url . '" target="_blank"' . $class . '>' . $match[0] . '</' . $a . '>';
            /* Replace URL with link using match offset. */
            $text = substr_replace($text, $new, $offset, strlen($match[0]));

            /* Increase offset to compensate for more characters in link. */
            $offset += (strlen($new) - strlen($match[0]));
        }

        return $text;
    }

    /**
     * Re-convert links generated by Text::linkUrls() to working
     * hrefs, after htmlspecialchars() has been called on the
     * text. This is an awkward chain, but necessary to filter out
     * other HTML.
     *
     * @param string $text             The text to convert.
     * @param optional string $target  The link target.
     *
     * @return string  The converted text.
     */
    function enableCapitalLinks($text, $target = '_blank')
    {
        $text = str_replace('&lt;A href=&quot;', '<a class="fixed" href="', $text);
        $text = str_replace('&quot; target=&quot;_blank&quot;&gt;', '" target="' . $target . '">', $text);
        $text = str_replace('&quot;&gt;','">', $text);
        $text = str_replace('&lt;/A&gt;', '</a>', $text); // only reconvert capital /A tags - the ones we generated

        return $text;
    }

    /**
     * Replace occurences of %VAR% with VAR, if VAR exists in the
     * webserver's environment. Ignores all text after a # character
     * (shell-style comments).
     *
     * @param string $text  The text to expand.
     *
     * @return string  The expanded text.
     */
    function expandEnvironment($text)
    {
        if (preg_match("|([^#]*)#.*|", $text, $regs)) {
            $text = $regs[1];

            if (strlen($text) > 0) {
                $text = $text . "\n";
            }
        }

        while (preg_match("|%([A-Za-z_]+)%|", $text, $regs)) {
            $text = preg_replace("|%([A-Za-z_]+)%|", getenv($regs[1]), $text);
        }
        return $text;
    }

    /**
     * Convert a line of text to display properly in HTML.
     *
     * @param string $text  The string of text to convert.
     *
     * @return string  The HTML-compliant converted text.
     */
    function htmlSpaces($text = '')
    {
        $text = htmlspecialchars($text);
        $text = str_replace("\t", '&nbsp; &nbsp; &nbsp; &nbsp; ', $text);
        $text = str_replace('  ', '&nbsp; ', $text);
        $text = str_replace('  ', ' &nbsp;', $text);

        return $text;
    }

    /**
     * Same as htmlSpaces() but converts all spaces to &nbsp;
     * @see htmlSpaces()
     *
     * @param string $text  The string of text to convert.
     *
     * @return string  The HTML-compliant converted text.
     */
    function htmlAllSpaces($text = '')
    {
        $text = Text::htmlSpaces($text);
        $text = str_replace(' ', '&nbsp;', $text);

        return $text;
    }

    /**
     * Removes some common entities and high-ascii or otherwise
     * nonstandard characters common in text pasted from Microsoft
     * Word into a browser.
     *
     * @param string $text  The text to be cleaned.
     *
     * @return string  The cleaned text.
     */
    function cleanEntities($text)
    {
        /* The '’' entry may look wrong, depending on your editor,
           but it's not - that's not really a single quote. */
        $from = array('…', '‘', '’', '“', '”', '•', '–', '—', 'Ÿ', '·', chr(167), '&#61479;', '&#61572;', '&#61594;', '&#61640;', '&#61623;', '&#61607;', '&#61558;', '&#9658;');
        $to   = array('...',     "'", "'",    '"',    '"',    '*',    '-',    '-',    '*', '*',      '*',        '.',        '*',        '*',        '-',        '-',        '*',        '*',       '>');

        return str_replace($from, $to, $text);
    }

    /**
     * Turn text into HTML with varying levels of parsing.
     *
     * @access public
     *
     * @param string $input            An url-decoded string, \n-separated for
     *                                 lines.
     * @param int $parselevel
     *  TEXT_HTML_PASSTHRU        =  No action. Pass-through. Included for
     *                               completeness.
     *  TEXT_HTML_SYNTAX          =  Allow full html, also do line-breaks,
     *                               in-lining, syntax-parsing.
     *  TEXT_HTML_REDUCED         =  Reduced html (bold, links, etc. by syntax
     *                               array).
     *  TEXT_HTML_MICRO           =  Micro html (only line-breaks, in-line
     *                               linking).
     *  TEXT_HTML_NOHTML          =  No html (all stripped, only line-breaks)
     *  TEXT_HTML_NOHTML_NOBREAK  =  No html whatsoever, no line breaks added.
     *                               Included for completeness.
     * For no html whatsoever, use htmlspecialchars()
     *
     * @access public
     *
     * @return string  The converted HTML.
     *
     * @since Horde 2.2
     */
    function toHTML($text, $parselevel)
    {
        $syntax = array('B' => '<b>',
                        '/B' => '</b>',
                        'I' => '<i>',
                        '/I' => '</i>',
                        'U' => '<u>',
                        '/U' => '</u>',
                        'Q'   => '<blockquote>',
                        '/Q' => '</blockquote>',
                        'LIST' => '<ul>',
                        '/LIST' => '</ul>',
                        '*' => '<li>');

        /* Abort out on simple cases. */
        if ($parselevel == TEXT_HTML_PASSTHRU) {
            return $text;
        }
        if ($parselevel == TEXT_HTML_NOHTML_NOBREAK) {
            return htmlspecialchars($text);
        }

        /* Tack on spaces so that we can count on whitespace coming before
           and after URLs and email addresses. */
        $text = ' ' . $text . ' ';

        /* Find tags we recognize with this parselevel and subst them to
           <tag> ==> [tag]
           and then subst the rest < --> &lt; > --> &gt; */
        if ($parselevel == TEXT_HTML_REDUCED) {
            foreach($syntax as $k => $val) {
                $text = str_replace('<' . $k . '>', '[' . $k . ']', $text);
                $k = strtolower($k);
                $text = str_replace('<' . $k . '>', '[' . $k . ']', $text);
            }
            $input = htmlspecialchars($input);
        }

        /* Interpret tags for parse levels TEXT_HTML_SYNTAX and
           TEXT_HTML_REDUCED. */
        if ($parselevel <= TEXT_HTML_REDUCED) {
            foreach($syntax as $k => $v) {
                $text = str_replace('[' . $k . ']', $v, $text);
                $text = str_replace('<' . $k . '>', $v, $text);
                $k = strtolower($k);
                $text = str_replace('[' . $k . ']', $v, $text);
                $text = str_replace('<' . $k . '>', $v, $text);
            }
        }

        /* For level TEXT_HTML_MICRO, TEXT_HTML_NOHTML, start with
           htmlspecialchars(). */
        if ($parselevel >= TEXT_HTML_MICRO) {
            $text = htmlspecialchars($text);
        }

        /* Do in-lining of http://xxx.xxx to link, xxx@xxx.xxx to email,
           part two. */
        if ($parselevel < TEXT_HTML_NOHTML) {
            // mailto
            $text = preg_replace('|(\s+)([\w\.\-]+\@[\w\-]+\.[\.\w]+)([^\.\w])|', '\1<a href="mailto:\2">\2</a>\3', $text);

            // urls
            $text = preg_replace('|(\s+)(\w+)://([^\s"<]*)([\w#?/&=])|', '\1<a href="\2://\3\4">\2://\3\4</a>', $text);
        }

        /* Do the blank-line ---> <br /> substitution.
           Everybody gets this; if you don't want even that, just save
           the htmlspecialchars() version of the input. */
        $text = nl2br($text);

        return trim($text);
    }

    /**
     * Highlights quoted messages with different colors for the different
     * quoting levels. CSS class names called "quoted1" .. "quoted$level"
     * must be present.
     *
     * @since Horde 2.2
     *
     * @access public
     *
     * @param string $text             The text to be highlighted.
     * @param optional integer $level  The maximum numbers of different
     *                                 colors.
     *
     * @return string  The highlighted text.
     */
    function highlightQuotes($text, $level = 5)
    {
        /* Use a global var since the class is called statically. */
        $GLOBALS['_tmp_maxQuoteChars'] = 0;

        preg_replace_callback("/^\s*((&gt;\s?)+)/m", array('Text', '_countQuoteChars'), $text);

        /* Go through each level of quote block and put the
           appropriate style around it. Important to work downwards so
           blocks with fewer quote chars aren't matched until their
           turn. */
        for ($i = $GLOBALS['_tmp_maxQuoteChars']; $i > 0; $i--) {
            $text = preg_replace(
                /* Finds a quote block across multiple newlines. */
                "/(\n)( *(&gt;\s?)\{$i}(?! ?&gt;).*?)(\n|$)(?! *(&gt; ?)\{$i})/s",
                '\1<span class="quoted' . ((($i - 1) % $level) + 1) . '">\2</span>\4',
                $text
            );
        }

        /* Unset the global variable. */
        unset($GLOBALS['_tmp_maxQuoteChars']);

        /* Remove the leading newline we added above. */
        return substr($text, 1);
    }

    /**
     * Called by the preg_replace_callback function in
     * highlightQuotes(). This method finds the maximum number of
     * quote characters in all of the quote blocks.
     *
     * @since Horde 2.2.4
     *
     * @access private
     *
     * @param array $matches  The matches from the regexp.
     */
    function _countQuoteChars($matches)
    {
        $num = count(preg_split('/&gt;\s?/', $matches[1])) - 1;
        if ($num > $GLOBALS['_tmp_maxQuoteChars']) {
            $GLOBALS['_tmp_maxQuoteChars'] = $num;
        }
    }

    /**
     * Displays message signatures marked by a '-- ' in the style of the CSS
     * class "signature". Class names inside the signature are prefixed with
     * "signature-".
     *
     * @param string $text  The text to be changed.
     *
     * @return string  The changed text.
     *
     * @since Horde 2.2
     */
    function dimSignature($text)
    {
        $parts = preg_split('|(\n--\s*(<br />)?\n)|', $text, 2, PREG_SPLIT_DELIM_CAPTURE);
        $text = array_shift($parts);
        if (count($parts)) {
            $text .= '<span class="signature">' . $parts[0];
            $text .= preg_replace('|class="([^"]+)"|', 'class="signature-\1"', $parts[2]);
            $text .= '</span>';
        }

        return $text;
    }

}
