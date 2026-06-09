<?php
/**
 * IPB SDK - Version 1.0
 * Help/FAQ Module.
 *
 * Designed for Invision Power Board Version 1.2 - 1.3.
 *
 * Class which works with the board's Help/FAQ files.
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

class SDK_Help {
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
	 * @param array Configuration options for the Help module (for future use)
	 * @return  object Instance of SDK_Help
	 */
	function SDK_Help(&$SDK, $options) {
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
	 * Returns array with list of all FAQs.
	 *
	 * @access  public
	 * @author  Cow <khlo@global-centre.com
	 * @return  array   FAQs
	 */
	function list_faqs () {
		// Reference DB
		$DB =& $this->SDK->DB;

		$faqs = array();
		$DB->query ("SELECT * FROM acf_faq");
		while ($row = $DB->fetch_row()) {
			$faqs[$row['id']] = $row;
		}

		return $faqs;
	}

	/**
	 * Returns info on a FAQ/Help File.
	 *
	 * @access  public
	 * @param   int $id FAQ/Help File ID
	 * @author  Scyth <scyth@wewub.com>
	 * @return  mixed Array with FAQ Information on success, or FALSE on failure.
	 */
	function get_faq ($id) {
		// Reference DB
		$DB =& $this->SDK->DB;

		if ($id) {
			$DB->query ("SELECT * FROM acf_faq WHERE id ='".$id."'");
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