<?php
/**
 * Horde_VFS:: implementation using PHP's PEAR database abstraction
 * layer and local file system for file storage.
 *
 * Required values for $params:
 *      'phptype'       The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *      'vfsroot'       The root directory of where the files should be actually stored.
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
 * $Horde: horde/lib/VFS/sql_file.php,v 1.6.2.2 2003/01/03 13:23:19 jan Exp $
 *
 * @author  Michael Varghese <mike.varghese@ascellatech.com>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.vfs
 */

// VFS File Types
/** @const HORDE_VFS_FILE   File value for vfs_type column.     */
define('HORDE_VFS_FILE', 1);

/** @const HORDE_VFS_FOLDER Folder value for vfs_type column.   */
define('HORDE_VFS_FOLDER', 2);

class Horde_VFS_sql_file extends Horde_VFS {

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
     * Constructs a new Local Filesystem SQL VFS object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Horde_VFS_sql_file($params = array())
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
    function write($path, $name, $tmpFile, $autocreate = false)
    {
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

        if (PEAR::isError($this->_writeSQLData($path, $name, $autocreate))) {
            @unlink($this->_getNativePath($path, $name));
            return PEAR::raiseError('Unable to write VFS file data.');
        }
    }

    /**
     * Store a files information within the database.
     *
     * @param string  $path        The path to store the file in.
     * @param string  $name        The filename to use.
     * @param boolean $autocreate  (optional) Automatically create directories?
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _writeSQLData($path, $name, $autocreate=false)
    {
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $id = $this->_db->nextId($this->_params['table']);

        $query = sprintf('INSERT INTO %s (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified,' .
                         ' vfs_owner) VALUES (%s, %s, %s, %s, %s, %s)',
                         $this->_params['table'],
                         $this->_db->quote($id),
                         $this->_db->quote(HORDE_VFS_FILE),
                         $this->_db->quote($path),
                         $this->_db->quote($name),
                         $this->_db->quote(time()),
                         $this->_db->quote(Auth::getAuth()));

        return $this->_db->query($query);
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
        $result = $this->_db->query(sprintf('INSERT INTO %s (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner)
                                         VALUES (%s, %s, %s, %s, %s, %s)',
                                         $this->_params['table'],
                                         $this->_db->quote($id),
                                         $this->_db->quote(HORDE_VFS_FOLDER),
                                         $this->_db->quote($path),
                                         $this->_db->quote($name),
                                         $this->_db->quote(time()),
                                         $this->_db->quote(Auth::getAuth())));
        if (PEAR::isError($result)) {
            return $result;
        }

        if (!@mkdir($this->_getNativePath($path, $name))) {
            $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_id = %s',
                                                $this->_params['table'],
                                                $this->_db->quote($id)));
            return PEAR::raiseError('Unable to create VFS directory.');
        }

        return true;
    }

    /**
     * Rename a file or folder in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @parma string $newname  The new filename.
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

        if (PEAR::isError($this->_recursiveSQLRename($oldpath, $oldname, $newpath, $newname))) {
            $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = %s, vfs_name = %s
                                                WHERE vfs_path = %s AND vfs_name = %s',
                                                $this->_params['table'],
                                                $this->_db->quote($oldpath),
                                                $this->_db->quote($oldname),
                                                $this->_db->quote($newpath),
                                                $this->_db->quote($newname)));
            return PEAR::raiseError('Unable to rename VFS directory.');
        }

        if (!@rename($this->_getNativePath($oldpath, $oldname), $this->_getNativePath($newpath, $newname))) {
            $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = %s, vfs_name = %s
                                                WHERE vfs_path = %s AND vfs_name = %s',
                                                $this->_params['table'],
                                                $this->_db->quote($oldpath),
                                                $this->_db->quote($oldname),
                                                $this->_db->quote($newpath),
                                                $this->_db->quote($newname)));
            return PEAR::raiseError('Unable to rename VFS file.');
        }

        return true;
    }

    /**
     * Renames all child paths.
     *
     * @param string $path  The path of the folder to rename.
     * @param string $name  The foldername to use.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function _recursiveSQLRename($oldpath, $oldname, $newpath, $newname)
    {
        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                                 $this->_params['table'],
                                                 $this->_db->quote(HORDE_VFS_FOLDER),
                                                 $this->_db->quote($this->_getSQLNativePath($oldpath, $oldname))));

        foreach ($folderList as $folder) {
            $this->_recursiveSQLRename($this->_getSQLNativePath($oldpath, $oldname), $folder, $this->_getSQLNativePath($newpath, $newname), $folder);
        }

        $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = %s WHERE vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote($this->_getSQLNativePath($newpath, $newname)),
                                            $this->_db->quote($this->_getSQLNativePath($oldpath, $oldname))));

        if (PEAR::isError($result)) {
            return $result;
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
        $conn = $this->_connect();
        if (PEAR::isError($conn)) {
            return $conn;
        }

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path = %s AND vfs_name = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FOLDER),
                                            $this->_db->quote($path),
                                            $this->_db->quote($name)));

        if ($this->_db->affectedRows() == 0 || PEAR::isError($result)) {
            return PEAR::raiseError('Unable to delete VFS directory.');
        }

        if (PEAR::isError($this->_recursiveSQLDelete($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS directory recursively.');
        }

        if (PEAR::isError($this->_recursiveLFSDelete($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS directory recursively.');
        }

        return $result;
    }

    /**
     * Delete a folders contents from the VFS in the SQL database, recursively.
     *
     * @param string $path  The path of the folder.
     * @param string $name  The foldername to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _recursiveSQLDelete($path, $name)
    {
        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FILE),
                                            $this->_db->quote($this->_getSQLNativePath($path, $name))));
        if (PEAR::isError($result)) {
            return $result;
        }

        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = %s AND vfs_path = %s',
                                                 $this->_params['table'],
                                                 $this->_db->quote(HORDE_VFS_FOLDER),
                                                 $this->_db->quote($this->_getSQLNativePath($path, $name))));

        foreach ($folderList as $folder) {
            $this->_recursiveSQLDelete($this->_getSQLNativePath($path, $name), $folder);
        }

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_name = %s AND vfs_path = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FOLDER),
                                            $this->_db->quote($name),
                                            $this->_db->quote($path)));

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
    function _recursiveLFSDelete($path, $name)
    {
        $dir = $this->_getNativePath($path, $name);
        $dh = @opendir($dir);

        while (false !== ($file = readdir($dh))) {
            if ($file != '.' && $file != '..') {
                if(is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                    $this->_recursiveLFSDelete(empty($path) ? $name : $path . DIRECTORY_SEPARATOR . $name, $file);
                } else {
                    @unlink($dir . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        @closedir($dh);

        return rmdir($dir);
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

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = %s AND vfs_path = %s AND vfs_name = %s',
                                            $this->_params['table'],
                                            $this->_db->quote(HORDE_VFS_FILE),
                                            $this->_db->quote($path),
                                            $this->_db->quote($name)));

        if ($this->_db->affectedRows() == 0) {
            return PEAR::raiseError('Unable to delete VFS file.');
        }

        if (PEAR::isError($result)) {
            return $result;
        }

        if (!@unlink($this->_getNativePath($path, $name))) {
            return PEAR::raiseError('Unable to delete VFS file.');
        }
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

        $fileList = $this->_db->getAll(sprintf('SELECT vfs_name, vfs_type, vfs_modified, vfs_owner FROM %s
                                               WHERE vfs_path = %s',
                                               $this->_params['table'],
                                               $this->_db->quote($path)));
        foreach ($fileList as $line) {
            $file['name'] = $line[0];

            if ($line[1] == HORDE_VFS_FILE) {
                $name = explode('.', $line[0]);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = strtolower($name[count($name) - 1]);
                }

                $file['size'] = filesize($this->_getNativePath($path, $line[0]));
            } else if ($line[1] == HORDE_VFS_FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line[2];
            $file['owner'] = $line[3];
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
                return PEAR::raiseError(_("No configuration information specified for SQL-File VFS."));
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
            if (!isset($this->_params['vfsroot'])) {
                return PEAR::raiseError(_("Required 'vfsroot' not specified in VFS configuration."));
            }
            if (!isset($this->_params['table'])) {
                $this->_params['table'] = 'horde_vfs';
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, true);
            if (DB::isError($this->_db)) {
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
        if (!empty($name)) {
            $name = DIRECTORY_SEPARATOR . $name;
        }
        if (!empty($path) || isset($path)) {
            return $this->_params['vfsroot'] . DIRECTORY_SEPARATOR . $path . $name;
        } else {
            return $this->_params['vfsroot'] . $name;
        }
    }

    /**
     * Return a full SQL filename on the native filesystem, from a VFS
     * path and name.
     *
     * @param string $path  The VFS file path.
     * @param string $name  The VFS filename.
     *
     * @return string  The full native filename.
     */
    function _getSQLNativePath($path, $name)
    {
        if (empty($path)) {
            return $name;
        }

        return $path . DIRECTORY_SEPARATOR . $name;
    }

}
