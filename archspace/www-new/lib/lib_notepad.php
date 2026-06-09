<?php
/**
 * IPB SDK - Version 1.0
 * Member Notepad Module
 *
 * Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Works with member notepads.
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

class SDK_Notepad {
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
	 * @param object A reference of the global $SDK variable
	 * @param array Configuration options for the Language module (for future use)
	 * @author  Cow <khlo@global-centre.com>
	 */
	function SDK_Notepad(&$SDK, $options) {
		// Create reference to main SDK class
		$this->SDK =& $SDK;
	}

	/**
	 * Returns module version.
	 *
	 * @access  public
	 * @author  Cow <khlo@global-centre.com>
	 * @return  string Module Version Number
	 */
	function get_version() {
		return $this->version;
	}

	/**
	 * Gets the contents of a user's notepad.
	 *
	 * @access  public
	 * @author  Cow <khlo@global-centre.com>
	 * @param   int $memberid Member ID
	 * @return  string   Notepad
	 */
	function get_contents ($memberid="") {
		// Create a reference
		$DB =& $this->SDK->DB;

		// If we don't have a member id get the current members id
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}

		if ($this->SDK->get_advinfo($memberid)) {
			$DB->query ("SELECT notes FROM acf_member_extra WHERE id='".intval($memberid)."'");

			if ($row = $DB->fetch_row()) {
				return $row['notes'];
			}
			else {
				return "";
			}
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Updates the content of a user's notepad.
	 *
	 * @access  public
	 * @author  Cow <khlo@global-centre.com>
	 * @param   string  $contents   Notepad Contents
	 * @param   int     $memberid   Member ID
	 * @return  bool   Success
	 */
	function set_contents ($contents="", $memberid="") {
		// Create a reference
		$DB =& $this->SDK->DB;

		// If we don't have a member id get the current members id
		if (!$memberid) {
			$memberid = $GLOBALS['ibforums']->member['id'];
		}

		$contents = $this->SDK->makesafe($contents);

		$DB->query ("UPDATE acf_member_extra SET notes='".$contents."' WHERE id='".intval($memberid)."'");
		if ($DB->get_affected_rows()) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}
}

?>