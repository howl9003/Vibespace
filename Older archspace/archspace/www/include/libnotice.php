<?
$NEWS_FORUM=3;

if (!$libnotice_include)
{
	$libnotice_include = true;
	include "libstd.php";
	
	// Retrieves and displays $row rows from the database
	function notice($row)
	{
    //include "../ipbsdk_class.inc.php";
 	  global $NEWS_FORUM;
    if (!$SDK)
       $SDK =& new IPBSDK();
//      $info['post_date'] = date('M-j-y@h:i A', $info['post_date']);
	  create_db_connection("Archspace");
    $res = mysql_query("SELECT tid FROM acf_topics WHERE forum_id=$NEWS_FORUM ORDER BY tid DESC LIMIT $row");
	  if (mysql_num_rows($res) == 0)
    {
      echo "<TR><TD VALIGN=\"TOP\" CLASS=maintext><FONT COLOR=66666>No News Entrys</TD></TR>";
      return;
    }
	  while (list($id) = mysql_fetch_row($res) )
	  {
     // get details about the selected post id
     $topicinfo = $SDK->get_topic_info($id);
	   $topicinfo['post_date'] = date('M j/y', $topicinfo['post_date']);
	   $meminfo = $SDK->get_info();
	   $skininfo = $SDK->get_skin_info($SDK->get_skin_id($meminfo['id']));
	   if ($topicinfo['icon_id'] != "0")
        $iconhtml = "<IMG SRC=".$SDK->board_url."/style_images/".$skininfo['img_dir']."/icon".$topicinfo['icon_id'].".gif BORDER=0>";
     else
        $iconhtml = "&nbsp;";
	   echo "<TR>";
	   echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" WIDTH=75 align=left><FONT COLOR=66666>{$topicinfo['post_date']}</FONT></TD>";
     echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" align=left width=4>$iconhtml</TD>";
	   echo "  <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=left width=201><A HREF=/news/show.php?id={$topicinfo['tid']} onMouseOver=\"window.status=('{$topicinfo['description']}'); return true;\" onMouseOut=\"window.status=(''); return true;\">".$SDK->bbcode2html($topicinfo['title'],"1")."</A></TD>";
     echo "</TR>";
     echo "<TR>";
     echo " <TD VALIGN=\"TOP\" COLSPAN=3 CLASS=\"maintext\"><FONT COLOR=66666><small>".$SDK->bbcode2html($topicinfo['description'],"1")."</small></TD>";
     echo "</TR>";
     echo "<TR>";
     if ($topicinfo['posts'] != "1")
        echo " <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=right><small><A class=small href=\"{$SDK->board_url}/index.php?showtopic={$topicinfo['tid']}\">{$topicinfo['posts']} replies</A> -- </small></TD>";
     else
        echo " <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=right><small><A class=small href=\"{$SDK->board_url}/index.php?showtopic={$topicinfo['tid']}\">{$topicinfo['posts']} reply</A> -- </small></TD>";
     echo " <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=left><small><A class=small href=\"{$SDK->board_url}/index.php?act=Post&CODE=02&f={$topicinfo['forum_id']}&t={$topicinfo['tid']}\">reply</A></small></TD>";
     echo " <TD VALIGN=\"TOP\" CLASS=\"maintext\" ALIGN=right><FONT COLOR=66666><small>Posted by <A class=small href={$SDK->board_url}/index.php?showuser={$topicinfo['author_id']}>{$topicinfo['author_name']}</a></small></TD>";
     echo "</TR>";
	  }
	  mysql_free_result($res);
	}

	// Retrieves and displays ALL entries from the database
	function all()
	{
      require_once "../ipbsdk_class.inc.php";
   	  global $NEWS_FORUM;
      if (!$SDK)
        $SDK =& new IPBSDK();


   	  create_db_connection("Archspace");
	    $res = mysql_query("SELECT tid FROM acf_topics WHERE forum_id=$NEWS_FORUM ORDER BY tid DESC");
  	  if (!$res || mysql_num_rows($res) == 0)
      {
        echo "<TR><TD VALIGN=\"TOP\" ALIGN=\"CENTER\" CLASS=maintext><FONT COLOR=66666>No News Entrys</TD></TR>";
        return;
      }
	
	  while (list($id) = mysql_fetch_row($res))
	   {
      // get details about the selected post id
      $topicinfo = $SDK->get_topic_info($id);
	    $meminfo = $SDK->get_info();
	    $skininfo = $SDK->get_skin_info($SDK->get_skin_id($meminfo['id']));
	    if ($topicinfo['icon_id'] != "0")
         $iconhtml = "<IMG SRC=".$SDK->board_url."/style_images/".$skininfo['img_dir']."/icon".$topicinfo['icon_id'].".gif BORDER=0>";
      else
         $iconhtml = "&nbsp;";

      // a more human readable date format
      $topicinfo['post_date'] = date('M j Y - h:i A', $topicinfo['post_date']);
	    echo "<TR>"; 
	    echo "  <TD CLASS='maintext' align=center><FONT COLOR='666666'>{$topicinfo['post_date']}</FONT></TD>";
      echo "  <TD VALIGN=\"TOP\" CLASS=maintext>$iconhtml</TD>";
      echo "  <TD CLASS='maintext' align=left><A HREF=/news/show.php?id=$id onMouseOver=\"window.status=('{$topicinfo['description']}'); return true;\" onMouseOut=\"window.status=(''); return true;\">".$SDK->bbcode2html($topicinfo['title'])."</A></TD>";
	    echo "</TR>";
	   }
	  mysql_free_result($res);
	}
	}
?>
