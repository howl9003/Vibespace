<?php
/**
 * IPB SDK - Version 1.0
 ' Language Module.
 *
 * Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Works with the languages system of Invision Board.
 *
 * Code (c) 2003-2004 IPB SDK Development Team
 * http://ipbsdk.sourceforge.net
 *
 * @package    IPBSDK
 * @subpackage Modules
 * @author     IPB SDK Development Team
 * @date       04/27/2004
 * @version    0.1
 * @copyright  2003-2004 IPB SDK Development Team
 */

class SDK_Lang {
	/**
	 * A reference of the main SDK object
	 * @access protected
	 * @var object
	 */
	var $SDK;
	/**
	 * Version number of this module
	 * @access protected
	 * @var string
	 */
	var $version = "0.1";

	/**
	 * Class Constructor
	 *
	 * @author  Cow <khlo@global-centre.com>
	 * @param object A reference of the global $SDK variable
	 * @param array Configuration options for the Language module (for future use)
	 * @return  object
	 */
	function SDK_Lang(&$SDK, $options) {
		// Create reference to main SDK class
		$this->SDK =& $SDK;
	}

	/**
	 * Returns the Calendar module version.
	 *
	 * @author  Cow <khlo@global-centre.com>
	 * @return  string Module Version Number
	 */
	function get_version() {
		return $this->version;
	}

	/**
	 * Lists all installed languages on forum.
	 *
	 * @author  Scyth <scyth@wewub.com>
	 * @author  Cow <khlo@global-centre.com
	 * @return  array List of installed IPB languages
	 */
	function list_all () {
		// Create a reference
		$DB =& $this->SDK->DB;

		// Modified by Cow to allow a little more flexibility
		$DB->query ("SELECT * FROM acf_languages");
		$langs = array();
		while ($row = $DB->fetch_row()) {
			$langs[] = $row;
		}

		return $langs;
	}

	/**
	 * Gets information on a language.
	 *
	 * @author Scyth <scyth@wewub.com>
	 * @param string $id The lanaguge ID as available in the database (acf_languages.lid)
	 * @return mixed Array with Language Information, or FALSE on failure
	 */
	function get_info ($id) {
		// Create a reference
		$DB =& $this->SDK->DB;

		$DB->query ("SELECT * FROM acf_languages WHERE lid='".$id."'");

		if ($row = $DB->fetch_row()) {
			return $row;
		}
 		else {
  			 return FALSE;
		}
	}

	/**
	 * Sets a member's language.
	 *
	 * The member must be logged in
	 *
	 * @access  public
	 * @param   string  $language Language ID accordin to IPB's language table
	 * @param   integer $memberid Member ID
	 * @author  Cow <khlo@global-centre.com>
	 * @author  bool   True on success
	 */
	function set ($language, $memberid="") {
		if ($this->get_info($language)) {
			if ($this->SDK->update_member(array("language" => $language), $memberid)) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
		else {
			return FALSE;
		}
	}
}

?>