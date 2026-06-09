<?php
// $Horde: horde/lib/SessionCache.php,v 1.5.2.4 2002/06/19 14:58:45 jan Exp $

/**
 * The maximum number of objects that the session cache will hold.
 * @const SESSION_CACHE_SIZE
 */
define('SESSION_CACHE_SIZE', 20);


/**
 * SessionCache:: provides an API for storing a limited number of
 * pieces of data intended to have a short lifetime in a user's
 * session.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since Horde 1.3
 * @package horde
 */
class SessionCache {

    /**
     * Stores a new object in the session cache.
     *
     * @param mixed $object     The object to store in the session cache.
     *
     * @return string           The MD5 string representing the object's ID.
     */
    function putObject($object)
    {
        $store = serialize($object);
        $objectID = md5($store);
        if (!isset($_SESSION['hordeObjectCache'])) {
            global $hordeObjectCache;
            $hordeObjectCache = array();
            $hordeObjectCache[$objectID] = $store;
            session_register('hordeObjectCache');
            $_SESSION['hordeObjectCache'] = &$hordeObjectCache;
        } else {
            /* Prune the cache if there are more than $cacheSize items in it. */
            if (count($_SESSION['hordeObjectCache']) >= SESSION_CACHE_SIZE) {
                array_shift($_SESSION['hordeObjectCache']);
            }
            $GLOBALS['hordeObjectCache'] = &$_SESSION['hordeObjectCache'];
            $GLOBALS['hordeObjectCache'][$objectID] = $store;
        }

        return $objectID;
    }

    /**
     * Retrives an object from the session cache.
     *
     * @param string $objectID  The ID of the object to retrieve.
     *
     * @return mixed            The requested object, or false on failure.
     */
    function getObject($objectID)
    {
        if (!isset($_SESSION['hordeObjectCache'][$objectID])) {
            return false;
        }

        return unserialize($_SESSION['hordeObjectCache'][$objectID]);
    }

}
?>
