use Mysql;

sub GetAdLine
{
	print "ADLINE";
	my $ad_line = "\n<A HREF=\"http://archmage.magewar.com/archmage\"><IMG SRC=\"http://image.magewar.com/img/archmagebanner.gif\" ALT=\"Play Archmage!\" BORDER=0></A>\n";
	my $ad_line = "\n<A HREF=\"http://archmage.magewar.com/archmage\"><IMG SRC=\"http://image.magewar.com/img/archmagebanner.gif\" ALT=\"Play Archmage!\" BORDER=0></A>\n";
	
	my $mysql;
	$mysql = Mysql->connect('localhost', 'Ad', 'jejak', 'ehlswkdrnr') 
			or return ($ad_line);

	my($num_p, $num_s, $res, $ctxt, $cval);	

	$num_p = 0;
	$num_s = 0;

	my($AdInfoP, $AdInfoS);

	$AdInfoP = 0;
	$AdInfoS = 0;

	local($chip, $val);
	foreach (split(/; /, $ENV{'HTTP_COOKIE'})) 
	{
		($chip, $val) = split(/=/,$_,2); 
		$chip =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;
		$val =~ s/%([A-Fa-f0-9]{2})/pack("c",hex($1))/ge;

		if ($chip eq "AdInfoP") {
			 $AdInfoP = $val;
#			 $ad_line .= "AdInfoP:$val<BR>";
		} elsif ($chip eq "AdInfoS") 
		{
			$AdInfoS = $val;
#			$ad_line .= "AdInfoS:$val<BR>";
		}
	}

	$mysql->query("LOCK TABLES Ad READ");

	$res = $mysql->query("SELECT count(*), ad_type FROM Ad GROUP BY ad_type");

#	$ad_line .= "TEST,$AdInfoP,$AdInfoS<BR>";
	while(my($num1, $type1) = $res->fetchrow)
	{
#		$ad_line .= "<$num1, $type1>";
		if ($type1 eq 'P')
		{
			$num_p = $num1;
		} elsif ($type1 eq 'S')
		{
			$num_s = $num1;
		}
	}

	if (($num_p > 0) && ($AdInfoP == 0))
	{
		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad WHERE ad_type ='P' ORDER BY priority, ad_id LIMIT 1");
#		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad ORDER BY priority, ad_id LIMIT 1"); # edited by wrice 20000517
                my($ad_id, $line) = $res->fetchrow;
                $ctxt = "AdInfoP";
                $cval = 1;
		$ad_line = $line;
	} elsif (($num_p > 0) && ($AdInfoP < $num_p)) {
		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad WHERE ad_type ='P' ORDER BY priority, ad_id LIMIT 1");
		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad WHERE ad_type ='P' ORDER BY priority, ad_id LIMIT 1");
                my($ad_id, $ine) = $res->fetchrow;
                $ctxt = "AdInfoP";
                $cval = $AdInfoP+1;
		$ad_line = $line;
	} elsif (($num_s > 0) && ($AdInfoS == 0)) {
		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad WHERE ad_type ='S' ORDER BY priority, ad_id LIMIT 1");
                my($ad_id, $ad_line) = $res->fetchrow;
                $ctxt = "AdInfoS";
                $cval = 1;
		$ad_line = $line;
	} elsif (($num_s > 0) && ($AdInfoS < $num_s)) {
		$res = $mysql->query("SELECT ad_id, ad_text FROM Ad WHERE ad_type ='S' ORDER BY priority, ad_id LIMIT 1");
                my($ad_id, $line) = $res->fetchrow;
      		$ctxt = "AdInfoS";
                $cval = $AdInfoS+1;	
		$ad_line = $line;
	} else {
		$ctxt = "AdInfoS";
		$cval = 0;
	}

	$ad_line = "\n<IMG SRC=\"/Ad/scookie.phtml?ctxt=$ctxt&cval=$cval\" WIDTH=5 HEIGHT=5 ALT=\"\">\n$ad_line";

	$mysql->query("UNLOCK TABLES");

	return ($ad_line);
}
