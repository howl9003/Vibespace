<?php
/*
 * $Horde: horde/lib/Menu.php,v 1.12.2.4 2003/01/03 12:48:37 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Menu:: class provides standardized methods for creating menus in
 * Horde applications.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Menu {

    /**
     * Generates the HTML for an item on the menu bar.
     *
     * @param string $url       String containing the value for the hyperlink.
     * @param string $text      String containing the label for this menu item.
     * @param optional string $icon   String containing the filename of the image
     *                                icon to display for this menu item.
     * @param optional string $icon_path  If the icon lives in a non-default
     *                                    directory, where is it?
     * @param optional string $target     If the link needs to open in another frame
     *                                    or window, what is its name?
     * @param optional string $onclick    Onclick javascript, if desired.
     * @param optional string $cell_class CSS class for the table cell.
     * @param optional string $link_class CSS class for the item link.
     *
     * @return  string  String containing the HTML to display this menu item.
     */
    function createItem($url, $text, $icon = '', $icon_path = null, $target = '', $onclick = null, $cell_class = null, $link_class = 'menuitem')
    {
        global $conf, $prefs;

        $html = '<td align="center" nowrap="nowrap" valign="';
        $html .= ($prefs->getValue('menu_view') == 'icon') ? 'middle' : 'bottom';
        $html .= '"';
        $html .= (!empty($cell_class)) ? " class=\"$cell_class\">" : '>';
        $html .= Horde::link($url, $text, $link_class, $target, $onclick);

        if (!empty($icon) && ($prefs->getValue('menu_view') == 'icon' || $prefs->getValue('menu_view') == 'both')) {
            $html .= Horde::img($icon, "alt=\"$text\"" . ($prefs->getValue('menu_view') == 'icon' ? 'hspace="5" vspace="5"' : ''), $icon_path);
            if ($prefs->getValue('menu_view') == 'both') {
                $html .= '<br />';
            }
        }

        if ($prefs->getValue('menu_view') != 'icon') {
            $html .= $text;
        }

        $html .= "</a>&nbsp;</td>\n";

        return $html;
    }

    /**
     * Prints the result of the createItem() function.
     *
     * @param string $url       String containing the value for the hyperlink.
     * @param string $text      String containing the label for this menu item.
     * @param optional string $icon   String containing the filename of the image
     *                                icon to display for this menu item.
     * @param optional string $icon_path  If the icon lives in a non-default
     *                                    directory, where is it?
     * @param optional string $target     If the link needs to open in another frame
     *                                    or window, what is its name?
     * @param optional string $onclick    Onclick javascript, if desired.
     * @param optional string $cell_class CSS class for the table cell.
     * @param optional string $link_class CSS class for the item link.
     */
    function printItem($url, $text, $icon = '', $icon_path = null, $target = '', $onclick = null, $cell_class = null, $link_class = 'menuitem')
    {
        echo Menu::createItem($url, $text, $icon, $icon_path, $target, $onclick, $cell_class, $link_class);
    }

    /**
     * Creates a menu string from a custom menu item.  Custom menu items
     * can either define a new menu item or a menu separate (spacer).
     *
     * A custom menu item consists of a hash with the following properties:
     *
     *  'url'       The URL value for the menu item.
     *  'text'      The text to accompany the menu item.
     *  'icon'      The filename of an icon to use for the menu item.
     *  'icon_path' The path to the icon if it doesn't exist in the graphics/
     *              directory.
     *  'target'    The "target" of the link (e.g. '_top', '_blank').
     *  'onclick'   Any onclick javascript.
     *
     * A menu separator item is simply a string set to 'separator'.
     *
     * @param $item     Mixed parameter containing the custom menu item.
     *
     * @return string   The resulting HTML to display the menu item.
     */
    function customItem($item)
    {
        global $conf;

        $text = '';

        if (is_array($item)) {
            $text = Menu::createItem($item['url'], $item['text'],
                                     @$item['icon'], @$item['icon_path'],
                                     @$item['target'], @$item['onclick']);
        } else {
            if (strcasecmp($item, 'separator') == 0) {
                $text = '<td>&nbsp;</td>';
            }
        }

        return $text;
    }

}
?>
