<?php
/*
 * $Horde: horde/lib/Cache/file.php,v 1.6.2.3 2003/01/03 12:48:41 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Cache:: class provides a common abstracted interface into the
 * various caching backends.  It also provides functions for checking
 * in, retrieving, and flushing a cache.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.cache
 */
class Cache_file {

    var $dir;
    var $prefix = 'cache_';

    /**
     * Construct a new Cache_file object.
     * @param array $params Parameters to determine where to cache
     */
    function Cache_file($params = array())
    {
        if (!empty($params['dir']) && @is_dir($params['dir'])) {
            $this->dir = $params['dir'];
        } else {
            $this->dir = Horde::getTempDir();
        }

        if (isset($params['prefix'])) {
            $this->prefix = $params['prefix'];
        }
    }

    /**
     * Attempts to store an object in the cache.
     *
     * @param string $oid  Object ID used as the caching key.
     * @param mixed $data  Data to store in the cache.
     * @return boolean     True on success, false on failure.
     */
    function store($oid, &$data)
    {
        $filename = $this->oidToFile($oid);
        $tmp_file = Horde::getTempFile('HordeCache', true, $this->dir);

        if ($fd = fopen($tmp_file, 'w')) {
            if (fwrite( $fd, $data ) < strlen($data)) {
                fclose($fd);
                return false;
            } else {
                fclose($fd);
                rename($tmp_file, $filename);
            }
        }
    }

    /**
     * Map an object ID to a unique filename.
     * @param string $oid Object ID
     * @return string Fully qualified filename.
     */
    function oidToFile($oid)
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->prefix . md5($oid);
    }

    /**
     * Attempts to retrieve a cached object from the filesystem.
     *
     * @param string $oid  Object ID to query.
     * @param int    $type Expiration heuristic.
     *                     CACHE_IMS: Expire if supplied value is greater than cached time
     * @param int $val     Value to supply for the expiration heuristic.
     * @return mixed       Cached data, or false if none was found.
     */
    function query($oid, $type = CACHE_IMS, $val = 0)
    {
        $filename = $this->oidToFile($oid);

        /* An object exists in the cache */
        if (file_exists($filename)) {

           $lastmod = filemtime($filename);
           switch ($type) {

             /* If the object has been created after the supplied
              * value, object is valid */
             case CACHE_IMS:
               if ($val > $lastmod) {
                   @unlink($filename);
               }
               break;

             /* Invalid cache query specified; unlink the object
              * to be safe */
             default:
               @unlink($filename);
               break;
           }

           /* This will fail if the object no longer exists */
           if ($fd = @fopen($filename, 'r')) {
               return fread($fd, filesize($filename));
           }
        }

        /* Nothing cached, return failure */
        return false;
    }

}

?>
