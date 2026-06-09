<?php

/*  See the README file that came with this library for more
 *  information, and read the inline documentation.
 *
 *  Anil Madhavapeddy, <anil@recoil.org>
 *  $Horde: chora/lib/CVSLib/Checkout.php,v 1.16.2.2 2002/10/06 12:23:52 jan Exp $
 */

/**
 * CVSLib checkout class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_Checkout {

    /**
      * Static function which returns a file pointing to the head of the requested
      * revision of an RCS file.
      * @param CVS CVSLib object of the desired CVS repository
      * @param fullname Fully qualified pathname of the desired RCS file to checkout
      * @param rev RCS revision number to check out
      * @return Either a CVSLib_Error object, or a stream pointer to the head of the checkout
      */
    function get(&$CVS, $fullname, $rev) {
        if (!CVSLib_Rev::valid($rev)) {
            return new CVSLib_Error(CVSLIB_INTERNAL_ERROR, _("Invalid revision number"));
        }

        if (OS_WINDOWS) {
            $Q    = '"';
            $mode = 'rb';
        } else {
            $Q    = "'";
            $mode = 'r';
        }

        if (!($RCS = popen($GLOBALS['conf']['paths']['co'] . " -p$rev $Q$fullname$Q 2>&1", $mode))) {
            return new CVSLib_Error(CVSLIB_INTERNAL_ERROR,
                      _("Couldn't perform checkout of the requested file"));
        }

        /* First line from co should be of the form :
         * /path/to/filename,v  -->  standard out
         * and we check that this is the case and error otherwise
         */

        $co = fgets($RCS, 1024);
        if (!preg_match('/^([\S ]+),v\s+-->\s+st(andar)?d ?out(put)?\s*$/', $co, $regs) || $regs[1] != $fullname) {
            return new CVSLib_Error(CVSLIB_INTERNAL_ERROR, "Unexpected output from CVS Checkout: $co");
        }

        /*
         * Next line from co is of the form:
         * revision 1.2.3
         * TODO: compare this to $rev for consistency, atm we just
         *       discard the value to move input pointer along - avsm
         */

        $co = fgets($RCS, 1024);

        return $RCS;
    }

    /**
     * Pretty-print the checked out copy, using the Horde::Mime::Viewer package.
     *
     * @param object $CVS CVSLib object of the desired CVS repository
     * @param string ext File extension of the checked out file
     * @param resource fp File pointer to the head of the checked out copy
     * @return object The MIME_Viewer object which can be rendered or
     *                false on failure
     */
    function &pretty(&$CVS, $ext, $fp) {
        $lns = '';
        while ($ln = fread($fp, 4096)) {
           $lns .= $ln;
        }

        $mime_type = MIME_Magic::extToMIME($ext);
        if (!isset($mime_type)) {
            return false;
        }

        $mime = new MIME_Part($mime_type, $lns);
        return MIME_Viewer::factory($mime);
    }
}

?>
