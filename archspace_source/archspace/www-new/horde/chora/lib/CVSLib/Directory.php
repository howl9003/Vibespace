<?php
/**
 * CVSLib directory class.
 *
 * See the README file that came with this library for more
 * information, and read the inline documentation.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * $Horde: chora/lib/CVSLib/Directory.php,v 1.16.2.3 2004/04/15 19:24:12 chuck Exp $
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_Directory {

    var $dirName, $rep, $files, $atticFiles, $mergedFiles, $dirs, $parent, $moduleName;

    /**
     * Create a CVS Directory object to store information
     * about the files in a single directory in the repository
     *
     * @param object CVSLib_Repository $rp  The CVSLib_Repository object this directory is part of.
     * @param string                   $dn  Path to the directory.
     * @param object CVSLib_Directory  $pn  (optional) The parent CVSLib_Directory to this one.
     */
    function CVSLib_Directory(&$rp, $dn, $pn = '')
    {
        $this->parent = &$pn;
        $this->rep = &$rp;
        $this->moduleName = $dn;
        $this->dirName = $rp->cvsRoot() . "/$dn";
        $this->files = array();
        $this->dirs = array();
    }

    /**
     * Return what class this is for identification purposes.
     *
     * @return CVSLIB_DIRECTORY constant
     */
    function id()
    {
        return CVSLIB_DIRECTORY;
    }

    /**
     * Return fully qualified pathname to this directory with no
     * trailing /.
     *
     * @return Pathname of this directory
     */
    function queryDir()
    {
        return $this->dirName;
    }

    function &queryDirList()
    {
        reset($this->dirs);
        return $this->dirs;
    }

    function &queryFileList($flags = CVSLIB_ATTIC_HIDE)
    {
        if ($flags == CVSLIB_ATTIC_SHOW && isset($this->mergedFiles)) {
            return $this->mergedFiles;
        } else {
            return $this->files;
        }
    }

    /**
     * Tell the object to open and browse its current directory, and
     * retrieve a list of all the objects in there.  It then populates
     * the file/directory stack and makes it available for retrieval.
     *
     * @return CVSLib_Error object on an error, 1 on success.
     */
    function browseDir($flags = CVSLIB_LOG_QUICK, $attic = CVSLIB_ATTIC_HIDE)
    {
        /* Make sure we are trying to list a directory */
        if (!@is_dir($this->dirName)) {
            return new CVSLib_Error(CVSLIB_NOT_FOUND, "Unable to find directory");
        }

        /* Open the directory for reading its contents */
        if (!($DIR = @opendir($this->dirName))) {
            global $where;
            $errmsg = (!empty($php_errormsg)) ? $php_errormsg : 'Permission Denied';
            return new CVSLib_Error(CVSLIB_PERMISSION_DENIED, "$where: $errmsg");
        }

        /* Create two arrays - one of all the files, and the other of all the dirs */
        while (($name = readdir($DIR)) !== false) {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $path = $this->dirName . '/' . $name;
            if (@is_dir($path)) {
                /* Skip Attic directory */
                if ($name != 'Attic') {
                    $this->dirs[] = $name;
                }
            } else if (@is_file($path) && substr($name, -2) == ',v') {
                /* Spawn a new file object to represent this file */
                $fl = &CVSLib_File::getFileObject($this->rep, substr($path, 0, -2), $flags);
                if ($fl->id() == CVSLIB_FILE) {
                    $this->files[] = $fl;
                }
            }
        }

        /* Close the filehandle; we've now got a list of dirs and files */
        closedir($DIR);

        /* If we want to merge the attic, add it in here */
        if ($attic == CVSLIB_ATTIC_SHOW) {
            $atticDir = new CVSLib_Directory($this->rep, $this->moduleName . '/Attic', $this);
            if ($atticDir->browseDir($flags, CVSLIB_ATTIC_HIDE) == 1) {
                $this->atticFiles =& $atticDir->queryFileList();
                $this->mergedFiles = array_merge($this->files, $this->atticFiles);
            }
        }

        return 1;
    }

    /**
     * Sort the contents of the directory in a given fashion and
     * order.
     *
     * @param $how Of the form CVSLIB_SORT_* where * can be:
     *             NONE, NAME, AGE, REV for sorting by name, age or revision.
     * @param $dir Of the form CVSLIB_SORT_* where * can be:
     *             ASCENDING, DESCENDING for the order of the sort.
     */
    function applySort($how = CVSLIB_SORT_NONE, $dir = CVSLIB_SORT_ASCENDING)
    {
        /* TODO: this code looks very inefficient! optimise... - avsm */
        // assume by name for the moment
        sort($this->dirs);
        reset($this->dirs);
        $this->doFileSort($this->files, $how, $dir);
        reset($this->files);
        if (isset($this->atticFiles)) {
            $this->doFileSort($this->atticFiles, $how, $dir);
            reset($this->atticFiles);
        }
        if (isset($this->mergedFiles)) {
            $this->doFileSort($this->mergedFiles, $how, $dir);
            reset($this->mergedFiles);
        }
        if ($dir == CVSLIB_SORT_DESCENDING) {
            $this->dirs = array_reverse($this->dirs);
            $this->files = array_reverse($this->files);
            if (isset($this->mergedFiles)) {
                $this->mergedFiles = array_reverse($this->mergedFiles);
            }
        }
    }

    function doFileSort(&$fileList, $how = CVSLIB_SORT_NONE, $dir = CVSLIB_SORT_ASCENDING)
    {
        switch ($how) {
        case CVSLIB_SORT_NONE:
            break;
        case CVSLIB_SORT_AGE:
            usort($fileList, array($this, "fileAgeSort"));
            break;
        case CVSLIB_SORT_NAME:
            usort($fileList, array($this, "fileNameSort"));
            break;
        case CVSLIB_SORT_AUTHOR:
            usort($fileList, array($this, "fileAuthorSort"));
            break;
        case CVSLIB_SORT_REV:
            usort($fileList, array($this, "fileRevSort"));
            break;
        default:
            break;
        }
    }

    /**
     * Sort function for ascending age.
     */
    function fileAgeSort($a, $b)
    {
        $aa = $a->queryLastLog();
        $bb = $b->queryLastLog();
        if ($aa->queryDate() == $bb->queryDate()) {
            return 0;
        } else {
            return ($aa->queryDate() < $bb->queryDate()) ? 1 : -1;
        }
    }

    /**
     * Sort function by author name.
     */
    function fileAuthorSort($a, $b)
    {
        $aa = $a->queryLastLog();
        $bb = $b->queryLastLog();
        if ($aa->queryAuthor() == $bb->queryAuthor()) {
            return 0;
        } else {
            return ($aa->queryAuthor() > $bb->queryAuthor()) ? 1 : -1;
        }
    }

    /**
     * Sort function for ascending filename.
     */
    function fileNameSort($a, $b)
    {
        if ($a->name == $b->name) {
            return 0;
        } else {
            return ($a->name < $b->name) ? -1 : 1;
        }
    }

    /**
     * Sort function for ascending revision.
     */
    function fileRevSort($a, $b)
    {
        return CVSLib_Rev::cmp($a->queryHead(), $b->queryHead());
    }

}
