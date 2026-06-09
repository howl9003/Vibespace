<?php

if (!extension_loaded('xml')) {
    die('The XML functions are not available. Rebuild PHP with --with-xml.');
}

/** @constant HELP_SOURCE_RAW Raw help in the string. */
define('HELP_SOURCE_RAW', 0);

/** @constant HELP_SOURCE_FILE Help text is in a file. */
define('HELP_SOURCE_FILE', 1);

/** @constant HELP_SOURCE_DB Help comes from a database. */
define('HELP_SOURCE_DB', 2);

/**
 * The Help:: class provides an interface to the online help subsystem.
 *
 * $Horde: horde/lib/Help.php,v 1.26.2.9 2003/01/03 12:48:37 jan Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Help {

    /**
     * Handle for the XML parser object.
     *
     * @var object $_parser
     */
    var $_parser = 0;

    /**
     * String buffer to hold the XML help source.
     *
     * @var string $_buffer
     */
    var $_buffer = '';

    /**
     * String containing the ID of the requested help entry.
     *
     * @var string $_reqEntry
     */
    var $_reqEntry = '';

    /**
     * String containing the ID of the current help entry.
     *
     * @var string $_curEntry
     */
    var $_curEntry = '';

    /**
     * String containing the formatted output.
     *
     * @var string $_output
     */
    var $_output = '';

    /**
     * Boolean indicating whether we're inside a <help> block.
     *
     * @var boolean $_inHelp
     */
    var $_inHelp = false;

    /**
     * Boolean indicating whether we're inside the requested block.
     *
     * @var boolean $_inBlock
     */
    var $_inBlock = false;

    /**
     * Boolean indicating whether we're inside a <title> block.
     *
     * @var boolean $_inTitle
     */
    var $_inTitle = false;

    /**
     * Hash containing an index of all of the help entries.
     *
     * @var array $_entries
     */
    var $_entries = array();

    /**
     * Hash of user-defined function handlers for the XML elements.
     *
     * @var array $_handlers
     */
    var $_handlers = array(
        'HELP' =>       'helpHandler',
        'ENTRY' =>      'entryHandler',
        'TITLE' =>      'titleHandler',
        'HEADING' =>    'headingHandler',
        'PARA' =>       'paraHandler',
        'REF' =>        'refHandler',
        'EREF' =>       'erefHandler'
    );


    /**
     * Constructor
     *
     * @access public
     *
     * @param integer $source       The source of the XML help data, based
     *                              on the HELP_SOURCE_* constants.
     * @param optional string $arg  Source-dependent argument for this Help
     *                              instance.
     */
    function Help($source, $arg = null)
    {
        /* Populate $this->_buffer based on $source. */
        switch ($source) {
        case HELP_SOURCE_RAW:
            $this->_buffer = $arg;
            break;

        case HELP_SOURCE_FILE:
            if (!(@file_exists($arg[0]) && ($fp = @fopen($arg[0], 'r')) && ($fs = @filesize($arg[0])) ||
                 @file_exists($arg[1]) && ($fp = @fopen($arg[1], 'r')) && ($fs = @filesize($arg[1])))) {
                $this->_buffer = '';
            } else {
                $this->_buffer = fread($fp, $fs);
                fclose($fp);
            }
            break;

        default:
            $this->_buffer = '';
            break;
        }
    }

    /**
     * Initialzes the XML parser.
     *
     * @access public
     *
     * @return boolean  Returns true on success, false on failure.
     */
    function init()
    {
        /* Create a new parser and set its default properties. */
        $this->_parser = xml_parser_create();
        xml_set_object($this->_parser, $this);
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($this->_parser, 'startElement', 'endElement');
        xml_set_character_data_handler($this->_parser, 'defaultHandler');

        return ($this->_parser != 0);
    }

    /**
     * Cleans up the Help class resources.
     *
     * @access public
     *
     * @return boolean  Returns true on success, false on failure.
     */
    function cleanup()
    {
        $this->_buffer = '';
        return xml_parser_free($this->_parser);
    }

    /**
     * Looks up the requested entry in the XML help buffer.
     *
     * @access public
     *
     * @param string $entry  String containing the entry ID.
     */
    function lookup($entry)
    {
        $this->_output = '';
        $this->_reqEntry = strtoupper($entry);
        if (!$this->_parser) {
            $this->init();
        }
        xml_parse($this->_parser, $this->_buffer, true);
    }

    /**
     * Returns a hash of all of the topics in this help buffer.
     *
     * @access public
     *
     * @return array  Hash of all of the topics in this buffer.
     */
    function topics()
    {
        if (!$this->_parser) {
            $this->init();
        }
        xml_parse($this->_parser, $this->_buffer, true);

        return $this->_entries;
    }

    /**
     * Display the contents of the formatted output buffer.
     */
    function display()
    {
        echo $this->_output;
    }

    /**
     * User-defined function callback for start elements.
     *
     * @access public
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     * @param array $attrs    List of this element's attributes.
     */
    function startElement($parser, $name, $attrs)
    {
        /* Call the assigned handler for this element, if one is available. */
        if (in_array($name, array_keys($this->_handlers))) {
            call_user_func(array(&$this, $this->_handlers[$name]), true, $attrs);
        }
    }

    /**
     * User-defined function callback for end elements.
     *
     * @access public
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     */
    function endElement($parser, $name)
    {
        /* Call the assigned handler for this element, if one is available. */
        if (in_array($name, array_keys($this->_handlers))) {
            call_user_func(array(&$this, $this->_handlers[$name]), false);
        }
    }

    /**
     * User-defined function callback for character data.
     *
     * @access public
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $data    String of character data.
     */
    function defaultHandler($parser, $data)
    {
        if ($this->_inTitle) {
            $this->_entries[$this->_curEntry] .= $data;
        }
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= htmlspecialchars($data);
        }
    }

    /**
     * XML element handler for the <help> tag.
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes (Not used).
     */
    function helpHandler($startTag, $attrs = array())
    {
        $this->_inHelp = ($startTag) ? true : false;
    }

    /**
     * XML element handler for the <entry> tag.
     * Attributes: id
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes.
     */
    function entryHandler($startTag, $attrs = array())
    {
        if (!$startTag) {
            $this->_inBlock = false;
        } else {
            $id = strtoupper($attrs['ID']);
            $this->_curEntry = $id;
            $this->_entries[$id] = '';
            $this->_inBlock = ($id == $this->_reqEntry);
        }
    }

    /**
     * XML element handler for the <title> tag.
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes (Not used).
     */
    function titleHandler($startTag, $attrs = array())
    {
        $this->_inTitle = $startTag;
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= ($startTag) ? '<h3>' : '</h3>';
        }
    }

    /**
     * XML element handler for the <heading> tag.
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes (Not used).
     */
    function headingHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= ($startTag) ? '<h4>' : '</h4>';
        }
    }

    /**
     * XML element handler for the <para> tag.
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes (Not used).
     */
    function paraHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= ($startTag) ? '<p>' : '</p>';
        }
    }

    /**
     * XML element handler for the <ref> tag.
     * Required attributes: ENTRY, MODULE
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes.
     */
    function refHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            if ($startTag) {
                $url = Horde::addParameter(Horde::selfURL(), 'show=entry');
                $url = Horde::addParameter($url, 'module=' . $attrs['MODULE']);
                $url = Horde::addParameter($url, 'topic=' . $attrs['ENTRY']);
                $this->_output .= Horde::link($url, null, 'helplink');
            } else {
                $this->_output .= '</a>';
            }
        }
    }

    /**
     * XML element handler for the <eref> tag.
     * Required elements: URL
     *
     * @access public
     *
     * @param boolean $startTag      Boolean indicating whether this instance
     *                               is a start tag.
     * @param optional array $attrs  Additional element attributes.
     */
    function erefHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            if ($startTag) {
                $this->_output .= Horde::link($attrs['URL'], null, 'helplink', '_blank');
            } else {
                $this->_output .= '</a>';
            }
        }
    }

    /**
     * Includes the JavaScript necessary to create a new pop-up help window.
     *
     * @access public
     */
    function javascript()
    {
        global $registry;

        include_once $registry->getParam('templates', 'horde') . '/javascript/open_help_win.js';
    }

    /**
     * Generates the HTML link that will pop up a help window for the
     * requested topic.
     *
     * @param string $module  The name of the current Horde module.
     * @param string $topic   The help topic to be displayed.
     *
     * @return string  The HTML to create the help link.
     */
    function link($module, $topic)
    {
        global $registry;

        $html = Horde::link('', _("Help"), '', '', "open_help_win('$module', '$topic'); return false;");
        $html .= Horde::img('help.gif', 'alt="' . _("Help") . '" width="12" height="17"', $registry->getParam('graphics', 'horde')) . '</a>';

        return $html;
    }

    /**
     * Generates the URL that will pop up a help window for the list
     * of topics.
     *
     * @access public
     *
     * @param string $module  The name of the current Horde module.
     *
     * @return string  The HTML to create the help link.
     */
    function listLink($module)
    {
        return "javascript:open_help_win('$module');";
    }

}
