<?php
/*
 * $Horde: horde/lib/CategoryTree.php,v 1.4.2.7 2003/01/03 12:48:37 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * Class for drawing a hierarchical tree structure from a category
 * export (CATEGORY_FORMAT_3D). Icons, styles, etc. can all be
 * defined.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.category
 */
class CategoryTree {

    /** The category object to draw the tree for.
        @object Category $category */
    var $category;

    /** The template path to use.
        @string $templates */
    var $templates;

    /** The default image for any elements of the tree which don't
        have an image defined.
        @var string $image */
    var $image = 'group.gif';

    /** An array of images, indexed by element name, to be displayed
        for specific tree elements.
        @var array $images */
    var $images = array();

    /** The default CSS style name for any table rows which don't have
        a CSS style defined.
        @var string $style */
    var $style = 'text';

    /** An array of CSS styles, indexed by element name, to be
        displayed for specific tree elements.
        @var array $styles */
    var $styles = array();

    /** The default CSS link style name for any table rows which don't have
        a CSS style defined.
        @var string $linkstyle */
    var $linkstyle = 'text';

    /** An array of CSS link styles, indexed by element name, to be
        displayed for specific tree elements.  @var array $linkstyles
        */
    var $linkstyles = array();

    /** The default action for any element which doesn't have specific
        actions defined. This is a hash with two elements, 'text' for
        the link text, and 'url' for the link URL.
        @var array $action */
    var $action = null;

    /** An array of actions, indexed by element name, to be displayed
        for specific tree elements. These follow the same format as
        the default action.
        @see $action
        @var array $actions */
    var $actions = array();

    function CategoryTree(&$category, $templates)
    {
        if (isset($category)) {
            $this->category = $category;
        }
        $this->templates = $templates;
    }

    function setCategory(&$category)
    {
        $this->category = &$category;
    }

    function setImage($image, $element = null)
    {
        if (isset($element)) {
            $this->images[$element] = $image;
        } else {
            $this->image = $image;
        }
    }

    function setStyle($style, $element = null)
    {
        if (isset($element)) {
            $this->styles[$element] = $style;
        } else {
            $this->style = $style;
        }
    }

    function setLinkStyle($linkstyle, $element = null)
    {
        if (isset($element)) {
            $this->linkstyles[$element] = $linkstyle;
        } else {
            $this->linkstyle = $linkstyle;
        }
    }

    function setAction($action, $element = null)
    {
        if (isset($element)) {
            $this->actions[$element] = $action;
        } else {
            $this->action = $action;
        }
    }

    function draw($root = '-1', $maxi = null, $maxj = null)
    {
        $data = $this->category->export(CATEGORY_FORMAT_3D, $root);
        if (count($data) == 0) {
            $data = array(0 => array(0 => array('p' => '0.0',
                                                'name' => 'root')),
                          'x' => 0,
                          'y' => 0);
        }

        if (!isset($maxi)) {
            $maxi = $data['x'];
        }
        if (!isset($maxj)) {
            $maxj = $data['y'];
        }

        $imageGrid[0][0] = 'line';
        for ($n = $maxj; $n >= 0; $n--) {
            for ($m = $maxi; $m >= 0; $m--) {
                if (!empty($data[$m][$n])) {
                    // Something here
                    $imageGrid[$m][$n] = null;
                } else {
                    // Empty by default
                    $imageGrid[$m][$n] = 'blank';

                    // Picture on the right?
                    if ($m < $maxi) {
                        if (!empty($data[$m + 1][$n])) { // Picture on the right
                            $imageGrid[$m][$n] = 'joinbottom';
                            if ($n < $maxj) {
                                if (!empty($imageGrid[$m][$n + 1]) && $imageGrid[$m][$n + 1] !== 'blank') { // Picture on the right and line below
                                    $imageGrid[$m][$n] = 'join';
                                }
                            }
                        } else { // Nothing on the right
                            if ($n < $maxj) {
                                if (!empty($imageGrid[$m][$n + 1]) && $imageGrid[$m][$n + 1] !== 'blank') { // Nothing on the right and line below
                                    $imageGrid[$m][$n] = 'line';
                                }
                            }
                        }
                    }
                }
            }
        }

        include $this->templates . '/tablehead.inc';
        for ($n = 0; $n <= $maxj; $n++)	{
            $curRow = -1;
            include $this->templates . '/rowstart.inc';
            for ($m = 0; $m <= $maxi; $m++) {
                $style = !@empty($this->styles[$data[$m][$n]['name']]) ? $this->styles[$data[$m][$n]['name']] : $this->style;
                if (!isset($imageGrid[$m][$n])) {
                    echo '<td class="' . $style . '">';
                    if (!empty($this->images[$data[$m][$n]['name']])) {
                        Horde::pimg($this->images[$data[$m][$n]['name']]);
                    } else {
                        Horde::pimg($this->image);
                    }
                    echo '</td><td valign="center" class="' . $style . '">&nbsp;';
                    echo $data[$m][$n]['name'];
                    $curRow = $m;
                    $m = $maxi + 1;
                } else {
                    echo '<td>' . Horde::img('tree/' . $imageGrid[$m][$n] . '.gif', 'hspace="0" vspace="0" width="20" height="20"');
                }
                echo '</td>';
            }

            $actions = isset($this->actions[$data[$curRow][$n]['name']]) ? $this->actions[$data[$curRow][$n]['name']] : $this->action;
            if (!empty($actions) && is_array($actions) && count($actions) > 0) {
                $linktext = '';
                $linkstyle = !empty($this->linkstyles[$data[$curRow][$n]['name']]) ? $this->linkstyles[$data[$curRow][$n]['name']] : $this->linkstyle;
                foreach ($actions as $action) {
                    if (!empty($linktext)) {
                        $linktext .= ' | ';
                    }
                    $link = Horde::url(Horde::addParameter($action['url'], 'category=' . urlencode($data[$curRow][$n]['name'])));
                    $linktext .= Horde::link($link, $action['text'], $linkstyle) . $action['text'] . '</a>';
                }
            } else {
                $linktext = '';
            }
            include $this->templates . '/rowend.inc';
        }
        include $this->templates . '/tablefoot.inc';
    }

}
?>
