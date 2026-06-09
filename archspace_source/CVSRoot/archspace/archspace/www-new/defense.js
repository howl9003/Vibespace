/***********************************
 defense.js
 by TheDAZ
***********************************/
function update(msg)
{
  //document.Fdescription.description.value = msg;
}

/**********************************************************
 fleet battle
**********************************************************/
var flag;
var x, y, xp, yp;
var isNav=false, isIE=false;
var layerStyleRef, layerRef, styleSwitch;
var over = new Array(20);
var fleet_flag = 0;

var fleetName, fleetNameTmp;
var fleetX=-1, fleetY=-1, fleetXtmp, fleetYtmp;

if (parseInt(navigator.appVersion) >= 4)
{
  if (navigator.appName == "Netscape")
  {
    isNav = true;
  }
  else
  {
    isIE = true;
  }
}

// Set zIndex property
function setZIndex(obj, zOrder)
{
  obj.zIndex = zOrder;
}

// Position an object at a specific pixel coordinate
function shiftTo(obj, x, y)
{
  if (isNav)
  {
    obj.moveTo(x,y);
  }
  else
  {
    obj.pixelLeft = x;
    obj.pixelTop = y;
  }
}
// ***End API Functions***

// Global holds reference to selected element
var selectedObj;
// Globals hold location of click relative to element
var offsetX, offsetY;

// Find out which element has been clicked on
function setSelectedElem(evt)
{
  if (isNav)
  {
    // declare local var for use in upcoming loop
    var testObj;
    // make copies of event coords for use in upcoming loop
    var clickX = evt.pageX;
    var clickY = evt.pageY;
    // loop through all layers (starting with frontmost layer)
    // to find if the event coordinates are in the layer

    for (var i = document.layers.length - 1; i >= 0; i--)
    {
      testObj = document.layers[i];
      if ((clickX > testObj.left) && (clickX < testObj.left + testObj.clip.width) && (clickY > testObj.top) && (clickY < testObj.top + testObj.clip.height))
      {
        // if so, then set the global to the layer, bring it
        // forward, and get outa here
        selectedObj = testObj;
        setZIndex(selectedObj, 100);
        return;
      }
    }
  }
  else
  {
    // use IE event model to get the targeted element
    var imgObj = window.event.srcElement;

    if (imgObj.parentElement.id.indexOf("capFleet") != 0)  // if not capital
	{
	  if (imgObj.parentElement.id.indexOf("fleet") != -1)  // if fleetX
      {
        // then set the global to the style property of the element,
        // bring it forward, and say adios
        selectedObj = imgObj.parentElement.style;
        setZIndex(selectedObj,100);
        return;
      }
    }

  }
  // the user probably clicked on the background
  selectedObj = null;
  return;
}

// Drag an element
/**************************************************************************
 DRAGIT
**************************************************************************/
function dragIt(evt)
{
  // operate only if a plane is selected
  if (selectedObj)
  {
    if (isNav)
    {
      shiftTo(selectedObj, (evt.pageX - offsetX), (evt.pageY - offsetY));
    }
    else
    {
      shiftTo(selectedObj, (window.event.clientX - offsetX), (window.event.clientY - offsetY));
      // prevent further system response to dragging in IE
      return false;
    }
	return true;
  }
  return false;
}

// Set globals to connect with selected element
/**************************************************************************
 ENGAGE
**************************************************************************/
function engage(evt)
{
  setSelectedElem(evt);
  if (selectedObj)
  {
    // set globals that remember where the click is in relation to the
    // top left corner of the element so we can keep the element-to-cursor
    // relationship constant throughout the drag
    if (isNav)
    {
      offsetX = evt.pageX - selectedObj.left;
      offsetY = evt.pageY - selectedObj.top;
    }
    else
    {
      offsetX = window.event.offsetX;
      offsetY = window.event.offsetY;
    }
  }
  // block mouseDown event from forcing Mac to display
  // contextual menu.
  return false;
}

// Restore elements and globals to initial values
/**************************************************************************
 RELEASE
**************************************************************************/
function release(evt)
{
  fleetName = window.event.srcElement.parentElement.id;

  // 오브젝트를 선택했을때
  if (selectedObj)
  {
    // 선택한 fleet 을 0 레이어로 set
    setZIndex(selectedObj, 0);
    // 커서에서 fleet 떼기
    selectedObj = null;

    xp = (document.layers) ? loc.pageX : window.event.clientX;
    yp = (document.layers) ? loc.pageY : window.event.clientY;

    // real x, y position calculate
    calxy();

    // fleet 이 최대 20개까지 배치될 수 있으므로
    for (var i=0; i<=20; i++)
    {
      fleet_flag = 0;

      // 현재 배열에 어떤 fleet 도 저장되어 있지 않으면
      if (over[i][0].substring(0, 5) != "fleet")
      {
        for (var j=0; j<=20; j++)
        {
          // 클릭한 fleetName 이 배열중에 저장되어 있지 않으면
          if (over[j][0] != fleetName)
          {
            fleet_flag++;
          }
          else
          {
            fleet_flag = 2;
          }

          // 클릭한 fleet 이 저장되어 있지 않으면
          if (fleet_flag == 1)
          {
            over[i][0] = fleetName;
            over[i][1] = fleetX;
            over[i][2] = fleetY;
          }
        }
      }
    }

///////////////////////////////////////
// for test
/*
for (var k=0; k<5; k++)
{
  alert(over[k][0]);
}
*/
///////////////////////////////////////

    // 브라우저 밖으로 나갔을때
    if (fleetName == "")
	{
	  // 이전에 기억해놓은 좌표로 이동
      eval(layerRef+'["'+fleetNameTmp+'"]'+styleSwitch+'.left = '+fleetXtmp);
      eval(layerRef+'["'+fleetNameTmp+'"]'+styleSwitch+'.top  = '+fleetYtmp);

	}
    else
	{
      // 이미지 찍는 좌표를 보정해서 실제 X, Y 좌표를 넘겨줌
	  eval('Fdescription.'+fleetName+'_X.value = fleetX+5');
      eval('Fdescription.'+fleetName+'_Y.value = fleetY+8');
	}
  }
}


// Turn on event capture for Navigator
function setNavEventCapture()
{
  if (isNav)
  {
    document.captureEvents(Event.MOUSEDOWN | Event.MOUSEMOVE | Event.MOUSEUP);
  }
}


/**************************************************************************
 INIT
**************************************************************************/
function init(sel)
{

  if (isNav)
  {
    layerStyleRef = "layer.";
    layerRef = "document.layers";
    styleSwitch = "";

    setNavEventCapture();
  }
  else
  {
    layerStyleRef = "layer.style.";
    layerRef = "document.all";
    styleSwitch = ".style";
  }

  document.onclick = FindXY;
  // assign functions to each of the events (works for both Navigator and IE)
  document.onmousedown = engage;
  document.onmousemove = dragIt;
  document.onmouseup = release;

  // order description 창이 필요한 페이지인지 아닌지 판별
  // true : 필요
  // false: 불필요
  flag = sel;

  // fleet 끼리 졉치는 경우 계산하기 위해 2차원 배열 선언
  for (var i=0; i<=20; i++)
  {
    over[i] = new Array(2);
  }

  // 초기화
  for (var i=0; i<=20; i++)
  {
    for (var j=0; j<=2; j++)
    {
      over[i][j] = "not any fleet";
    }
  }

/*
  if (fleetX == -1) 
	  fleetX = fleetXDefault;
  if (fleetY == -1) 
	  fleetY = fleetYDefault;
*/

}


/**********************************************************
 
**********************************************************/
function calxy()
{
  // 격자에 맞춰서 이미지 위치 변경
  if (fleetName == "capFleet")
  {
  // if capFleet, don't move.
  }
  else if (fleetName != "none")
  {
    if (xp>=600) fleetX = 609 - 5;
    else if (xp<=19) fleetX = 9 - 5;
    else
    {
      fleetX = ((xp-xp%20)+9)-5;
    }
    eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.left = '+fleetX);

    if (yp<=236) fleetY = 226 - 8;
    else if (yp>=417) fleetY = 426 - 8;
    else
    {
      fleetY = (((yp+3)-(yp+3)%20)+6)-8;
    }
    eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.top  = '+fleetY);

    if (fleetX != 304 || fleetY != 318)
    {
      fleetXtmp = fleetX;
      fleetYtmp = fleetY;
    }
    else // if you fleet drop over capFleet position
    {
      eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.left = '+fleetXtmp);
      eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.top  = '+fleetYtmp);
    }
  }
}

/**********************************************************
 (x, y) position
**********************************************************/
if (document.layers)
{
  document.captureEvents(Event.MOUSEMOVE);
}

document.onclick = FindXY;

function FindXY(loc)
{

  fleetName = window.event.srcElement.parentElement.id;

  // 예외상황을 처리하기위해 이전의 fleet 이름을 임시 저장
  fleetNameTmp = fleetName;

  if (fleetName != "order")
  {
    fleetNameTmp = fleetName;
  }

  // if NOT select fleet -> display fleet name
  if (fleetName == "" || fleetName == "none")
  {
    fleetName = "none";
    document.oncontextmenu = hidepopup;
  }
  else 
  {
    fleetName = window.event.srcElement.parentElement.id; // fleet name
    document.oncontextmenu = showpopup;
  }

//  document.Fdescription.name.value = fleetName; 

  // 커서 좌표
  xp = (document.layers) ? loc.pageX : window.event.clientX;
  yp = (document.layers) ? loc.pageY : window.event.clientY;


/*
if (xp>600 || yp<228 || yp>428)
{
  if (fleetName != "none")
  {
    alert("invalid fleet position!!");
    eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.left = '+fleetLeftDefault1);
    eval(layerRef+'["'+fleetName+'"]'+styleSwitch+'.top  = '+fleetTopDefault1);
  }
}
*/

//  calxy();

  // fleet 이름과 넘어가는 실제 좌표를 text 박스에 표시
 /* 
  document.Fdescription.name.value = fleetName;
  document.Fdescription.x.value = fleetX + 5;
  document.Fdescription.y.value = fleetY + 8;
  */
 
}

/**********************************************************
 right button popup
**********************************************************/
function showpopup()
{
  //Find out how close the mouse is to the corner of the window
  var rightedge  = document.body.clientWidth - event.clientX
  var bottomedge = document.body.clientHeight - event.clientY

  //if the horizontal distance isn't enough to accomodate the width of the context menu
  if (rightedge < order.offsetWidth)
  //move the horizontal position of the menu to the left by it's width
  {
    order.style.left = document.body.scrollLeft + event.clientX - order.offsetWidth
  }
  else
  //position the horizontal position of the menu where the mouse was clicked
  {
    order.style.left = document.body.scrollLeft + event.clientX;
  }

  //same concept with the vertical position
  if (bottomedge < order.offsetHeight)
  {
    order.style.top = document.body.scrollTop+event.clientY - order.offsetHeight;
  }
  else
  {
    order.style.top = document.body.scrollTop + event.clientY;
  }

  order.style.visibility = "visible";

  return false;
}

function hidepopup()
{
  order.style.visibility = "hidden";
}

function highlight()
{
  var ORDER;

  if (event.srcElement.className == "menuitems")
  {
    event.srcElement.style.backgroundColor = "highlight";
    event.srcElement.style.color = "white";

	if (event.srcElement.NAME.toUpperCase() == "NORMAL")
    {
      ORDER = "The fleet will move forward until there is an enemy in range, and will then engage the enemy. The flight path of a normal fleet is considered 1500 wide with the fleet in the center.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "FORMATION")
    {
      ORDER = "The fleets in this group will retain the same formation they are originally placed in. They entire formation will then move forward via the Normal command. They move at the speed of the slowest fleet in the formation.  There is a chance that they will break formation.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "PENETRATE")
    {
      ORDER = "The fleet assigned this will attempt to pass through enemy lines and turn and hit them from behind. They will penetrate until they are behind the entire enemy force, and then they will turn and attack from behind.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "FLANK")
    {
      ORDER = "When the player chooses flank, they must place their fleets carefully.  Fleets placed on the upper half of the screen will flank up, and fleets on the lower half will flank down.  When flanking, the fleets will move from their placed position to the edge they were ordered to (top or bottom) and then will move along this edge until they are behind the enemy forces.  They will then turn around and attempt to destroy the enemy from behind.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "RESERVE")
    {
      ORDER = "Reserve fleets will advance at 3/4 the speed of the rest of the armada. They will engage any enemies that they encounter and will replace any destroyed/retreated fleets that were under formation command.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "ASSAULT")
    {
      ORDER = "Fleets assigned assault will move forward thru enemy fleets until they have made it 50% behind enemy lines. They will then attempt to engage enemy fleets that are in front of them. Fleets assigned assault will not engage until they have penetrated a minimum of 50% or they are forced to engage.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "FREE")
    {
      ORDER = "Find nearest target and attack. They will also seek out any target with a high danger rating. The flight path of free fleets is considered to be 1500 wide.";
    }
    else if (event.srcElement.NAME.toUpperCase() == "STAND_GROUND")
    {
      ORDER = "This command is available only to the defender. This fleet will wait for attackers to come to them and then engage. There is a chance that fleets will break from this stance. If a fleet breaks this stance, they will advance as per the Normal command. When there are more than one enemies in range, they will attack the enemy fleet with the highest danger rating.";
    }

    if (flag == true)
    {
      update(ORDER);
    }
  }
}

function lowlight()
{
  if (event.srcElement.className == "menuitems")
  {
    event.srcElement.style.backgroundColor = "";
    event.srcElement.style.color = "#999999";

    if (flag == true)
    {
      update(""); 
    }
  }
}

function act()
{
    if (fleetNameTmp != 'order' && fleetNameTmp != 'none') {
      var myIndex = eval('document.Fdescription.img_'+fleetNameTmp+'.title.indexOf("Order ", 0) + 6');
      var myString = eval('document.Fdescription.img_'+fleetNameTmp+'.title.substring(0, myIndex)');
      switch(event.srcElement.NAME) {
        case 'NORMAL' : myString += 'Normal'; break;
        case 'FORMATION' : myString += 'Formation'; break;
        case 'PENETRATE' : myString += 'Penetrate'; break;
        case 'FLANK' : myString += 'Flank'; break;
        case 'RESERVE' : myString += 'Reserve'; break;
        case 'ASSAULT' : myString += 'Assault'; break;
        case 'FREE' : myString += 'Free'; break;
        case 'STAND_GROUND' : myString += 'Stand Ground'; break;
        default : myString = 'error';
        }
      if (myString != 'error') {
      	eval('document.Fdescription.'+fleetNameTmp+'_O.value = event.srcElement.NAME');
        eval('document.Fdescription.img_'+fleetNameTmp+'.title = myString');
        }
      }
}

