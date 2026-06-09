<?php

/*  See the README file that came with this library for more
 *  information, and read the inline documentation.
 *
 *  Anil Madhavapeddy, <anil@recoil.org>
 *  $Horde: chora/lib/CVSLib/Annotate.php,v 1.7.2.5 2004/05/17 09:15:41 jan Exp $
 */

/**
 * CVSLib annotate class.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.2
 * @package chora
 */
class CVSLib_Annotate {
    var $file, $CVS, $tmpfile;

    function CVSLib_Annotate(&$rep, $file) {
        $this->CVS = &$rep;
        $this->file = &$file;
    }

    function doAnnotate($rev)
    {
        global $conf;

        /* Make sure that the file values for this object is valid */
        if (!is_object($this->file) || $this->file->id() != CVSLIB_FILE) {
            return false;
        }

        /* Make sure that the cvsrep parameter is valid */
        if (!is_object($this->CVS) || $this->CVS->id() != CVSLIB_REPOSITORY) {
            return false;
        }

        if (!CVSLib_Rev::valid($rev)) {
            return false;
        }

        $where = $this->file->queryModulePath();
        $cvsroot = $this->CVS->cvsRoot();

        $this->tmpfile = Horde::getTempFile('chora');

// l method not supported
//        $pipe = popen($conf['paths']['cvs'] . ' -n -l server > ' . $this->tmpfile, OS_WINDOWS ? 'wb' : 'w');
        $pipe = popen('cvs -n server > ' . $this->tmpfile, OS_WINDOWS ? 'wb' : 'w');

        $out = array();
// yeah...my root is definitely not that...
//        $out[] = "Root $cvsroot";
	$out[] = "Root /var/cvsroot";
        $out[] = 'Valid-responses ok error Valid-requests Checked-in Updated Merged Removed M E';
        $out[] = 'UseUnchanged';
        $out[] = 'Argument -r';
        $out[] = "Argument $rev";
        $out[] = "Argument $where";
        $dirs = explode('/', dirname($where));
        while (sizeof($dirs)) {
            $out[] = 'Directory ' . implode('/', $dirs);
            $out[] = "$cvsroot/" . implode('/', $dirs);
            array_pop($dirs);
        }
        $out[] = 'Directory .';
        $out[] = "$cvsroot";
        $out[] = 'annotate';

        foreach ($out as $line) {
// debugging
//	    echo '<font color=white>'.$line.'<br>';
            fwrite($pipe, "$line\n");
        }

        pclose($pipe);

        if (!($fl = fopen($this->tmpfile, OS_WINDOWS ? 'rb' : 'r'))) {
            exit;
        }

        $lines = array();
        $line = fgets($fl, 4096);

        // Windows versions of cvs always return $where with forwards slashes.
        if (OS_WINDOWS) {
            $where = str_replace(DIRECTORY_SEPARATOR, '/', $where);
        }

        while ($line && !preg_match("|^E\s+Annotations for $where|", $line)) {
            $line = fgets($fl, 4096);
        }

        if (!$line) {
            return new CVSLib_Error(CVSLIB_INTERNAL_ERROR, "Unable to annotate; server said: $line");
        }

        $lineno = 1;
        while($line = fgets($fl, 4096)) {
            if (preg_match('/^M\s+([\d\.]+)\s+\((.+)\s+(\d+-\w+-\d+)\):.(.*)$/', $line, $regs)) {
                $entry = array();
                $entry['rev']    = $regs[1];
                $entry['author'] = $regs[2];
                $entry['date']   = $regs[3];
                $entry['line']   = $regs[4];
                $entry['lineno'] = $lineno++;
                $lines[] = $entry;
            }
        }

        fclose($fl);
        return $lines;
}

    /**
      * Return what class this is for identification purposes
      * @return CVSLIB_ANNOTATE constant
      */
    function id()
    {
        return CVSLIB_ANNOTATE;
    }

}

?>
