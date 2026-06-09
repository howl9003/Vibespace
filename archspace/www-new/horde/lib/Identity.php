<?php
/*
 * $Horde: horde/lib/Identity.php,v 1.4.2.13 2003/04/28 19:59:07 jan Exp $
 *
 * Copyright 2001-2003 Jan Schneider <jan@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * This class provides an interface to all identities a user might
 * have. Its methods take care of any site-specific restrictions
 * configured in prefs.php and conf.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3.5
 * @package horde.identity
 */
class Identity {

    /**
     * Array containing all the user's identities.
     * @var array $identities
     */
    var $identities = array();

    /**
     * A pointer to the user's standard identity.
     * This one is used by the methods returning values
     * if no other one is specified.
     * @var integer $default
     */
    var $default = 0;

    /**
     * Array containing all of the properties in this identity,
     * excluding the id.
     * @var array $properties
     */
    var $properties = array();

    /**
     * Reference to the prefs object that this Identity points to.
     * @var object Prefs $prefs
     */
    var $prefs;

    /**
     * Reads all the user's identities from the prefs object or builds
     * a new identity from the standard values given in prefs.php.
     *
     * @param optional string $user  If specified, we read another user's
     *                               identities instead of the current user.
     */
    function Identity($user = null)
    {
        if (is_null($user)) {
            $this->prefs = &$GLOBALS['prefs'];
            $this->default = $this->prefs->getValue('default_identity');
            $this->identities = @unserialize($this->prefs->getValue('identities'));
        } else {
            global $registry;
            $this->prefs = &Prefs::singleton($GLOBALS['conf']['prefs']['driver'], $registry->getApp(), '', '', $GLOBALS['conf']['prefs']['params']);
            $this->prefs->setDefaults($registry->getParam('fileroot', 'horde') . '/config/prefs.php');
            $this->prefs->setDefaults($registry->getParam('fileroot') . '/config/prefs.php');

            $vals = $this->prefs->getPref($user, array('default_identity', 'identities'));
            $this->default = isset($vals['default_identity']) ? $vals['default_identity'] : 0;
            $this->identities = @unserialize($vals['identities']);
        }

        if (!is_array($this->identities) || (count($this->identities) <= 0)) {
            $identity = array('id' => _("Default Identity"));
            foreach ($this->properties as $key) {
                $identity[$key] = $this->prefs->getValue($key);
            }

            $this->identities[] = $identity;
        }
    }

    /**
     * Saves all identities in the prefs backend.
     */
    function save()
    {
        $this->prefs->setValue('identities', serialize($this->identities));
        $this->prefs->setValue('default_identity', $this->default);
        $this->prefs->store();
    }

    /**
     * Adds a new empty identity to the array of identities.
     * @return integer      The pointer to the created identity
     */
    function add()
    {
        $this->identities[] = array();
        return count($this->identities) - 1;
    }

    /**
     * Remove an identity from the array of identities
     * @param integer $identity The pointer to the identity to be removed
     * @return array            The removed identity
     */
    function delete($identity)
    {
        $deleted = array_splice($this->identities, $identity, 1);
        foreach ($this->identities as $id => $null) {
            if ($this->setDefault($id)) {
                break;
            }
        }
        $this->save();
        return $deleted;
    }

    /**
     * Returns a pointer to the current default identity.
     * @return integer      The pointer to the current default identity
     */
    function getDefault()
    {
        return $this->default;
    }

    /**
     * Sets the current default identity.
     * If the identity doesn't exist, the old default identity
     * stays the same.
     * @param integer $identity     The pointer to the new default identity
     * @return boolean              True on success, false on failure
     */
    function setDefault($identity)
    {
        if (isset($this->identities[$identity])) {
            $this->default = $identity;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns a property from one of the identities. If this value doesn't exist
     * or is locked, the property is retrieved from the prefs backend.
     * @param string $key           The property to retrieve.
     * @param integer $identity     (optional) The identity to retrieve the property from
     * @return mixed                The value of the property.
     */
    function getValue($key, $identity = null)
    {
        if (!isset($identity) || !isset($this->identities[$identity])) {
            $identity = $this->default;
        }

        if (!isset($this->identities[$identity][$key]) || $this->prefs->isLocked($key)) {
            $val = $this->prefs->getValue($key);
        } else {
            $val = $this->identities[$identity][$key];
        }

        return $val;
    }

    /**
     * Returns an array with the specified property from all existing
     * identities.
     * @param string $key       The property to retrieve.
     * @return array            The array with the values from all identities
     */
    function getAll($key)
    {

        $list = array();
        foreach ($this->identities as $identity => $null) {
            $list[$identity] = $this->getValue($key, $identity);
        }
        return $list;
    }

    /**
     * Sets a property with a specified value.
     * @param string $key       The property to set
     * @param mixed $val        The value to which the property should be set
     * @param integer $identity (optional) The identity to set the property in
     * @return boolean          True on success, false on failure (property was locked)
     */
    function setValue($key, $val, $identity = null)
    {
        if (!isset($identity)) {
            $identity = $this->default;
        }

        if (!$this->prefs->isLocked($key)) {
            $this->identities[$identity][$key] = $val;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns true if all properties are locked and therefore nothing
     * in the identities can be changed.
     * @return boolean          True if all properties are locked, false otherwise
     */
    function isLocked()
    {
        foreach ($this->properties as $key) {
            if (!$this->prefs->isLocked($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if the given address belongs to one of the identities.\
     *
     * @param string $key          The identity key to search.
     * @param string $value        The value to search for in $key.
     *
     * @return boolean             True if the $value was found in $key.
     */
    function hasValue($key, $valueA)
    {
        $list = $this->getAll($key);
        foreach ($list as $valueB) {
            if (strpos(strtolower($valueA), strtolower($valueB)) !== false) {
                return true;
            }
        }
        return false;
    }

}
