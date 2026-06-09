<?php
/**
 * CVSLib diff class.
 *
 * See the README file that came with this library for more
 * information, and read the inline documentation.
 *
 * Copyright Anil Madhavapeddy, <anil@recoil.org>
 *
 * $Horde: chora/lib/CVSLib/Diff.php,v 1.18.2.5 2004/06/12 14:06:20 chuck Exp $
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Chora 0.1
 * @package chora
 */
class CVSLib_Diff {

    /**
     * Obtain the differences between two revisions within a file.
     *
     * @param CVS  CVSLib object for the current repository
     * @param file CVSLib_File object for the desired file
     * @param rev1 Original revision number to compare from
     * @param rev2 New revision number to compare against
     * @param type Constant which indicates the type of diff (e.g. unified)
     * @param num  Number of lines to be used in context and unified diff
     * @param ws   Show whitespace in the diff?
     *
     * @return false on failure, or a string containing the diff on success
     */
    function get(&$CVS, &$file, $rev1, $rev2, $type = CVSLIB_DIFF_CONTEXT, $num = 3, $ws = true)
    {
        /* Make sure that the file parameter is valid */
        if (!is_object($file) || $file->id() != CVSLIB_FILE) {
            return false;
        }

        /* Make sure that the CVS parameter is valid */
        if (!is_object($CVS) || $CVS->id() != CVSLIB_REPOSITORY) {
            return false;
        }

        /* Check that the revision numbers are valid */
        $rev1 = CVSLib_Rev::valid($rev1)?$rev1:'1.1';
        $rev2 = CVSLib_Rev::valid($rev1)?$rev2:'1.1';

        $fullName = $file->queryFullPath();
        $diff = array();
        $options = '-kk ';
        if (!$ws) {
            $opts = ' -bB ';
            $options .= $opts;
        } else {
            $opts = '';
        }
        switch ($type) {
        case CVSLIB_DIFF_CONTEXT:
            $options = $opts . '-p --context=' . (int)$num;
            break;
        case CVSLIB_DIFF_UNIFIED:
            $options = $opts . '-p --unified=' . (int)$num;
            break;
        case CVSLIB_DIFF_COLUMN:
            $options = $opts . '--side-by-side --width=120';
            break;
        case CVSLIB_DIFF_ED:
            $options = $opts . '-e';
            break;
        }

        // TODO: add options for $hr options - however these may not
        // be compatible with some diffs - avsm
        $command = $GLOBALS['conf']['paths']['rcsdiff'] . " $options -r$rev1 -r$rev2 \"" . escapeshellcmd($fullName) . '" 2>&1';
        if (OS_WINDOWS) {
            $command .= ' < "' . __FILE__ . '"';
        }

        exec($command, $diff, $retval);
        return ($retval > 0) ? $diff : array();
    }

    /**
     * Obtain a tree containing information about the changes between two
     * revisions.
     *
     * @param raw The raw unified diff, normally obtained through CVSLib_Diff::get()
     *
     * @return TODO: document this thoroughly, as the format is a bit complex
     */
    function humanReadable(&$raw)
    {
        $ret = array();

        /* Hold the left and right columns of lines for change blocks */
        $cols = array( array(), array() );
        $state = CVSLIB_DIFF_EMPTY;

        /* Iterate through every line of the diff */
        foreach ($raw as $line) {
            /* Look for a header which indicates the start of a diff chunk */
            if (preg_match('/^@@ \-([0-9]+).*\+([0-9]+).*@@(.*)/', $line, $regs)) {
               /* Push any previous header information to the return stack */
               if (isset($data)) $ret[] = $data;
               $data = array('type' => CVSLIB_DIFF_HEADER, 'oldline' => $regs[1],
                             'newline' => $regs[2], 'contents'> array());
               $data['function'] = isset($regs[3])?$regs[3]:'';
               $state = CVSLIB_DIFF_DUMP;

            } else if ($state != CVSLIB_DIFF_EMPTY) {
               /* We are in a chunk, so split out the action (+/-) and the line */
               preg_match('/^([\+\- ])(.*)/', $line, $regs);
               if (sizeof($regs) > 2) {
                   $action = $regs[1];
                   $content = $regs[2];
               } else {
                   $action = ' ';
                   $content = '';
               }

               if ($action == '+') {
                   /* This is just an addition line */
                   if ($state == CVSLIB_DIFF_DUMP || $state == CVSLIB_DIFF_ADD) {
                       /* Start adding to the addition stack */
                       $cols[0][] = $content;
                       $state = CVSLIB_DIFF_ADD;
                   } else {
                   /* This is inside a change block, so start accumulating lines */
                       $state = CVSLIB_DIFF_CHANGE;
                       $cols[1][] = $content;
                   }

               } else if ($action == '-') {
                   /* This is a removal line */
                   $state = CVSLIB_DIFF_REMOVE;
                   $cols[0][] = $content;

               } else {
                   /* An empty block with no action */
                   switch ($state) {
                     case CVSLIB_DIFF_ADD:
                       $data['contents'][] = array('type' => CVSLIB_DIFF_ADD, 'lines' => $cols[0]);
                       break;
                     case CVSLIB_DIFF_REMOVE:
                       /* We have some removal lines pending in our stack, so flush them */
                       $data['contents'][] = array('type' => CVSLIB_DIFF_REMOVE, 'lines' => $cols[0] );
                       break;
                     case CVSLIB_DIFF_CHANGE:
                       /* We have both remove and addition lines, so this is a change block */
                       $data['contents'][] = array('type' => CVSLIB_DIFF_CHANGE, 'old' => $cols[0], 'new' => $cols[1]);
                       break;
                   }
                   $cols = array( array(), array() );
                   $data['contents'][] = array('type' => CVSLIB_DIFF_EMPTY, 'line' => $content);
                   $state = CVSLIB_DIFF_DUMP;
               }
            }
        }

        /* Just flush any remaining entries in the columns stack */
        switch ($state) {
        case CVSLIB_DIFF_ADD:
            $data['contents'][] = array('type' => CVSLIB_DIFF_ADD, 'lines' => $cols[0]);
            break;
        case CVSLIB_DIFF_REMOVE:
            /* We have some removal lines pending in our stack, so flush them */
            $data['contents'][] = array('type' => CVSLIB_DIFF_REMOVE, 'lines' => $cols[0] );
            break;
        case CVSLIB_DIFF_CHANGE:
            /* We have both remove and addition lines, so this is a change block */
            $data['contents'][] = array('type' => CVSLIB_DIFF_CHANGE, 'old' => $cols[0], 'new' => $cols[1]);
            break;
        }

        if (isset($data)) {
            $ret[] = $data;
        }

        return $ret;
    }

}
