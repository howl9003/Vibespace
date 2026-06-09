ns4 = (document.layers)? true:false
ie4 = (document.all)? true:false
if (ie4) {
        if (navigator.userAgent.indexOf('MSIE 5')>0) {
                ie5 = true;
        } else {
                ie5 = false; }
} else { ie5 = false; }

var width = 200;
var border = "0";
var offsetx = 3;
var offsety = 3;
// var fcolor = "#FFCCCC";
var fcolor = "#ffffff";
// var backcolor = "#CC0000";
var backcolor = "pink";
var textcolor = "#332000";
var capcolor = "#FFFFFF";
var closecolor = "#99FF99";

var x = 0;
var y = 0;
var snow = 0;
var tmpX;
var newWin = null;
var doc_width=800, doc_height=600

if ( (ns4) || (ie4) ) {
        if (ns4) over = document.overDiv;
        if (ie4) over = overDiv.style;
        document.onmousemove = mouseMove;
        if (ns4) document.captureEvents(Event.MOUSEMOVE);
}

function nd() {
        if ( (ns4) || (ie4) ) {
                snow = 0;
                hideObject(over);
        }
}
function drc(name, type, level, prerequisite, project, spy, shipComponent, CM) {
        if(ns4) doc_width=self.innerWidth;
        else if(ie4) doc_width=document.body.clientWidth;
//        txt = "<TABLE WIDTH="+width+" STYLE=\"border:1 black solid\" CELLPADDING="+border+" CELLSPACING=0><TR><TD BGCOLOR=\""+backcolor+"\"><TABLE WIDTH=100% BORDER=0 CELLPADDING=0 CELLSPACING=0><TR><TD align=right><SPAN ID=\"PTT\">&nbsp;<FONT COLOR=\""+capcolor+"\"><B>"+title+"</B></FONT></SPAN></TD></TR></TABLE><TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=0 BGCOLOR=\""+fcolor+"\"><TR><TD align=left><SPAN ID=\"PST\"><FONT COLOR=\""+textcolor+"\">"+text+"</FONT><SPAN></TD></TR></TABLE></TD></TR></TABLE>"
        txt = "<TABLE BORDER=0 WIDTH=310 CELLSPACING=0 CELLPADDING=0 BGCOLOR=#9d9d9d><TR><TD COLSPAN=2>&nbsp;&nbsp;"+name+"</TD></TR><TR><TD COLSPAN=2>&nbsp;</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Type</TD><TD>"+type+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Level</TD><TD>"+level+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Prerequisite</TD><TD>"+prerequisite+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Project</TD><TD>"+project+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Spy</TD><TD>"+spy+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Ship Component</TD><TD>"+shipComponent+"</TD></TR><TR><TD WIDTH=160>&nbsp;&nbsp;Control Model Effect</TD><TD>"+CM+"</TD></TR></TABLE>";

        layerWrite(txt);
        disp();
}
function disp() {
        if ( (ns4) || (ie4) ) {
                if (snow == 0)  {
                        moveTo(over,tmpX,y+offsety);
                        showObject(over);
                        snow = 1;
                }
        }
}
function mouseMove(e) {
        if (ns4) {x=e.pageX; y=e.pageY}
        if (ie4) {x=event.x; y=event.y}
        if (ie5) {x=event.x+document.body.scrollLeft; y=event.y+document.body.scrollTop;}
        tmpX=(doc_width-x-offsetx-width < 0)? (doc_width-width):(x+offsetx)
        if (snow) {
                moveTo(over,tmpX,y+offsety);
        }
}
function layerWrite(txt) {
        if (ns4) {
                var lyr = document.overDiv.document
                lyr.write(txt)
                lyr.close()
        }
        else if (ie4) document.all["overDiv"].innerHTML = txt
}
function showObject(obj) {
        if (ns4) obj.visibility = "show"
        else if (ie4) obj.visibility = "visible"
}
function hideObject(obj) {
        if (ns4) obj.visibility = "hide"
        else if (ie4) obj.visibility = "hidden"
}
function moveTo(obj,xL,yL) {
        obj.left = xL;
        obj.top = yL;
}

