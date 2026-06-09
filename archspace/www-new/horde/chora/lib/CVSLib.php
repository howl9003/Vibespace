<?php
/**
 * CVSLib base class.
 *
 * See the README file that came with this library for more
 * information, and read the inline documentation.
 *
 * Copyright 2000-2003 Anil Madhavapeddy, <anil@recoil.org>
 *
 * $Horde: chora/lib/CVSLib.php,v 1.73.2.6 2003/01/03 12:48:47 jan Exp $
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib {

    var $cvsusers;

    function CVSLib()
    {
    }

    /**
      * Return what class this is for identification purposes
      * @return CVSLIB_REPOSITORY constant
      */
    function id()
    {
        return CVSLIB_REPOSITORY;
    }

    /**
      * Return the CVSROOT for this repository, with no trailing /
      * @return CVSROOT for this repository
      */
    function cvsRoot()
    {
        return $GLOBALS['conf']['paths']['cvsRoot'];
    }

    function queryDir($where) {
        $dir = new CVSLib_directory($this, $where);
        return $dir;
    }

    /*
     * Parse the 'cvsusers' file, if present in the CVSROOT, and return a
     * hash containing the requisite information, keyed on the username, and
     * with the 'desc','name', and 'mail' values inside.
     * @return false if the file is not present, otherwise populate $this->cvsusers with the data
     */
    function parseCVSUsers() {
        /* Check that we haven't already parsed cvsusers */
        if (isset($this->cvsusers) && is_array($this->cvsusers)) return true;

        /* Try to locate the cvsusers file, and test to see if it is there */
        $cvsfile = $GLOBALS['conf']['paths']['cvsusers'];

        if (!@is_file($cvsfile) || !($fl = @fopen($cvsfile, OS_WINDOWS ? 'rb' : 'r'))) {
            return false;
        }

        $this->cvsusers = array();

        /* Discard the first line, since it'll be the header info */
        fgets($fl, 4096);

        /* Parse the rest of the lines into a hash, keyed on username */
        while ($line = fgets($fl, 4096)) {
            if (preg_match('/^\s*$/', $line)) continue;
            if (!preg_match('/^(\w+)\s+(.+)\s+([\w\.\-\_]+@[\w\.\-\_]+)\s+(.*)$/', $line, $regs)) continue;
            $this->cvsusers[$regs[1]]['name'] = trim($regs[2]);
            $this->cvsusers[$regs[1]]['mail'] = trim($regs[3]);
            $this->cvsusers[$regs[1]]['desc'] = trim($regs[4]);
        }

        return true;
    }

}

require_once dirname(__FILE__) . '/CVSLib/Checkout.php';
require_once dirname(__FILE__) . '/CVSLib/Log.php';
require_once dirname(__FILE__) . '/CVSLib/File.php';
require_once dirname(__FILE__) . '/CVSLib/Directory.php';
require_once dirname(__FILE__) . '/CVSLib/Diff.php';
require_once dirname(__FILE__) . '/CVSLib/Annotate.php';
require_once dirname(__FILE__) . '/CVSLib/Rev.php';
require_once dirname(__FILE__) . '/CVSLib/Error.php';

?>
