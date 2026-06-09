<?php
/**
 * Prints a useful page with debug information on IPB SDK and the things behind the scene.
 *
 * This extension is loaded from within IPBSDK::sdk_info() and can't be used by itself.
 *
 * @author Cow <khlo@global-centre.com>
 * @see IPBSDK::sdk_info()
 */

if (!$this) return 'Homer bad -- IPB SDK good!';

$info = '';
// removed spaces in <style>, added a, width to .c1 and .c2, .ct, .lt
$info .= '<html><head><title>IPB SDK Information Page</title><style type="text/css">body{font-family:Verdana,Arial,Helvetica,Sans-Serif;font-size:10pt;} h1{font-size:18pt;text-align:center;} h2{font-size:14pt;text-align:center;font-weight:normal;} table{width:100%;} th{font-size:10pt;background-color:#AABFDC;padding:5px;} a {color:#3A4F6C;} .c1{width:25%;text-align:left;background-color:#D1DCEB;vertical-align:middle;font-family:Verdana;font-size:10pt;padding:5px;font-weight:bold;color:#3A4F6C;} .c2{width:75%;background-color:#EEF2F7;font-family:Verdana;font-size:10pt;padding:5px;} .ct{text-align:center} .lt{text-align:left}</style></head><body><h1>IPB SDK Information Page</h1>';

$mysqlinfo = array();
$this->DB->query ('SHOW VARIABLES;');
while ($row = $this->DB->fetch_row()) {
	$mysqlinfo[$row['Variable_name']] = $row['Value'];
}
// anonymous functions to do the HTML ... this is 'hardcore PHP' ;-)
$tbl_op = create_function('$sect,$c1="Name",$c2="Value"', 'static $cnt;$cnt++;return "<h2 id=\"sdkinfo_{$cnt}\">$sect</h2><table cellspacing=\"1\"><tr><th>$c1</th><th>$c2</th></tr>";');
$tbl_tr = create_function('$lbl,$val', 'return "<tr><td class=\"c1\">$lbl</td><td class=\"c2\">$val</td></tr>";');
$tbl_cl = create_function('$t=TRUE', 'return (!$t)?"</table>":"<tr><td colspan=\"2\" class=\"c1 ct\"><a href=\"#top\">[ top ]</a></td></tr></table>";');
// the headlines of all sections. reduce repetitions by using this array
$sections = array('IPB SDK Configuration', 'Cookies', '$ibforums->input', 'Current Member Details', 'Invision Power Board Configuration', 'mySQL System Variables', 'Debug Information', 'IPB SDK Credits',);
// General IPB SDK Information
$info .= '<a name="top"></a><table cellspacing="1"><tr><th class="c1">IPB SDK Information Page</th></tr><tr><th class="c2 lt">';
// loop thru $sections to build the TOC
for($x = 0, $y = count($sections); $x < $y; $x++) {
	$info .= '&nbsp;&middot;&nbsp;<a href="#sdkinfo_' . ($x + 1) . '">' . $sections[$x] . '</a><br />';
}
// updated (c) years ;)
$info .= '<br />&copy; 2003-2004 IPB SDK Development Team</td></tr>';
$info .= $tbl_cl(false);
// IPB SDK Config
$info .= $tbl_op(array_shift($sections));
// I think this better fits here
$info .= $tbl_tr('IPB SDK Version', $this->ipbsdk_version) . $tbl_tr('IPB Version', $GLOBALS['ibforums']->version) . $tbl_tr('PHP Version', phpversion()) . $tbl_tr('Zend Engine Version', zend_version()) . $tbl_tr('mySQL Version', $mysqlinfo['version']) . $tbl_tr('Database Queries Used', $this->DB->query_count);

foreach ($this->ipbsdk_settings as $x => $y) {
	$info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// $_COOKIE
$info .= $tbl_op(array_shift($sections));
foreach ($_COOKIE as $x => $y) {
	if (strstr($x, 'sql_') !== false) continue;
	// deserialize arrays for better reading
	if (version_compare(phpversion(), '4.3.0', '>=') && ($i = @unserialize($y))) eval('$y=print_r($i, 1);');
	$info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// $ibforums->input
$info .= $tbl_op(array_shift($sections));
foreach ($GLOBALS['ibforums']->input as $x => $y) {
	$info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// $GLOBALS['ibforums']->member
$info .= $tbl_op(array_shift($sections));
foreach ($GLOBALS['ibforums']->member as $x => $y) {
	$info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// IPB Config
$info .= $tbl_op(array_shift($sections));
foreach ($GLOBALS['ibforums']->vars as $x => $y) {
	if (strstr($x, 'sql_') === false) $info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// mySQL Details
$info .= $tbl_op(array_shift($sections));
foreach ($mysqlinfo as $x => $y) {
	if (strstr($x, 'sql_') === false) $info .= $tbl_tr($x, $y);
}
$info .= $tbl_cl();
// Other Debug Information
$dbqueries = '';
$errors = '';
foreach ($this->DB->obj['cached_queries'] as $x) {
	$dbqueries .= $x . '<br />';
}
foreach ($this->_errors as $x => $y) {
	$errors .= '<strong>' . $x . '</strong> ' . $y . '<br />';
}
$info .= $tbl_op(array_shift($sections));
$info .= $tbl_tr('SDK Errors', count($this->_errors)) . $tbl_tr('Last SDK Error', $this->_lasterror) . $tbl_tr('Errors Generated', $errors) . $tbl_tr('Database Queries Count', $this->DB->query_count) . $tbl_tr('SQL Queries Run', $dbqueries);
$info .= $tbl_cl();
// Credits
// just an idea - link to the online function reference as a goodie;
// I dunno the URL syntax, so you need to fix this for Ripper and CTiga
$hp = 'http://ipbsdk.sourceforge.net/';
$credits = array('Cow' => 'Project Founder',
	'CirTap' => 'OOP Version of IPB SDK',
	'Scyth' => 'Various contributions',
	'MrTweakin' => 'Helping sort out the SourceForge Site',
	'Ripper' => 'Contribution to <a href="' . $hp . '/functions/get_skin_info">get_skin_info()</a> function',
	'CTiga' => '<a href="' . $hp . '/functions/get_groupinfo">get_groupinfo()</a> function',
	'Bug Testers and Users' => 'Thanks for using the script :) Special mentions go to everyone on the IBPlanet Forums for their huge support, suggestions and feedback.',
	);

$info .= $tbl_op(array_shift($sections), 'Name', 'Project Involvement');
foreach ($credits as $x => $y) {
	$info .= $tbl_tr($x, $y);
}
// homepage link <g>
$info .= $tbl_cl() . '<p style="text-align:center;margin-top:2em;color:#3A4F6C;"><a href="' . $hp . '">IPB SDK Homepage</a></p>';
$info .= '</body></html>';

echo $info;

return 'Homer bad -- IPB SDK good!';

?>