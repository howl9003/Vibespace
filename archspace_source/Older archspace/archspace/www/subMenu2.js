/***********************************
 subMenu2.js
 by TheDAZ
***********************************/
var stayFolded=false;

var exImg=new Image();
exImg.src='/image/as_game/minus.gif';

var unImg=new Image();
unImg.src='/image/as_game/plus.gif';

var n = (document.layers) ? 1:0;
var ie = (document.all) ? 1:0;
var browser = ((n || ie) && parseInt(navigator.appVersion) >= 4)

function makeMenu(obj,nest)
{
  nest = (!nest) ? '':'document.'+nest+'.';
  this.css = (n) ? eval(nest+'document.'+obj):eval('document.all.'+obj+'.style');
  this.ref = (n) ? eval(nest+'document.'+obj+'.document'):eval('document');
  this.height = n?this.ref.height:eval(obj+'.offsetHeight');
  this.x = (n) ? this.css.left:this.css.pixelLeft;
  this.y = (n) ? this.css.top:this.css.pixelTop;
  this.hideIt = b_hideIt;
  this.showIt = b_showIt;
  this.vis = b_vis;
  this.moveIt = b_moveIt;
  return this;
}

function b_showIt()
{
  this.css.visibility = "visible";
}

function b_hideIt()
{
  this.css.visibility = "hidden";
}

function b_vis()
{
  if(this.css.visibility == "hidden" || this.css.visibility == "hide")
  return true;
}

function b_moveIt(x,y)
{
  this.x = x;
  this.y = y;
  this.css.left = this.x;
  this.css.top = this.y;
}


function init()
{
  oTop = new Array();

  oTop[0] = new makeMenu('divTop1','divCont');
  oTop[1] = new makeMenu('divTop2','divCont');
  oTop[2] = new makeMenu('divTop3','divCont');
  oTop[3] = new makeMenu('divTop4','divCont');
  oTop[4] = new makeMenu('divTop5','divCont');
  oTop[5] = new makeMenu('divTop6','divCont');
  oTop[6] = new makeMenu('divTop7','divCont');
  oTop[7] = new makeMenu('divTop8','divCont');
  oTop[8] = new makeMenu('divTop9','divCont');
  oTop[9] = new makeMenu('divTop10','divCont');
  oTop[10] = new makeMenu('divTop11','divCont');
  oTop[11] = new makeMenu('divTop12','divCont');
  oTop[12] = new makeMenu('divTop13','divCont');

  oSub = new Array();

  oSub[0] = new makeMenu('divSub1','divCont.document.divTop1');
  oSub[1] = new makeMenu('divSub2','divCont.document.divTop2');
  oSub[2] = new makeMenu('divSub3','divCont.document.divTop3');
  oSub[3] = new makeMenu('divSub4','divCont.document.divTop4');
  oSub[4] = new makeMenu('divSub5','divCont.document.divTop5');
  oSub[5] = new makeMenu('divSub6','divCont.document.divTop6');
  oSub[6] = new makeMenu('divSub7','divCont.document.divTop7');
  oSub[7] = new makeMenu('divSub8','divCont.document.divTop8');
  oSub[8] = new makeMenu('divSub9','divCont.document.divTop9');
  oSub[9] = new makeMenu('divSub10','divCont.document.divTop10');
  oSub[10] = new makeMenu('divSub11','divCont.document.divTop11');
  oSub[11] = new makeMenu('divSub12','divCont.document.divTop12');
  oSub[12] = new makeMenu('divSub13','divCont.document.divTop13');

  for(i=0; i<oSub.length; i++)
  {
    oSub[i].hideIt();
  }

  for(i=1; i<oTop.length; i++)
  {
    oTop[i].moveIt(0, oTop[i-1].y+oTop[i-1].height);
  }
}

function menu(num)
{
  if(browser)
  {
    if(!stayFolded)
    {
      for(i=0; i<oSub.length; i++)
      {
        if(i!=num)
        {
/*
          oSub[i].hideIt();
          oTop[i].ref["imgA"+i].src = unImg.src;
*/
        }
      }
      for(i=1; i<oTop.length; i++)
      {
        oTop[i].moveIt(0, oTop[i-1].y+oTop[i-1].height);
      }
    }
    if(oSub[num].vis())
    {
      oSub[num].showIt();
      oTop[num].ref["imgA"+num].src = exImg.src;
    }
    else
    {
      oSub[num].hideIt();
      oTop[num].ref["imgA"+num].src = unImg.src;
    }
    for(i=1; i<oTop.length; i++)
    {
      if(!oSub[i-1].vis())
      {
        oTop[i].moveIt(0, oTop[i-1].y+oTop[i-1].height+oSub[i-1].height);
      }
      else
      {
        oTop[i].moveIt(0, oTop[i-1].y+oTop[i-1].height);
      }
    }
  }
}

if(browser)
{
  onload = init;
}
