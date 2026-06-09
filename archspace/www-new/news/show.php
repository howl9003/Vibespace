<?
  include "libnotice.php";
  // Load and Start IPB SDK
  require_once "../ipbsdk_class.inc.php";
  if (!$SDK)
  $SDK =& new IPBSDK();

  // Set Post ID Here. Use $postid = $_GET['postid'] to get from query string.
  $topicid = (@$_REQUEST['id']) ? (int)$_REQUEST['id']: 16;
  if (!empty($_GET['id']))
  {
    $topicid = (int)$_GET['id'];
  }
  // get details about the selected post id
  $info = $SDK->get_topic_info($topicid);

  // a more human readable date format
  $info['post_date'] = date('M j Y, h:i A', $info['post_date']);

  $meminfo = $SDK->get_info();
  $skininfo = $SDK->get_skin_info($SDK->get_skin_id($meminfo['id']));
  if ($info['icon_id'] != "0")
      $iconhtml = "<IMG SRC=".$SDK->board_url."/style_images/".$skininfo['img_dir']."/icon".$info['icon_id'].".gif BORDER=0>";
  else
      $iconhtml = "";


  // for the sake of this example increment counter for the "next" link.
  // It may result in a non-existing topicid because the topic IDs on a busy
  // board are not in sequence
  $next_id = $topicid +1;

  // TODO: get rid of all these damned echos
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
  <HEAD>
    <TITLE>ARCPHSPACE</TITLE>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; iso-8859-1">
    <META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">
    <META HTTP-EQUIV="Expires" CONTENT="0">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <LINK REL="stylesheet" HREF="/archspace.css" TYPE="text/css">

    <LINK REL="stylesheet" HREF="/cssLib.css" TYPE="text/css">
  </HEAD>
  <BODY BGCOLOR="#000000" TOPMARGIN="0">
    <CENTER>
      <TABLE CELLSPACING="0" CELLPADDING="0" WIDTH="800" BORDER="
      0">
        <TBODY>
          <TR>
            <TD COLSPAN="13">
              <IMG HEIGHT="21" SRC="/images/top_01.jpg" WIDTH="
              800">

            </TD>
            <TD>
              <IMG HEIGHT="21" SRC="/images/spacer.gif" WIDTH="1">
            </TD>
          </TR>
          <TR>
            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_02.jpg" WIDTH="46">
            </TD>

            <TD ROWSPAN="2">
              <IMG HEIGHT="10" SRC="/images/top_03.jpg" WIDTH="
              108">
            </TD>
            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_04.jpg" WIDTH="91">
            </TD>
            <TD ROWSPAN="2">
              <IMG HEIGHT="10" SRC="/images/top_05.jpg" WIDTH="34">
            </TD>

            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_06.jpg" WIDTH="47">
            </TD>
            <TD>
              <IMG HEIGHT="9" SRC="/images/top_07.jpg" WIDTH="51">
            </TD>
            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_08.jpg" WIDTH="42">
            </TD>

            <TD ROWSPAN="2">
              <IMG HEIGHT="10" SRC="/images/top_09.jpg" WIDTH="89">
            </TD>
            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_10.jpg" WIDTH="46">
            </TD>
            <TD>
              <IMG HEIGHT="9" SRC="/images/top_11.jpg" WIDTH="88">
            </TD>

            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_12.jpg" WIDTH="43">
            </TD>
            <TD ROWSPAN="2">
              <IMG HEIGHT="10" SRC="/images/top_13.jpg" WIDTH="73">
            </TD>
            <TD ROWSPAN="3">
              <IMG HEIGHT="56" SRC="/images/top_14.jpg" WIDTH="42">
            </TD>

            <TD>
              <IMG HEIGHT="9" SRC="/images/spacer.gif" WIDTH="1">
            </TD>
          </TR>
          <TR>
            <TD ROWSPAN="2">
              <IMG HEIGHT="47" SRC="/images/top_15.jpg" WIDTH="51">
            </TD>
            <TD ROWSPAN="2">

              <IMG HEIGHT="47" SRC="/images/top_16.jpg" WIDTH="88">
            </TD>
            <TD>
              <IMG HEIGHT="1" SRC="/images/spacer.gif" WIDTH="1">
            </TD>
          </TR>
          <TR>
            <TD>
              <IMG HEIGHT="46" SRC="/images/top_17.jpg" WIDTH="
              108">

            </TD>
            <TD>
              <IMG HEIGHT="46" SRC="/images/top_18.jpg" WIDTH="34">
            </TD>
            <TD>
              <IMG HEIGHT="46" SRC="/images/top_19.jpg" WIDTH="89">
            </TD>
            <TD>
              <IMG HEIGHT="46" SRC="/images/top_20.jpg" WIDTH="73">

            </TD>
            <TD>
              <IMG HEIGHT="46" SRC="/images/spacer.gif" WIDTH="1">
            </TD>
          </TR>
        </TBODY>
      </TABLE>
<?
  echo "<TABLE WIDTH=610 BORDER=0 CELLSPACING=0 CELLPADDING=0>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD CLASS=maintext><IMG SRC=/image/as_login/news/news_title.gif WIDTH=245 HEIGHT=42></TD>";
  echo "  </TR>";
  echo "  <TR>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TH CLASS=maintext>Topic: $iconhtml<a href='{$SDK->board_url}/index.php?showtopic={$info['tid']}'>";
  echo $SDK->bbcode2html($info['title'])."</a><BR><small>Description: ".$SDK->bbcode2html($info['description'])."</small></TH>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TH CLASS=maintext><small>Posted by <a href='{$SDK->board_url}/index.php?showuser={$info['author_id']}'>
            {$info['author_name']}</a></small>
            on {$info['post_date']}</TH>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>";
  echo "      <TABLE WIDTH=500 BORDER=0 CELLSPACING=1 CELLPADDING=0>";
  echo "        <TR>";
  echo "          <TD CLASS=maintext>{$info['post']}</TD>";
  echo "        </TR>";
  echo "      </TABLE>";
  echo "    </TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD CLASS=pointer_h><IMG SRC=/image/as_login/bu_back.gif onClick=history.back()></TD>";
  echo "  </TR>";
  echo "  <TR ALIGN=CENTER>";
  echo "    <TD>&nbsp;</TD>";
  echo "  </TR>";
  echo "</TABLE>";
  echo "</CENTER>";
  echo "</BODY>";
  echo "</HTML>";
  ?>
