<?php
/**
 * IPB SDK - Version 1.0
 * Calendar module.
 *
 * Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Contains various functions which allow you to interact with the board's calendar.
 *
 * Code (c) 2003-2004 IPB SDK Development Team
 * http://ipbsdk.sourceforge.net
 *
 * @package    IPBSDK
 * @subpackage Modules
 * @author     IPB SDK Development Team
 * @date       04/27/2004
 * @version    0.2
 * @copyright  2003-2004 IPB SDK Development Team
 */

class SDK_Calendar {
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
	var $version = "0.2";

	/**
	 * Class Constructor
	 *
	 * @access  public
	 * @author  Cow <khlo@global-centre.com>
	 * @param object A reference of the global $SDK variable
	 * @param array Configuration options for the Language module (for future use)
	 * @return  object
	 */
	function SDK_Calendar(&$SDK, $options) {
			$this->SDK =& $SDK; // Create reference to main SDK class
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
	 * Adds a new calender event.
	 *
	 * The current member needs permission to add events to the calendar.
	 *
	 * @access  public
	 * @param   string  $title  Event Title
	 * @param   string  $text   Event Description
	 * @param   int     $day    Event Day
	 * @param   int     $month  Event Month
	 * @param   int     $year   Event Year
	 * @author  Cow <khlo@global-centre.com>
	 * @return  bool Success.
	 */
	function add_event ($title, $text, $day, $month, $year) {
		// Create a reference so we don't have to enter
		//something like $this->SDK->DB->query :)
		$DB =& $this->SDK->DB;
		$lang =& $this->SDK->lang;

		// Check permissions
		$member = $this->SDK->get_advinfo();
		if (!$member['g_calendar_post']) {
			$this->SDK->sdkerror($lang['sdk_noperms']);
			return FALSE;
		}

		$day = intval($day);
		$month = intval($month);
		$year = intval($year);
		$stamp = mktime(0,0,0,$month,$day,$year);
		$title = $this->SDK->makesafe($title);
		$text = $this->SDK->makesafe($text);

		$DB->query ("INSERT INTO acf_calendar_events (userid,year,month,mday,title,event_text,read_perms,unix_stamp) VALUES ('".$GLOBALS['ibforums']->member['id']."', '{$year}', '{$month}', '{$day}', '{$title}', '{$text}', '*', '{$stamp}');");

		return $DB->get_insert_id();
	}

	/**
	 * Returns information on a calendar event.
	 *
	 * @access  public
	 * @param   int $id Calendar Event ID
	 * @author  Scyth <scyth@wewub.com>
	 * @return  mixed Array with Event Information on success, boolean FALSE on failure
	 */
	function get_event_info ($id) {
		// Reference DB
		$DB =& $this->SDK->DB;

		if ($id) {
			$DB->query ("SELECT * FROM acf_calendar_events WHERE eventid='".$id."'");

			if ($row = $DB->fetch_row()) {
				return $row;
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