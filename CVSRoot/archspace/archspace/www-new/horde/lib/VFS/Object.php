<?php

require_once dirname(__FILE__) . '/../VFS.php';

/**
 * A wrapper for the Horde VFS class to return objects, instead of arrays.
 *
 * $Horde: horde/lib/VFS/Object.php,v 1.1.2.4 2003/01/03 13:24:43 jan Exp $
 *
 * Copyright 2002-2003 Jon Wood <jon@jellybob.co.uk>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Wood <jon@jellybob.co.uk>
 * @version $Revision: 1.1.1.1 $
 * @package horde.vfs
 * @since   Horde 2.2
 */
class Horde_VFS_Object {

    /**
     * The actual vfs that does the work
     *
     * @var $_vfs object Horde_VFS
     */
    var $_vfs;

    /**
     * The current path that has been passed to listFolder, if this
     * changes, the list will be rebuilt.
     *
     * @var $_currentPath String
     */
    var $_currentPath;

    /**
     * The return value from a standard Horde_VFS listFolder call, to
     * be read with the Object listFolder.
     *
     * @var $_folderList Array
     */
    var $_folderList;

    /**
     * Constructor, if you pass this an existing Horde_VFS object,
     * then it will be used as the VFS object for this object.
     *
     * @param object Horde_VFS The VFS object to wrap.
     */
    function Horde_ObjectVFS($vfs)
    {
        if (isset($vfs)) {
            $this->_vfs = $vfs;
        }
    }

    /**
     * Attempts to return a concrete Horde_ObjectVFS instance based on
     * $driver.
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
     * @return object Horde_ObjectVFS  The newly created concrete Horde_ObjectVFS instance,
     *                                 or false on an error.
     */
    function &factory($driver, $params = array())
    {
        $vfs = Horde_VFS::factory($driver, $params = array());
        return new Horde_ObjectVFS($vfs);
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
     * @return object Horde_ObjectVFS  The concrete Horde_ObjectVFS reference, or false on an
     *                                 error.
     */
    function &singleton($driver, $params = array())
    {
        $vfs = &Horde_VFS::singleton($driver, $params = array());
        return new Horde_ObjectVFS(&$vfs);
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
        return $this->_vfs->checkCredentials();
    }

    /**
     * Set configuration parameters.
     *
     * @param array $params  An associative array, parameter name => parameter value.
     */
    function setParams($params = array())
    {
        $this->_vfs->setParams($params);
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
    function read($path)
    {
        return $this->_vfs->read(dirname($path), basename($path));
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
    function write($path, $tmpFile, $autocreate = false)
    {
        return $this->_vfs->write(dirname($path), basename($path), $tmpFile, $autocreate = false);
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
    function writeData($path, $data, $autocreate = false)
    {
        return $this->_vfs->writeData(dirname($path), basename($path), $data, $autocreate = false);
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFile($path)
    {
        return $this->_vfs->deleteFile(dirname($path), basename($path));
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
    function rename($oldpath, $newpath)
    {
        return $this->_vfs->rename(dirname($oldpath), basename($oldpath), dirname($newpath), basename($newpath));
    }

    /**
     * Create a folder in the VFS.
     *
     * @param string $path  The path to the folder.
     * @param string $name  The name of the new folder.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function createFolder($path)
    {
        return $this->_vfs->createFolder(dirname($path));
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path The path of the folder to delete.
     * @param string $name The name of the folder to delete.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function deleteFolder($path)
    {
        return $this->_vfs->deleteFolder(dirname($path));
    }

    /**
     * Returns a Horde_VFS_ListItem object if the folder can
     * be read, or a PEAR_Error if it can't be. Returns false once
     * the folder has been completely read.
     *
     * @param string $path  The path of the diretory.
     *
     * @return mixed  File list (array) on success, a PEAR_Error
     *                object on failure, or false if the folder is
     *                completely read.
     */
    function listFolder($path)
    {
        if (!($path === $this->_currentPath)) {
            $folderList = $this->_vfs->listFolder($path);
            if ($folderList) {
                $this->_folderList = $folderList;
                $this->_currentPath = $path;
            } else {
                return PEAR::raiseError("Couldn't read $path.");
            }
        }

        require_once dirname(__FILE__) . '/ListItem.php';
        if ($file = array_shift($this->_folderList)) {
            $file = &new Horde_VFS_ListItem($path, $file);
            return $file;
        } else {
            return false;
        }
    }

    /**
     * Changes permissions for an Item on the VFS.
     *
     * @param string $path Holds the path of directory of the Item.
     * @param string $name Holds the name of the Item.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function changePermissions($path, $permission)
    {
        return $this->_vfs->changePermissions(dirname($path), basename($path), $permission);
    }

    /**
     * Return the list of additional credentials required, if any.
     *
     * @return array  Credential list
     */
    function getRequiredCredentials()
    {
        return $this->_vfs->getRequiredCredentials();
    }

    /**
     * Return the array specificying what permissions are
     * changeable for this implementation.
     *
     * @return array  Changeable Permisions
     */
    function getModifiablePermissions()
    {
        return $this->_vfs->getModifiablePermissions();
    }

    /**
     * Close any resources that need to be closed.
     */
    function _disconnect()
    {
        return $this->_vfs->_disconnect();
    }

}
