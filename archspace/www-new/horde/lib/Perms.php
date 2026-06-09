<?php
/*
 * $Horde: horde/lib/Perms.php,v 1.14.2.8 2003/01/17 10:22:15 jan Exp $
 *
 * Copyright 2001-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once HORDE_BASE . '/lib/Category.php';

// Permissions constants (bitmasks)
/** @constant _PERMS_NONE No rights. */
define('_PERMS_NONE', 1);

/** @constant _PERMS_SHOW Existence of object is known - object is shown to user. */
define('_PERMS_SHOW', 2);

/** @constant _PERMS_READ Contents of the object can be read. */
define('_PERMS_READ', 4);

/** @constant _PERMS_EDIT Contents of the object can be edited. */
define('_PERMS_EDIT', 8);

/** @constant _PERMS_DELETE The object can be deleted. */
define('_PERMS_DELETE', 16);

/**
 * The Perms:: class provides the Horde permissions system.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.perms
 */
class Perms {

    /**
     * Pointer to a category instance to manage the different permissions.
     * @var object Category $perms
     */
    var $perms;

    /**
     * Constructor
     */
    function Perms()
    {
        global $conf;
        $this->perms = &Category::singleton($conf['category']['driver'],
                                            array_merge($conf['category']['params'],
                                                        array('group' => 'horde.perms')));
    }

    /**
     * Attempts to return a reference to a concrete Perms instance.
     * It will only create a new instance if no Perms instance
     * currently exists.
     *
     * This method must be invoked as: $var = &Perms::singleton()
     *
     * @return object Perms  The concrete Perm reference, or false on an
     *                       error.
     */
    function &singleton()
    {
        static $perm;

        if (!isset($perm)) {
            $perm = new Perms();
        }

        return $perm;
    }

    /**
     * Return a new permissions object.
     *
     * @param          string $name     The perm's name.
     * @param optional array  $parents  Any parent permissions to be autocreated.
     *
     * @return object CategoryObject_Permissions A new permissions object.
     */
    function &newPermission($name, $parents = array())
    {
        if (empty($name)) {
            return PEAR::raiseError('Permission names must be non-empty');
        }
        $perm = &new CategoryObject_Permission($name);
        $perm->setPermsOb($this);
        return $perm;
    }

    /**
     * Return a CategoryObject_Permission object corresponding to the named
     * perm, with the users and other data retrieved appropriately.
     *
     * @param string $name The name of the perm to retrieve.
     */
    function &getPermission($name)
    {
        $perm = $this->perms->getCategory($name, 'CategoryObject_Permission');
        if (!PEAR::isError($perm)) {
            $perm->setPermsOb($this);
        }
        return $perm;
    }

    /**
     * Add a perm to the perms system. The perm must first be created
     * with Perm::newPermission(), and have any initial users added to
     * it, before this function is called.
     *
     * @param object CategoryObject_Permission $perm The new perm object.
     * @param optional array $parents            The name of the parent permissions
     *                                           (defaults to the category root)
     *                                           These will be auto-created if necessary.
     */
    function addPermission($perm, $parents = array('-1'))
    {
        if (!((get_class($perm) == 'categoryobject_permission') ||
              is_subclass_of($perm, 'CategoryObject_Permission'))) {
            return PEAR::raiseError('Permissions must be CategoryObject_Permission objects or extend that class.');
        }

        if ($parents[0] == 'root') {
            $parents[0] = -1;
        }
        if ($parents[0] != -1) {
            array_unshift($parents, -1);
        }

        $ppar = $parents[count($parents) - 1];
        while ($parent = array_pop($parents)) {
            if ($parent == -1 || $this->exists($parent)) {
                break;
            }
            $npar = &$this->newPermission($parent);
            $result = $this->addPermission($npar, $parents);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return $this->perms->addCategory($perm, $ppar);
    }

    /**
     * Store updated data - users, etc. - of a perm to the backend
     * system.
     *
     * @param object CategoryObject_Permission $perm   The perm to update.
     */
    function updatePermission($perm)
    {
        if (!((get_class($perm) == 'categoryobject_permission') ||
              is_subclass_of($perm, 'CategoryObject_Permission'))) {
            return PEAR::raiseError('Permissions must be CategoryObject_Permission objects or extend that class.');
        }
        return $this->perms->updateCategoryData($perm);
    }

    /**
     * Remove a perm from the perms system permanently.
     *
     * @param object CategoryObject_Permissionission $perm   The permission to remove.
     * @param string                           $parent (optional) The permission's immediate parent.
     */
    function removePermission($perm, $parent = '0')
    {
        if (!((get_class($perm) == 'categoryobject_permission') ||
              is_subclass_of($perm, 'CategoryObject_Permission'))) {
            return PEAR::raiseError('Permissions must be CategoryObject_Permission objects or extend that class.');
        }
        if ($this->perms->getNumberOfChildren($perm) != 0) {
            return PEAR::raiseError('Cannot remove: children exist');
        }

        return $this->perms->removeCategory($perm->getName(), $parent);
    }

    /**
     * Find out what rights the given user has to this object.
     *
     * @param string $objectpath The full path to the piece of
     *                           content/whatever to check the permissions of.
     * @param string $user The user to check for.
     * @param int    $default What to return if no permissions are explicitly set. Defaults to no permissions.
     *
     * @return int Any permissions the user has, or $default if there
     *             are none.
     */
    function getPermissions($permission, $user, $default = _PERMS_NONE)
    {
        $perm = &$this->getPermission($permission);
        if (!PEAR::isError($perm)) {
            if (isset($perm->data['users'][$user])) {
                return $perm->data['users'][$user];
            } elseif (isset($perm->data['groups']) && is_array($perm->data['groups'])) {
                include_once HORDE_BASE . '/lib/Group.php';
                $groups = &Group::singleton();

                foreach ($perm->data['groups'] as $group => $permission) {
                    if ($groups->userIsInGroup($user, $group, true)) {
                        return $permission;
                    }
                }
            }
        }

        return $default;
    }

    /**
     * Find out if the user has the specified rights to the given object.
     *
     * @param string $permission The permission to check.
     * @param string $user The user to check for.
     * @param int    $perm The permission level that needs to be checked for.
     *
     * @return boolean True if the user has the specified permissions, and
     *                 false otherwise.
     */
    function hasPermission($permission, $user, $perm)
    {
        return ($this->getPermissions($permission, $user) & $perm);
    }

    /**
     * Check if a permission exists in the system.
     *
     * @param string $permission  The permission to check.
     * @param optional string $parent The name of the parent from where
     *                                we want to check.
     *                                0 means every parent
     *
     * @return boolean true if the permission exists, false otherwise.
     */
    function exists($permission, $parent = '0')
    {
        return $this->perms->exists($permission, $parent);
    }

    /**
     * Get a list of parent permissions.
     *
     * @param string $child The name of the child to retrieve parents for.
     * @param optional string $parent The name of the parent from where
     *                           we want to start.
     * @return array [child] [parent] with a tree format
     */
    function getParents($child, $parentfrom = '0')
    {
        return $this->perms->getParents($child, $parentfrom);
    }

}

/**
 * Extension of the CategoryObject class for storing Permission
 * information in the Categories driver. If you want to store
 * specialized Permission information, you should extend this class
 * instead of extending CategoryObject directly.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.perms
 */
class CategoryObject_Permission extends CategoryObject {

    /** The Perms object which this permission came from - needed for
        updating data in the backend to make changes stick, etc.
        @var object Perms $permsOb */
    var $permsOb;

    /**
     * The CategoryObject_Permission constructor. Just makes sure to
     * call the parent constructor so that the perm's name is set
     * properly.
     *
     * @param string $name The name of the perm.
     */
    function CategoryObject_Permission($name)
    {
        parent::CategoryObject($name);
    }

    /**
     * Associates a Perms object with this perm.
     *
     * @param object Perm $permsOb The Perm object.
     */
    function setPermsOb(&$permsOb)
    {
        $this->permsOb = &$permsOb;
    }

    function addUserPermission($user, $permission, $update = true)
    {
        if (empty($user)) {
            return;
        }
        if (isset($this->data['users'][$user])) {
            $this->data['users'][$user] |= $permission;
        } else {
            $this->data['users'][$user] = $permission;
        }
        if ($update) {
            $this->permsOb->updatePermission($this);
        }
    }

    function addGroupPermission($group, $permission, $update = true)
    {
        if (empty($group)) {
            return;
        }
        if (isset($this->data['groups'][$group])) {
            $this->data['groups'][$group] |= $permission;
        } else {
            $this->data['groups'][$group] = $permission;
        }
        if ($update) {
            $this->permsOb->updatePermission($this);
        }
    }

    function removeUserPermission($user, $permission, $update = true)
    {
        if (empty($user)) {
            // return;
        }
        if (isset($this->data['users'][$user])) {
            $this->data['users'][$user] &= ~$permission;
            if (empty($this->data['users'][$user])) {
                unset($this->data['users'][$user]);
            }
            if ($update) {
                $this->permsOb->updatePermission($this);
            }
        }
    }

    function removeGroupPermission($group, $permission, $update = true)
    {
        if (empty($group)) {
            return;
        }
        if (isset($this->data['groups'][$group])) {
            $this->data['groups'][$group] &= ~$permission;
            if (empty($this->data['groups'][$group])) {
                unset($this->data['groups'][$group]);
            }
            if ($update) {
                $this->permsOb->updatePermission($this);
            }
        }
    }

    function save()
    {
        $this->permsOb->updatePermission($this);
    }

    function getUserPermissions()
    {
        return (isset($this->data['users']) && is_array($this->data['users'])) ?
            $this->data['users'] :
            array();
    }

    function getGroupPermissions()
    {
        return (isset($this->data['groups']) && is_array($this->data['groups'])) ?
            $this->data['groups'] :
            array();
    }

}
?>
