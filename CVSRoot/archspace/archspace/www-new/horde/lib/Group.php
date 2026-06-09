<?php
/*
 * $Horde: horde/lib/Group.php,v 1.6.2.11 2003/01/17 10:22:14 jan Exp $
 *
 * Copyright 1999-2003 Stephane Huther <shuther@bigfoot.com>
 * Copyright 2001-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once HORDE_BASE . '/lib/Category.php';

/**
 * The Group:: class provides the Horde groups system.
 *
 * @author  Stephane Huther <shuther@bigfoot.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.group
 */
class Group {

    /**
     * Pointer to a category instance to manage the different groups.
     * @var object Category $groups
     */
    var $groups;

    /**
     * Constructor
     */
    function Group()
    {
        global $conf;
        $this->groups = &Category::singleton($conf['category']['driver'],
                                             array_merge($conf['category']['params'],
                                                         array('group' => 'horde.groups')));
    }

    /**
     * Attempts to return a reference to a concrete Group instance.
     * It will only create a new instance if no Group instance
     * currently exists.
     *
     * This method must be invoked as: $var = &Group::singleton()
     *
     * @return object Group  The concrete Group reference, or false on an
     *                       error.
     */
    function &singleton()
    {
        static $group;

        if (!isset($group)) {
            $group = new Group();
        }

        return $group;
    }

    /**
     * Return a new group object.
     *
     * @param string $name The group's name.
     *
     * @return object CategoryObject_Group A new group object.
     */
    function &newGroup($name)
    {
        if (empty($name)) {
            return PEAR::raiseError('Group names must be non-empty');
        }
        $group = &new CategoryObject_Group($name);
        $group->setGroupOb($this);
        return $group;
    }

    /**
     * Return a CategoryObject_Group object corresponding to the named
     * group, with the users and other data retrieved appropriately.
     *
     * @param string $name The name of the group to retrieve.
     */
    function &getGroup($name)
    {
        $group = $this->groups->getCategory($name, 'CategoryObject_Group');
        if (!PEAR::isError($group)) {
            $group->setGroupOb($this);
        }
        return $group;
    }

    /**
     * Add a group to the groups system. The group must first be
     * created with Group::newGroup(), and have any initial users
     * added to it, before this function is called.
     *
     * @param object CategoryObject_Group $group The new group object.
     * @param optional string $parent            The name of the parent group
     *                                           (defaults to the category root)
     */
    function addGroup($group, $parent = '-1')
    {
        if (!((get_class($group) == 'categoryobject_group') ||
              is_subclass_of($group, 'CategoryObject_Group'))) {
            return PEAR::raiseError('Groups must be CategoryObject_Group objects or extend that class.');
        }
        return $this->groups->addCategory($group, $parent);
    }

    /**
     * Store updated data - users, etc. - of a group to the backend
     * system.
     *
     * @param object CategoryObject_Group $group   The group to update.
     */
    function updateGroup($group)
    {
        if (!((get_class($group) == 'categoryobject_group') ||
              is_subclass_of($group, 'CategoryObject_Group'))) {
            return PEAR::raiseError('Groups must be CategoryObject_Group objects or extend that class.');
        }
        return $this->groups->updateCategoryData($group);
    }

    /**
     * Change the name of a group without changing its contents or
     * where it is in the groups hierarchy.
     *
     * @param object CategoryObject_Group $group   The group to rename.
     * @param string                      $newName The group's new name.
     */
    function renameGroup($group, $newName)
    {
        if (!((get_class($group) == 'categoryobject_group') ||
              is_subclass_of($group, 'CategoryObject_Group'))) {
            return PEAR::raiseError('Groups must be CategoryObject_Group objects or extend that class.');
        }
        $newGroup = $group;
        $newGroup->name = $newName;
        return $this->groups->renameCategory($group, $newGroup);
    }

    /**
     * Remove a group from the groups system permanently.
     *
     * @param object CategoryObject_Group $group  The group to remove.
     * @param string                      $parent (optional) The group's immediate parent.
     */
    function removeGroup($group, $parent = '0')
    {
        if (!((get_class($group) == 'categoryobject_group') ||
              is_subclass_of($group, 'CategoryObject_Group'))) {
            return PEAR::raiseError('Groups must be CategoryObject_Group objects or extend that class.');
        }
        if ($this->groups->getNumberOfChildren($group) != 0) {
            return PEAR::raiseError('Cannot remove: children exist');
        }

        return $this->groups->removeCategory($group, $parent);
    }

    /**
     * Move a group around in the groups hierarchy.
     *
     * @param object CategoryObject_Group $group      The group to move.
     * @param string                      $old_parent The group's old parent.
     * @param string                      $new_parent The new parent.
     */
    function moveGroup($group, $old_parent, $new_parent)
    {
        if (!((get_class($group) == 'categoryobject_group') ||
              is_subclass_of($group, 'CategoryObject_Group'))) {
            return PEAR::raiseError('Groups must be CategoryObject_Group objects or extend that class.');
        }
        return $this->groups->moveCategory($group, $old_parent, $new_parent);
    }

    /**
     * Get a list of the parents of a child group.
     *
     * @param string $group The name of the child group.
     *
     * @return array
     */
    function getGroupParents($group)
    {
        return $this->groups->getParents($group);
    }

    /**
     * Get a list of every user that is a part of this group ONLY.
     *
     * @param string $group The name of the group.
     *
     * @return array The user list.
     * @access public
     */
    function listUsers($group)
    {
        $groupOb = &$this->getGroup($group);
        if (!isset($groupOb->data['users']) ||
            !is_array($groupOb->data['users'])) {
            return array();
        }

        return array_keys($groupOb->data['users']);
    }

    /**
     * Get a list of every user that is part of the specified group
     * and any of its subgroups.
     *
     * @param string $group The name of the parent group.
     *
     * @return array The complete user list.
     * @access public
     */
    function listAllUsers($group)
    {
        // Get a list of every group that is a sub-group of $group.
        $groups = $this->groups->export(CATEGORY_FORMAT_FLAT, $group);
        $groups = array_keys($groups);
        $users = array();
        foreach ($groups as $group) {
            $users = array_merge($users, $this->listUsers($group));
        }
        return array_values(array_flip(array_flip($users)));
    }

    /**
     * Get a list of every group that $user is in.
     *
     * @param string $user  The user to get groups for.
     *
     * @return array  An array of all groups the user is in.
     */
    function getGroupMemberships($user)
    {
        $memberships = array();

        $groups = $this->groups->export(CATEGORY_FORMAT_FLAT);
        array_shift($groups);

        foreach ($groups as $group => $junk) {
            if ($this->userIsInGroup($user, $group)) {
                $memberships[] = $group;
            }
        }

        return $memberships;
    }

    /**
     * Say if a user is a member of a group or not.
     *
     * @param          string  $user      The name of the user.
     * @param          string  $group     The name of the group.
     * @param optional boolean $subgroups Return true if the user is in any subgroups
     *                                    of $group, also.
     *
     * @return boolean
     * @access public
     */
    function userIsInGroup($user, $group, $subgroups = false)
    {
        if ($subgroups) {
            $users = $this->listAllUsers($group);
        } else {
            $users = $this->listUsers($group);
        }
        return in_array($user, $users);
    }

}

/**
 * Extension of the CategoryObject class for storing Group information
 * in the Categories driver. If you want to store specialized Group
 * information, you should extend this class instead of extending
 * CategoryObject directly.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.group
 */
class CategoryObject_Group extends CategoryObject {

    /** The Group object which this group came from - needed for
        updating data in the backend to make changes stick, etc.
        @var object Group $groupOb */
    var $groupOb;

    /**
     * The CategoryObject_Group constructor. Just makes sure to call
     * the parent constructor so that the group's name is set
     * properly.
     *
     * @param string $name The name of the group.
     */
    function CategoryObject_Group($name)
    {
        parent::CategoryObject($name);
    }

    /**
     * Associates a Group object with this group.
     *
     * @param object Group $groupOb The Group object.
     */
    function setGroupOb(&$groupOb)
    {
        $this->groupOb = &$groupOb;
    }

    /**
     * Adds a user to this group, and makes sure that the backend is
     * updated as well.
     *
     * @param string $username The user to add.
     */
    function addUser($username)
    {
        $this->data['users'][$username] = 1;
        if ($this->groupOb->groups->exists($this->getName())) {
            $this->groupOb->updateGroup($this);
        }
    }

    /**
     * Removes a user from this group, and makes sure that the backend
     * is updated as well.
     *
     * @param string $username The user to remove.
     */
    function removeUser($username)
    {
        unset($this->data['users'][$username]);
        $this->groupOb->updateGroup($this);
    }

    /**
     * Get a list of every user that is a part of this group
     * (and only this group)
     *
     * @return array The user list
     * @access public
     */
    function listUsers()
    {
        return $this->groupOb->listUsers($this->name);
    }

    /**
     * Get a list of every user that is a part of this group and
     * any of it's subgroups
     *
     * @return array The complete user list
     * @access public
     */
    function listAllUsers()
    {
        return $this->groupOb->listAllUsers($this->name);
    }

}
?>
