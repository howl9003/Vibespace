<?php
/**
 * Horde_VFS implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:
 *      'phptype'       The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *
 * Optional values:
 *      'table'         The name of the vfs table in 'database'. Defaults to 'horde_vfs'.
 *
 * Required by some database implementations:
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.
 *
 * The table structure for the VFS can be found in
 * horde/scripts/db/vfs.sql.
 *
 * Database specific notes:
 *
 * MSSQL:
 * - The vfs_data field must be of type IMAGE.
 * - You need the following php.ini settings:
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textlimit = 0 ; zero to pass through
 *
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textsize = 0 ; zero to pass through
 *
 * $Horde: horde/lib/VFS/sql.php,v 1.32.2.4 2003/01/28 12:37:58 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.vfs
 */

// VFS File Types
/** @const HORDE_VFS_FILE File value for vfs_type column. */
define('HORDE_VFS_FILE', 1);

/** @const HORDE_VFS_FOLDER Folder value for vfs_type column. */
define('HORDE_VFS_FOLDER', 2);

class Horde_VFS_sql extends Horde_VFS {

    /**
     * Handle for the current database connection.
     * @var $_db object DB
     */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL
     * server.
     * @var $_connected boolean
     */
    var $_connected = false;

    /**
     * Constructs a new SQL VFS object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Horde_VFS_sql($params = array())
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

        require_once dirname(__FILE__) . '/../SQL.php';
        return Horde_SQL::readBlob($this->_db, $this->_params['table'], 'vfs_data',
                                   array('vfs_path' => $path,
                                         'vfs_name' => $name));
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
        $dataFP = @fopen($tmpFile, 'rb');
        $data = @fread($dataFP, filesize($tmpFile));
        fclose($dataFP);
        return $this->writeData($path, $name, $data, $autocreate);
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        /* Check to see if the data already exists. */
        $query = sprintf('SELECT vfs_id FROM %s WHERE vfs_path %s AND vfs_name = %s',
                         $this->_params['table'],
                         (empty($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path),
                         $this->_db->quote($name));
        $id = $this->_db->getOne($query);

        if (PEAR::isError($id)) {
            return $id;
        }

        require_once dirname(__FILE__) . '/../SQL.php';
        if ($id) {
            return Horde_SQL::updateBlob($this->_db, $this->_params['table'], 'vfs_data',
                                         $data, array('vfs_id' => $id),
                                         array('vfs_modified' => time()));
        } else {
            $id = $this->_db->nextId($this->_params['table']);
            if (PEAR::isError($id)) {
                return $id;
            }
            return Horde_SQL::insertBlob($this->_db, $this->_params['table'], 'vfs_data',
                                         $data, array('vfs_id' => $id,
                                                      'vfs_type' => HORDE_VFS_FILE,
                                                      'vfs_path' => $path,
                                                      'vfs_name' => $name,
                                                      'vfs_modified' => time(),
                                                      'vfs_owner' => Auth::getAuth()));
        }
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path %s AND vfs_name = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FILE),
                                            (empty($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path),
                                            $this->_db->quote($name)));

        if ($this->_db->affectedRows() == 0) {
            return PEAR::raiseError('Unable to delete VFS file.');
        }

        return $result;
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = %s, vfs_name = %s, vfs_modified = %s
                                            WHERE vfs_path = %s AND vfs_name = %s',
                                            $this->_params['table'],
                                            $this->_db->quote($newpath),
                                            $this->_db->quote($newname),
                                            $this->_db->quote(time()),
                                            $this->_db->quote($oldpath),
                                            $this->_db->quote($oldname)));

        if ($this->_db->affectedRows() == 0) {
            return PEAR::raiseError('Unable to rename VFS file.');
        }

        $rename = $this->_recursiveRename($oldpath, $oldname, $newpath, $newname);
        if (PEAR::isError($rename)) {
            return PEAR::raiseError(sprintf('Unable to rename VFS directory: %s.', $rename->getMessage()));
        }

        return $result;
    }

    /**
     * Renames all child paths.
     *
     * @param string $path  The path of the folder to rename.
     * @param string $name  The foldername to use.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function _recursiveRename($oldpath, $oldname, $newpath, $newname)
    {
        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                                 $this->_params['table'],
                                                 $this->_db->quote(HORDE_VFS_FOLDER),
                                                 $this->_db->quote($this->_getNativePath($oldpath, $oldname))));

        foreach ($folderList as $folder) {
            $this->_recursiveRename($this->_getNativePath($oldpath, $oldname), $folder, $this->_getNativePath($newpath, $newname), $folder);
        }

        $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = %s WHERE vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote($this->_getNativePath($newpath, $newname)),
                                            $this->_db->quote($this->_getNativePath($oldpath, $oldname))));

        if (is_a($result)) {
            return $result;
        }
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

        $id = $this->_db->nextId($this->_params['table']);
        if (PEAR::isError($id)) {
            return $id;
        }

        return $this->_db->query(sprintf('INSERT INTO %s (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner)
                                         VALUES (%s, %s, %s, %s, %s, %s)',
                                         $this->_params['table'],
                                         $this->_db->quote($id),
                                         $this->_db->quote(HORDE_VFS_FOLDER),
                                         $this->_db->quote($path),
                                         $this->_db->quote($name),
                                         $this->_db->quote(time()),
                                         $this->_db->quote(Auth::getAuth())));
    }

    /**
     * Delete a folder from the VFS.
     *
     * @param string $path  The path of the folder.
     * @param string $name  The folername to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($path, $name)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path %s AND vfs_name = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FOLDER),
                                            (empty($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path),
                                            $this->_db->quote($name)));

        if ($this->_db->affectedRows() == 0) {
            return PEAR::raiseError('Unable to delete VFS directory.');
        }

        $delete = $this->_recursiveDelete($path, $name);
        if (PEAR::isError($delete)) {
            return PEAR::raiseError(sprintf('Unable to delete VFS recursively: %s.', $delete->getMessage()));
        }

        return $result;
    }

    /**
     * Delete a folders contents from the VFS, recursively.
     *
     * @param string $path  The path of the folder.
     * @param string $name  The foldername to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _recursiveDelete($path, $name)
    {
        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FILE),
                                            $this->_db->quote($this->_getNativePath($path, $name))));
        if (PEAR::isError($result)) {
            return $result;
        }

        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                                 $this->_params['table'],
                                                 $this->_db->quote(HORDE_VFS_FOLDER),
                                                 $this->_db->quote($this->_getNativePath($path, $name))));

        foreach ($folderList as $folder) {
            $this->_recursiveDelete($this->_getNativePath($path, $name), $folder);
        }

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_name = %s AND vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FOLDER),
                                            $this->_db->quote($name),
                                            $this->_db->quote($path)));

        return $result;
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
    function _getNativePath($path, $name)
    {
        if (empty($path)) {
            return $name;
        }

        return $path . DIRECTORY_SEPARATOR . $name;
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $files = array();
        $fileList = array();

        // Fix for an ODD Oracle quirk.
        if (empty($path) && $this->_db->dbsyntax == 'oci8') {
            $where = 'vfs_path IS NULL';
        } else {
            $where = 'vfs_path = ' . $this->_db->quote($path);
        }

        $fileList = $this->_db->getAll(sprintf('SELECT vfs_name, vfs_type, vfs_data, vfs_modified, vfs_owner FROM %s
                                               WHERE %s',
                                               $this->_params['table'],
                                               $where));
        if (PEAR::isError($fileList)) {
            return $fileList;
        }

        foreach ($fileList as $line) {
            $file['name'] = $line[0];

            if ($line[1] == HORDE_VFS_FILE) {
                $name = explode('.', $line[0]);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = strtolower($name[count($name) - 1]);
                }

                $file['size'] = strlen($line[2]);
            } else if ($line[1] == HORDE_VFS_FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line[3];
            $file['owner'] = $line[4];
            $file['perms'] = '-';
            $file['group'] = '-';

            $files[$file['name']] = $file;
        }

        return $files;
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->_params)) {
                return PEAR::raiseError(_("No configuration information specified for SQL VFS."));
            }
            if (!isset($this->_params['phptype'])) {
                return PEAR::raiseError(_("Required 'phptype' not specified in VFS configuration."));
            }
            if (!isset($this->_params['hostspec'])) {
                return PEAR::raiseError(_("Required 'hostspec' not specified in VFS configuration."));
            }
            if (!isset($this->_params['username'])) {
                return PEAR::raiseError(_("Required 'username' not specified in VFS configuration."));
            }
            if (!isset($this->_params['password'])) {
                return PEAR::raiseError(_("Required 'password' not specified in VFS configuration."));
            }
            if (!isset($this->_params['database'])) {
                return PEAR::raiseError(_("Required 'database' not specified in VFS configuration."));
            }
            if (!isset($this->_params['table'])) {
                $this->_params['table'] = 'horde_vfs';
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, true);
            if (PEAR::isError($this->_db)) {
                return $this->_db;
            }

            /* Enable the "portability" option. */
            $this->_db->setOption('optimize', 'portability');
            $this->_connected = true;
        }

        return true;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return boolean     True on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_db->disconnect();
        }

        return true;
    }

}
