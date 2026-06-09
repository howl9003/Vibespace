<?php
/**
 * This class defines the Horde_Image:: API, and also provides some
 * utility functions, such as generating highlights of a color.
 *
 * $Horde: horde/lib/Image.php,v 1.1.2.1 2003/02/27 17:53:44 chuck Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 3.0
 * @package horde.image
 */
class Horde_Image {

    /**
     * Calculate a lighter (or darker) version of a color.
     *
     * @param string $color    An HTML color, e.g.: #ffffcc.
     *
     * @return string  A modified HTML color.
     */
    function modifyColor($color, $factor = 0x11)
    {
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

        if ($r >= $g && $r >= $b) {
            $g = $g / $r;
            $b = $b / $r;

            $r = $r + $factor;
            $g = floor($g * $r);
            $b = floor($b * $r);
        } elseif ($g >= $r && $g >= $b) {
            $r = $r / $g;
            $b = $b / $g;

            $g = $g + $factor;
            $r = floor($r * $g);
            $b = floor($b * $g);
        } else {
            $r = $r / $b;
            $g = $g / $b;

            $b = $b + $factor;
            $r = floor($r * $b);
            $g = floor($g * $b);
        }

        return '#' . str_pad(dechex(min($r, 255)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min($g, 255)), 2, '0', STR_PAD_LEFT) . str_pad(dechex(min($b, 255)), 2, '0', STR_PAD_LEFT);
    }

}
