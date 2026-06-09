<?php
/**
 * Horde_VFS implementation for an FTP server.
 *
 * Required values for $params:
 *      'username'       The username with which to connect to the ftp server.
 *      'password'       The password with which to connect to the ftp server.
 *      'hostspec'       The ftp server to connect to.
 *      'port'           The port used to connect to the ftp server.
 *
 * $Horde: horde/lib/VFS/ftp.php,v 1.25.2.4 2003/01/03 13:23:19 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 2002-2003 Michael Varghese <mike.varghese@ascellatech.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Varghese <mike.varghese@ascellatech.com>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.vfs
 */
class Horde_VFS_ftp extends Horde_VFS {

    /**
     * Boolean indicating whether or not we're connected to the ftp
     * server.
     *
     * @var $_connected boolean
     */
    var $_connected = false;

    /**
     * List of additional credentials required for this VFS backend.
     *
     * @var $_credentials array
     */
    var $_credentials = array('username', 'password');

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
     * Variable holding the connection to the ftp server.
     *
     * @var $_stream resource
     */
    var $_stream = '';

    /**
     * Constructs a new FTP-based VFS object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Horde_VFS_ftp($params = array())
    {
        $this->_params = $params;
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $tmpFile = Horde::getTempFile('vfs', false);
        $fetch = @ftp_get($this->_stream, $tmpFile,
                          $this->_getPath($path, $name), FTP_BINARY);
        if ($fetch !== false) {
            if (OS_WINDOWS) {
                $mode = 'rb';
            } else {
                $mode = 'r';
            }
            $fp = fopen($tmpFile, $mode);
            $data = fread($fp, filesize($tmpFile));
            fclose($fp);
            unlink($tmpFile);
            return $data;
        } else {
            return PEAR::raiseError('Unable to open VFS file.');
        }
    }

    /**
     * Store a file in the VFS.
     *
     * @param string  $path        The path to store the file in.
     * @param string  $name        The filename to use.
     * @param string  $tmpFile     The temporary file containing the data to be stored.
     * @param boolean $autocreate  (optional) Automatically create directories?
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function write($path, $name, $tmpFile, $autocreate = false)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_put($this->_stream, $this->_getPath($path, $name), $tmpFile, FTP_BINARY)) {
            return PEAR::raiseError('Unable to write VFS file.');
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
    function writeData($path, $name, $data, $autocreate = false)
    {
        $tmpFile = Horde::getTempFile('vfs');
        $fp = fopen($tmpFile, 'wb');
        fwrite($fp, $data);
        fclose($fp);

        return $this->write($path, $name, $tmpFile, $autocreate);
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to delete the file from.
     * @param string $name  The filename to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFile($path, $name)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_delete($this->_stream, $this->_getPath($path, $name))) {
            return PEAR::raiseError('Unable to delte VFS file.');
        }

        return true;
    }

    /**
     * Delete a folder from the VFS.
     *
     * @param string $path The path to delete the folder from.
     * @param string $name The name of the folder to delete.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($path, $name)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_rmdir($this->_stream, $this->_getPath($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS folder.');
        }

        return true;
    }

    /**
     * Rename a file in the VFS.
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_rename($this->_stream, $this->_getPath($oldpath, $oldname), $this->_getPath($newpath, $newname))) {
            return PEAR::raiseError(sprintf(_('Unable to rename VFS file %s/%s.'), $oldpath, $oldname));
        }

        return true;
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path Holds the path of directory to create folder.
     * @param string $name Holds the name of the new folder.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function createFolder($path, $name)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_mkdir($this->_stream, $this->_getPath($path, $name))) {
            return PEAR::raiseError('Unable to create VFS folder.');
        }

        return true;
    }

    /**
     * Changes permissions for an Item on the VFS.
     *
     * @param string $path Holds the path of directory of the Item.
     * @param string $name Holds the name of the Item.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function changePermissions($path, $name, $permission)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        if (!@ftp_site($this->_stream, 'CHMOD ' . $permission . ' ' . $this->_getPath($path, $name))) {
            return PEAR::raiseError(sprintf(_('Unable to change permission for VFS file %s/%s.'), $path, $name));
        }

        return true;
    }

    /**
     * Returns the full path of an item.
     *
     * @param string $path Holds the path of directory of the Item.
     * @param string $name Holds the name of the Item.
     *
     * @return mixed  Full path when $path isset and just $name when not set.
     */
    function _getPath($path, $name)
    {
        if ($path !== '') {
             return ($path . '/' . $name);
        }
        return ($name);
    }

    /**
     * Returns an unsorted file list.
     *
     * @param string $path  Holds the path of the directory to get the file list for.
     *
     * @return mixed  File list on success or a PEAR_Error object on failure.
     */
    function listFolder($path = '')
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $files = array();
        $type = @ftp_systype($this->_stream);

        if (!empty($path)) {
            if (!@ftp_chdir($this->_stream, $path)) {
                return PEAR::raiseError('Unable to open ' . $path . '.');
            }
        }

        if ($type == 'UNIX') {
           $list = ftp_rawlist($this->_stream, '-al');
        } else {
           $list = ftp_rawlist($this->_stream, '');
        }

        if (!is_array($list)) {
            return array();
        }
        foreach ($list as $line) {
            $file = array();
            $item = preg_split('/\s+/', $line);
            if ($type == 'UNIX' || ($type == 'Windows_NT' && !preg_match('|\d\d-\d\d-\d\d|', $item[0]))) {
                if (count($item) < 8 || substr($line, 0, 5) == 'total') {
                    continue;
                }
                $file['perms'] = $item[0];
                $file['owner'] = $item[2];
                $file['group'] = $item[3];
                $file['name'] = substr($line, strpos($line, sprintf("%s %2s %5s", $item[5], $item[6], $item[7])) + 13);
                if (preg_match('/^\.\.?\/?$/', $file['name'])) {
                    continue;
                }
                $p1 = substr($file['perms'], 0, 1);
                if ($p1 === 'l') {
                    $file['link'] = substr($file['name'], strpos($file['name'], '->') + 3);
                    $file['name'] = substr($file['name'], 0, strpos($file['name'], '->') - 1);
                    $file['type'] = '**sym';
                } else if ($p1 === 'd') {
                    $file['type'] = '**dir';
                } else {
                    $name = explode('.', $file['name']);
                    if (count($name) == 1 || (substr($file['name'], 0, 1) === '.' && count($name) == 2)) {
                        $file['type'] = '**none';
                    } else {
                        $file['type'] = strtolower($name[count($name) - 1]);
                    }
                }
                if ($file['type'] == '**dir') {
                    $file['size'] = -1;
                } else {
                    $file['size'] = $item[4];
                }
                if (strstr($item[7], ':')) {
                    $file['date'] = strtotime($item[7] . ':00' . $item[5] . ' ' . $item[6] . ' ' . date('Y', time()));
                    if ($file['date'] > time()) {
                        $file['date'] = strtotime($item[7] . ':00' . $item[5] . ' ' . $item[6] . ' ' . (date('Y', time()) - 1));
                    }
                } else {
                    $file['date'] = strtotime('00:00:00' . $item[5] . ' ' . $item[6] . ' ' . $item[7]);
                }
            } else {
                /* Handle Windows FTP servers returning DOS-style file
                 * listings. */
                $file['perms'] = '';
                $file['owner'] = '';
                $file['group'] = '';
                $file['name'] = $item[3];
                $index = 4;
                while ($index < count($item)) {
                    $file['name'] .= ' ' . $item[$index];
                    $index++;
                }
                $file['date'] = strtotime($item[0] . ' ' . $item[1]);
                if ($item[2] == '<DIR>') {
                    $file['type'] = '**dir';
                    $file['size'] = -1;
                } else {
                    $file['size'] = $item[2];
                    $name = explode('.', $file['name']);
                    if (count($name) == 1 || (substr($file['name'], 0, 1) === '.' && count($name) == 2)) {
                        $file['type'] = '**none';
                    } else {
                        $file['type'] = strtolower($name[count($name) - 1]);
                    }
                }
            }
            $files[$file['name']] = $file;
        }

        /* Figure out the real type of symlinks. */
        foreach ($files as $key => $file) {
            if ($file['type'] === '**sym') {
                if (isset($files[$file['link']]) && ($file['name'] !== $file['link'])) {
                    $file['type'] = $files[$file['link']]['type'];
                } else {
                    $file['type'] = '**broken';
                }
                $files[$key] = $file;
            }
        }
        return $files;
    }

    /**
     * Attempts to open a connection to the FTP server.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->_params)) {
                return PEAR::raiseError(_("No configuration information specified for FTP VFS."));
            }
            if (!isset($this->_params['username'])) {
                return PEAR::raiseError(_("Required 'username' not specified in VFS configuration."));
            }
            if (!isset($this->_params['password'])) {
                return PEAR::raiseError(_("Required 'password' not specified in VFS configuration."));
            }
            if (!isset($this->_params['hostspec'])) {
                return PEAR::raiseError(_("Required 'hostspec' not specified in VFS configuration."));
            }
            if (!isset($this->_params['port'])) {
                return PEAR::raiseError(_("Required 'port' not specified in VFS configuration."));
            }

            /* Connect to the ftp server using the supplied parameters. */
            $this->_stream = @ftp_connect($this->_params['hostspec'], $this->_params['port']);
            $this->_connected = @ftp_login($this->_stream, $this->_params['username'], $this->_params['password']);

            if (!$this->_connected) {
                return PEAR::raiseError(_("Connection to ftp server failed."));
            }

            @ftp_pasv($this->_stream, true);
        }
        return true;
    }

    /**
     * Disconnect from the FTP server and clean up the connection.
     *
     * @return boolean     True on success, false on failure.
     */
    function _disconnect()
    {
        @ftp_quit($this->_stream);
        $this->_connected = false;
    }

}
