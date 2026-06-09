<?php
/**
 * Horde_VFS implementation for a standard filesystem.
 *
 * Required values for $params:
 *      'vfsroot'       The root path
 *
 * Note: The user that your webserver runs as (commonly 'nobody',
 * 'apache', or 'www-data') MUST have read/write permission to the
 * directory you specific as the 'vfsroot'.
 *
 * $Horde: horde/lib/VFS/file.php,v 1.22.2.5 2003/01/03 13:23:17 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.vfs
 */
class Horde_VFS_file extends Horde_VFS {

    /**
     * List of permissions and if they can be changed in this VFS
     * backend.
     *
     * @var $_permissions array
     */
    var $_permissions = array(
        'owner' => array('read' => true, 'write' => true, 'execute' => true),
        'group' => array('read' => true, 'write' => true, 'execute' => true),
        'all'   => array('read' => true, 'write' => true, 'execute' => true));

    /**
     * Constructs a new Filesystem based Horde_VFS object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Horde_VFS_file($params = array())
    {
        $this->_params = $params;
        if (substr($this->_params['vfsroot'], -1) == '/' ||
            substr($this->_params['vfsroot'], -1) == '\\') {
            $this->_params['vfsroot'] = substr($this->_params['vfsroot'], 0, strlen($this->_params['vfsroot']) - 1);
        }
    }

    /**
     * Retrieve a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string The file data.
     */
    function read($path, $name)
    {
        $file = $this->_getNativePath($path, $name);
        $fp = @fopen($file, 'rb');
        if (!$fp) {
            return PEAR::raiseError('Unable to open VFS file.');
        }
        $data = fread($fp, filesize($file));
        fclose($fp);

        return $data;
    }

    /**
     * Store a file in the VFS, with the data copied from a temporary
     * file.
     *
     * @param string  $path        The path to store the file in.
     * @param string  $name        The filename to use.
     * @param string  $tmpFile     The temporary file containing the data to be stored.
     * @param boolean $autocreate  (optional) Automatically create directories?
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function write($path, $name, $tmpFile, $autocreate = true)
    {
        if (!@is_dir($this->_getNativePath($path))) {
            if ($autocreate) {
                $res = $this->_autocreate($path);
                if (PEAR::isError($res)) {
                    return $res;
                }
            } else {
                return PEAR::raiseError('VFS directory does not exist.');
            }
        }

        $fp = @fopen($this->_getNativePath($path, $name), 'w');
        if (!$fp) {
            return PEAR::raiseError('Unable to open VFS file for writing.');
        }

        $dataFP = @fopen($tmpFile, 'rb');
        $data = @fread($dataFP, filesize($tmpFile));
        fclose($dataFP);

        if (!@fwrite($fp, $data)) {
            return PEAR::raiseError('Unable to write VFS file data.');
        }

        return true;
    }

    /**
     * Store a file in the VFS from raw data.
     *
     * @param string  $path        The path to store the file in.
     * @param string  $name        The filename to use.
     * @param string  $data        The file data.
     * @param boolean $autocreate  (optional) Automatically create directories?
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function writeData($path, $name, $data, $autocreate = true)
    {
        if (!@is_dir($this->_getNativePath($path))) {
            if ($autocreate) {
                $res = $this->_autocreate($path);
                if (PEAR::isError($res)) {
                    return $res;
                }
            } else {
                return PEAR::raiseError('VFS directory does not exist.');
            }
        }

        $fp = @fopen($this->_getNativePath($path, $name), 'w');
        if (!$fp) {
            return PEAR::raiseError('Unable to open VFS file for writing.');
        }

        if (!@fwrite($fp, $data)) {
            return PEAR::raiseError('Unable to write VFS file data.');
        }

        return true;
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFile($path, $name)
    {
        if (!@unlink($this->_getNativePath($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS file.');
        }
    }

    /**
     * Delete a folder from the VFS.
     *
     * @param string $path  The path to delete the folder from.
     * @param string $name  The foldername to use.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($path, $name)
    {
        if (!@rmdir($this->_getNativePath($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS directory.');
        }

        return true;
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  The path to delete the folder from.
     * @param string $name  The foldername to use.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function createFolder($path, $name)
    {
        if (!@mkdir($this->_getNativePath($path, $name))) {
            return PEAR::raiseError('Unable to create VFS directory.');
        }

        return true;
    }

    /**
     * Changes permissions for an item in the VFS.
     *
     * @param string $path Holds the path of directory of the item.
     * @param string $name Holds the name of the item.
     * @param integer $permission Holds the value of the new permission.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function changePermissions($path, $name, $permission)
    {
        if (!@chmod($this->_getNativePath($path, $name), $permission)) {
            return PEAR::raiseError(sprintf(_('Unable to change permission for VFS file %s/%s.'), $path, $name));
        }

        return true;
    }

    /**
     * Return a list of the contents of a folder.
     *
     * @param string $path Holds the path of the directory.
     *
     * @return mixed  File list on success or false on failure.
     */
    function listFolder($path)
    {
        $files = array();
        $path = isset($path) ? $this->_getNativePath($path) : $this->_getNativePath();

        if (is_dir($path)) {
            if (!@chdir($path)) {
                return PEAR::raiseError("Unable to access VFS directory.");
            }
            $handle = opendir($path);

            while (($entry = readdir($handle)) !== false) {
                if (($entry != '.') && ($entry != '..')) {
                    // File name
                    $file['name'] = $entry;

                    // Unix style file permissions
                    $file['perms'] = $this->_getUnixPerms(fileperms($entry));

                    // Owner
                    $file['owner'] = fileowner($entry);
                    if (function_exists('posix_getpwuid')) {
                        $owner = posix_getpwuid($file['owner']);
                        $file['owner'] = $owner['name'];
                    }

                    // Group
                    $file['group'] = filegroup($entry);
                    if (function_exists('posix_getgrgid')) {
                        $group = posix_getgrgid($file['group']);
                        $file['group'] = $group['name'];
                    }

                    // Size
                    $file['size'] = filesize($entry);

                    // Date
                    $file['date'] = filemtime($entry);

                    // Type
                    if (is_dir($entry) && !is_link($entry)) {
                        $file['perms'] = 'd' . $file['perms'];
                        $file['type'] = '**dir';
                        $file['size'] = -1;
                    } else if (is_link($entry)) {
                        $file['perms'] = 'l' . $file['perms'];
                        $file['type'] = '**sym';
                        $file['link'] = readlink($entry);
                    } else if (is_file($entry)) {
                        $file['perms'] = '-' . $file['perms'];
                        $ext = explode('.', $entry);

                        if (count($ext) == 1 || (substr($file['name'], 0, 1) === '.' && count($ext) == 2)) {
                            $file['type'] = '**none';
                        } else {
                            $file['type'] = strtolower($ext[count($ext)-1]);
                        }
                    } else {
                        $file['type'] = '**none';
                        if ((fileperms($entry) & 0xC000) == 0xC000) {
                            $file['perms'] = 's' . $file['perms'];
                        } else if ((fileperms($entry) & 0x6000) == 0x6000) {
                            $file['perms'] = 'b' . $file['perms'];
                        } else if ((fileperms($entry) & 0x2000) == 0x2000) {
                            $file['perms'] = 'c' . $file['perms'];
                        } else if ((fileperms($entry) & 0x1000) == 0x1000) {
                            $file['perms'] = 'p' . $file['perms'];
                        } else {
                            $file['perms'] = '?' . $file['perms'];
                        }
                    }

                    $files[$file['name']] = $file;
                    unset($file);
                }
            }

            /* Figure out the real type of symlinks. */
            foreach ($files as $key => $file) {
                if ($file['type'] === '**sym') {
                    if (file_exists($file['link'])) {
                        if (is_dir($file['link'])) {
                            $file['type'] = '**dir';
                            $file['size'] = -1;
                        } else if (is_link($file['link'])) {
                            $file['type'] = '**sym';
                        } else if (is_file($file['link'])) {
                            $ext = explode('.', $file['link']);
                            if (count($ext) == 1 || (substr($file['name'], 0, 1) === '.' && count($ext) == 2)) {
                                $file['type'] = '**none';
                            } else {
                                $file['type'] = strtolower($ext[count($ext)-1]);
                            }
                        } else {
                            $file['type'] = '**none';
                        }
                    } else {
                        $file['type'] = '**broken';
                    }
                    $files[$key] = $file;
                }
            }
        }
        return $files;
    }

    /**
     * Return Unix style perms.
     *
     * @param string $perms
     *
     * @return mixed Unix style perms or a PEAR_Error object on failure.
     */
    function _getUnixPerms($perms)
    {
        // Determine permissions
        $owner['read']    = ($perms & 00400) ? 'r' : '-';
        $owner['write']   = ($perms & 00200) ? 'w' : '-';
        $owner['execute'] = ($perms & 00100) ? 'x' : '-';
        $group['read']    = ($perms & 00040) ? 'r' : '-';
        $group['write']   = ($perms & 00020) ? 'w' : '-';
        $group['execute'] = ($perms & 00010) ? 'x' : '-';
        $world['read']    = ($perms & 00004) ? 'r' : '-';
        $world['write']   = ($perms & 00002) ? 'w' : '-';
        $world['execute'] = ($perms & 00001) ? 'x' : '-';

        // Adjust for SUID, SGID and sticky bit
        if ($perms & 0x800) {
            $owner['execute'] = ($owner['execute'] == 'x') ? 's' : 'S';
        }
        if ($perms & 0x400) {
            $group['execute'] = ($group['execute'] == 'x') ? 's' : 'S';
        }
        if ($perms & 0x200) {
            $world['execute'] = ($world['execute'] == 'x') ? 't' : 'T';
        }

        $unixPerms = $owner['read'] . $owner['write'] . $owner['execute'] .
                     $group['read'] . $group['write'] . $group['execute'] .
                     $world['read'] . $world['write'] . $world['execute'];

        return $unixPerms;
    }

    /**
     * Rename a file or folder in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @param string $newname  The new filename.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function rename($oldpath, $oldname, $newpath, $newname)
    {
        if (!@rename($this->_getNativePath($oldpath, $oldname),
                     $this->_getNativePath($newpath, $newname))) {
            return PEAR::raiseError(sprintf(_('Unable to rename VFS file %s/%s.'), $oldpath, $oldname));
        }

        return true;
    }

    /**
     * Return a full filename on the native filesystem, from a VFS
     * path and name.
     *
     * @param string $path  The VFS file path.
     * @param string $name  The VFS filename.
     *
     * @return string  The full native filename.
     */
    function _getNativePath($path = '', $name = '')
    {
        $name = basename($name);
        if (!empty($name)) {
            if ($name == '..') {
                $name = '';
            } else {
                $name = DIRECTORY_SEPARATOR . $name;
            }
        }
        if (!empty($path)) {
            $path = str_replace('..', '', $path);
            return $this->_params['vfsroot'] . DIRECTORY_SEPARATOR . $path . $name;
        } else {
            return $this->_params['vfsroot'] . $name;
        }
    }

    /**
     * Automatically create any necessary parent directories in the
     * specified $path.
     *
     * @param string $path  The VFS path to autocreate.
     */
    function _autocreate($path)
    {
        $dirs = explode('/', $path);
        if (is_array($dirs)) {
            $cur = '';
            foreach ($dirs as $dir) {
                if (!empty($cur)) {
                    $cur .= '/';
                }
                $cur .= $dir;
                if (!@is_dir($this->_getNativePath($cur))) {
                    if (!@mkdir($this->_getNativePath($cur))) {
                        return PEAR::raiseError('Unable to create VFS directory structure.');
                    }
                }
            }
        }

        return true;
    }

    /**
     * Stub to check if we have a valid connection. Makes sure that
     * the vfsroot is readable.
     *
     * @return mixed  True if it is, PEAR_Error if it isn't.
     */
    function _connect()
    {
        if ((@is_dir($this->_params['vfsroot']) &&
             @is_readable($this->_params['vfsroot'])) ||
            @mkdir($this->_params['vfsroot'])) {
            return true;
        } else {
            return PEAR::raiseError(_("Unable to read the vfsroot directory."));
        }
    }

}
