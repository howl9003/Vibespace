<?php
/**
 * An item returned from a folder list.
 *
 * $Horde: horde/lib/VFS/ListItem.php,v 1.1.2.4 2003/01/03 13:24:43 jan Exp $
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
class Horde_VFS_ListItem
{

    /**
     * VFS path
     *
     * @var $path string
     */
    var $_path;

    /**
     * Filename
     *
     * @var $name string
     */
    var $_name;

    /**
     * File permissions (*nix format: drwxrwxrwx)
     *
     * @var $perms string
     */
    var $_perms;

    /**
     * Owner user
     *
     * @var $owner string
     */
    var $_owner;

    /**
     * Owner group
     *
     * @var $group string
     */
    var $_group;

    /**
     * Size
     *
     * @var $size string
     */
    var $_size;

    /**
     * Last modified date
     *
     * @var $date string
     */
    var $_date;

    /**
     * Type
     * .*: File extension
     * **none: File not found
     * **sym: Symlink to a symlink
     * **broken: Broken symlink
     * **dir: Directory
     *
     * @var $type string
     */
    var $_type;

    /**
     * Constructor
     * Requires the path to the file, and it's array of properties, returned from a standard
     * Horde_VFS::listFolder() call.
     *
     * @param string $path      The path to the file.
     * @param array  $fileArray An array of file properties.
     */
    function Horde_VFS_ListItem($path, $fileArray)
    {
        $this->_path = $path . '/' . $fileArray['name'];
        $this->_name = $fileArray['name'];
        $this->_dirname = $path;
        $this->_perms = $fileArray['perms'];
        $this->_owner = $fileArray['owner'];
        $this->_group = $fileArray['group'];
        $this->_size = $fileArray['size'];
        $this->_date = $fileArray['date'];
        $this->_type = $fileArray['type'];
    }

}
