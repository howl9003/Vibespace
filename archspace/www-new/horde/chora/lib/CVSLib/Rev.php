<?php
/**
 * CVSLib revisions class.
 *
 * See the README file that came with this library for more
 * information, and read the inline documentation.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * $Horde: chora/lib/CVSLib/Rev.php,v 1.7.2.1 2002/10/06 12:23:52 jan Exp $
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_Rev {

    /**
     * Validation function to ensure that a revision number is of the
     * right form.
     *
     * @param val Value to check.
     *
     * @return boolean true if it is a revision number
     */
    function valid($val)
    {
        return $val && preg_match('/^[\d\.]+$/' , $val);
    }

    /**
     * Given a revision number, remove a given number of portions from
     * it. For example, if we remove 2 portions of 1.2.3.4, we are
     * left with 1.2.
     *
     * @param val input revision
     * @param amount number of portions to strip
     *
     * @return stripped revision number
     */
    function strip($val, $amount)
    {
        if (!CVSLib_Rev::valid($val)) {
            return false;
        }
        $pos = 0;
        while ($amount-- > 0 && ($pos = strrpos($val, '.')) !== false) {
            $val = substr($val, 0, $pos);
        }
        return $pos !== false ? $val : false;
    }

    /**
     * The size of a revision number is the number of portions it has.
     * For example, 1,2.3.4 is of size 4.
     *
     * @param input revision number to determine size of
     * @param size of revision number
     */
    function sizeof($val)
    {
        if (!CVSLib_Rev::valid($val)) {
            return false;
        }

        return (substr_count($val, '.') + 1);
    }

    /**
     * Given a valid revision number, this will return the revision
     * number from which it branched. If it cannot be determined, then
     * false is returned.
     *
     * @param input revision number
     *
     * @return branch point revision, or false
     */
    function branchPoint($val)
    {
        /* Check if we have a valid revision number */
        if (!CVSLib_Rev::valid($val)) { 
            return false;
        }

        /* If its on the trunk, or is an odd size, ret false */
        if (CVSLib_Rev::sizeof($val) < 3 || (CVSLib_Rev::sizeof($val) % 2)) {
            return false;
        }

        /* Strip off two revision portions, and return it */
        return CVSLib_Rev::strip($val, 2); 
    }

    /**
     * Given two CVS revision numbers, this figures out which one is
     * greater than the other by stepping along the decimal points
     * until a difference is found, at which point a sign comparison
     * of the two is returned.
     *
     * @param rev1 Period delimited revision number
     * @param rev2 Second period delimited revision number
     *
     * @return 1 if the first is greater, -1 if the second if greater,
     *         and 0 if they are equal
     */
    function cmp($rev1, $rev2)
    {
        return version_compare($rev1, $rev2);
    }

    /**
     * Return the logical revision before this one. Normally, this
     * will be the revision minus one, but in the case of a new
     * branch, we strip off the last two decimal places to return the
     * original branch point.
     *
     * @param $rev Revision number to decrement
     *
     * @return revision number, or false if none could be determined
     */
    function prev($rev)
    {
        $last_dot = strrpos($rev, '.');
        $val = substr($rev, ++$last_dot);

        if (--$val > 0) {
            return substr($rev, 0, $last_dot) . $val;
        } else {
            $last_dot--;
            while (--$last_dot) {
                if ($rev[$last_dot] == '.') {
                    return  substr($rev, 0, $last_dot);
                } else if ($rev[$last_dot] == null) {
                    return false;
                }
            }
        }
    }

    /**
     * Given a revision number of the form x.y.0.z, this remaps it
     * into the appropriate branch number, which is x.y.z
     *
     * @param $rev Even-digit revision number of a branch
     *
     * @return Odd-digit Branch number
     */
    function toBranch($rev)
    {
        /* Check if we have a valid revision number */
        if (!CVSLib_Rev::valid($rev)) {
            return false;
        }

        if (($end = strrpos($rev, '.')) === false) {
            return false;
        }

        $rev[$end] = 0;
        if (($end2 = strrpos($rev, '.')) === false) {
            return substr($rev, ++$end);
        }

        return substr_replace($rev, '.', $end2, ($end-$end2+1));
    }

}
