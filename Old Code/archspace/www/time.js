/*****************************
 time.js
 by TheDAZ
*****************************/

function turn_time()
{
/*
  var cur_year = cur.substring(9, 13);
  var cur_month = cur.substring(14, 16);
  var cur_day = cur.substring(17, 19);
  var cur_hour = cur.substring(25, 27);
  var cur_minute = cur.substring(28, 30);
  var cur_second = cur.substring(31, 33);
  var cur_ampm = cur.substring(34, 36);
*/

  myDate = new Date();

  // 파싱한 날짜로 셋팅
//  myDate.setFullYear(cur_year, cur_month, cur_day);
//  myDate.setHours(cur_hour, cur_minute, cur_second);


  // 날짜 얻음
  CurYear = myDate.getYear();
  CurMonth = myDate.getMonth();
  CurDay = myDate.getDay();
  CurHour = myDate.getHours() - 12;
  CurMinute = myDate.getMinutes();
  CurSecond = myDate.getSeconds();

  // 한자리 숫자면 앞에 0 붙이기
  if (CurMonth > 0 && CurMonth < 9)
  {
    CurMonth = "0" + CurMonth;
  }
  if (CurDay > 0 && CurDay < 9)
  {
    CurDay = "0" + CurDay;
  }
  if (CurHour > 0 && CurHour < 9)
  {
    CurHour = "0" + CurHour;
  }
  if (CurMinute > 0 && CurMinute < 9)
  {
    CurMinute = "0" + CurMinute;
  }
  if (CurSecond > 0 && CurSecond < 9)
  {
    CurSecond = "0" + CurSecond;
  }

  // 텍스트박스에 출력
  document.all.cur_time.value = "Current Time: " + CurYear + "/" + CurMonth + "/" + CurDay + " " + CurHour + ":" + CurMinute + ":" + CurSecond;
}
