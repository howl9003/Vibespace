<?
if (!$libadmin_include)
$libadmin_include = true;
{
Function has_today( $aYearString, $aMonthString, $aStartDay, $aEndDay)
{
	$Year = date("Y");
	$Month = date("n");
	$Day = date("j");

	if ($aYearString == $Year && $aMonthString == $Month &&
		$Day >= $aStartDay && $Day <= $aEndDay) return true;

	return false;
}

Function my_array_merge($aArray1, $aArray2)
{
	$Result = array();

	$Index = 0;

	for ($i=0 ; $i<count($aArray1) ; $i++)
	{
		$Result[$Index] = $aArray1[$i];
		$Index++;
	}

	for ($i=0 ; $i<count($aArray2) ; $i++)
	{
		$Result[$Index] = $aArray2[$i];
		$Index++;
	}

	return $Result;
}

Function get_log_by_type($aType, $aYear, $aMonth, $aStartDay, $aEndDay)
{
	if ($aType == "NEW_ACCOUNT")
	{
		$InitLogPath = PORTAL_LOG_PATH;
		$InitLogFile = PORTAL_LOG_FILE;
	}
	else
	{
		$InitLogPath = SYSTEM_LOG_PATH;
		$InitLogFile = SYSTEM_LOG_FILE;
	}

	$Year = $aYear;
	$Month = $aMonth;

	$Result = array();

	for ($i=$aStartDay+1 ; $i<=$aEndDay+1 ; $i++)
	{
		if ($i == 32)
		{
			$Day = 1;
			$Month++;

			if ($Month == "13")
			{
				$Month = "01";
				$Year++;
			}
		}
		else
		{
			$Day = $i;
		}

		$YearString = substr('0'.$Year, -2);
		$MonthString = substr('0'.$Month, -2);
		$DayString = substr('0'.$Day, -2);

		$LogPath = $InitLogPath."log".$YearString.$MonthString."/";
		$LogFile = $InitLogFile.$MonthString.$DayString;

		exec("grep ADMIN_".$aType." ".$LogPath.$LogFile, $Display);
		$Result = my_array_merge($Result, $Display);
	}

	if (has_today($aYear, $aMonth, $aStartDay, $aEndDay))
	{
		exec("grep ADMIN_".$aType." ".$InitLogPath.$InitLogFile, $Display);
		$Result = my_array_merge($Result, $Display);
	}

	return $Result;
}
}
?>
