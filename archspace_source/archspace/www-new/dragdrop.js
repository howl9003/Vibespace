DragLayer=0;
DragX=0;
DragY=0
function drag_drop(name)
{
	DragLayer=name
	gm=gmobj(name);
	if(ns4)gm.captureEvents(Event.MOUSEDOWN | Event.MOUSEUP);
	gm.onmousedown=go;
	gm.onmouseup=stop;
}

function go()
{
	gm=gmobj(DragLayer)
	gp=gpos(gm);
	DragY=MouseY-gp[0];
	DragX=MouseX-gp[1];
	inDragMode=1;
}

function stop()
{
	inDragMode=0;
}
