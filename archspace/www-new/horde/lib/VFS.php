<?php
/**
 * Horde VFS API for abstracted file storage and access.
 *
 * $Horde: horde/lib/VFS.php,v 1.21.2.3 2003/01/03 13:23:28 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @package horde.vfs
 * @since   Horde 2.2
 */
class Horde_VFS {

    /**
     * Hash containing connection parameters.
     *
     * @var $_params array
     */
    var $_params = array();

    /**
     * List of additional credentials required for this VFS backend
     * (example: For FTP, we need a username and password to log in to
     * the server with).
     *
     * @var $_credentials array
     */
    var $_credentials = array();

    /**
     * List of permissions and if they can be changed in this VFS
     * backend.
     *
     * @var $_permissions array
     */
    var $_permissions = array(
        'owner' => array('read' => false, 'write' => false, 'execute' => false),
        'group' => array('read' => false, 'write' => false, 'execute' => false),
        'all'   => array('read' => false, 'write' => false, 'execute' => false));

    /**
     * Attempts to return a concrete Horde_VFS instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_VFS subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/VFS/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Horde_VFS  The newly created concrete Horde_VFS instance,
     *                           or false on an error.
     */
    function &factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        /* Return a base Horde_VFS object if no driver is specified. */
        $driver = strtolower($driver);
        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Horde_VFS($params);
        }

        if (@file_exists(dirname(__FILE__) . '/VFS/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/VFS/' . $driver . '.php';
        } else {
            @include_once 'Horde/VFS/' . $driver . '.php';
        }
        $class = 'Horde_VFS_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Horde_VFS instance
     * based on $driver. It will only create a new instance if no
     * Horde_VFS instance with the same parameters currently exists.
     *
     * This should be used if multiple types of file backends (and,
     * thus, multiple Horde_VFS instances) are required.
     *
     * This method must be invoked as: $var = &Horde_VFS::singleton()
     *
     * @param mixed $driver  The type of concrete Horde_VFS subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/VFS/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Horde_VFS  The concrete Horde_VFS reference, or false on an
     *                           error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;
        if (!isset($instances)) {
            $instances = array();
        }

        if (is_array($driver)) {
            $drivertag = implode(':', $driver);
        } else {
            $drivertag = $driver;
        }
        $signature = md5(strtolower($drivertag) . '][' . implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Horde_VFS::factory($driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Check the credentials that we have by calling _connect(), to
     * see if there is a valid login.
     *
     * @return mixed  True on success, PEAR_Error describing the problem
     *                if the credentials are invalid.
     */
    function checkCredentials()
    {
        return $this->_connect();
    }

    /**
     * Set configuration parameters.
     *
     * @param array $params  An associative array, parameter name => parameter value.
     */
    function setParams($params = array())
    {
        foreach ($params as $name => $value) {
            $this->_params[$name] = $value;
        }
    }

    /**
     * Retrieve a file from the VFS.
     *
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string The file data.
     */
    function read($path, $name)
    {
        return PEAR::raiseError('not supported');
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
        return PEAR::raiseError('not supported');
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
        return PEAR::raiseError('not supported');
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
        return PEAR::raiseError('not supported');
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
        return PEAR::raiseError('not supported');
    }

    /**
     * Create a folder in the VFS.
     *
     * @param string $path  The path to the folder.
     * @param string $name  The name of the new folder.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function createFolder($path, $name)
    {
        return PEAR::raiseError('not supported');
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path The path of the folder to delete.
     * @param string $name The name of the folder to delete.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($path, $name)
    {
        return PEAR::raiseError('not supported');
    }

    /**
     * Returns a file list of the directory passed in.
     *
     * @param string $path  The path of the diretory.
     *
     * @return mixed  File list (array) on success or a PEAR_Error
     *                object on failure.
     */
    function listFolder($path)
    {
        return PEAR::raiseError('not supported');
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
        return PEAR::raiseError('not supported');
    }

    /**
     * Return the list of additional credentials required, if any.
     *
     * @return array  Credential list
     */
    function getRequiredCredentials()
    {
        return $this->_credentials;
    }

    /**
     * Return the array specificying what permissions are
     * changeable for this implementation.
     *
     * @return array  Changeable Permisions
     */
    function getModifiablePermissions()
    {
        return $this->_permissions;
    }

    /**
     * Close any resources that need to be closed.
     */
    function _disconnect()
    {
    }

}
