

/*
	 Milonic DHTML Website Navigation Menu
	 Written by Andy Woolley - Copyright 2003 (c) Milonic Solutions Limited. All Rights Reserved
	 Please visit http://www.milonic.co.uk/ for more information

	 Although this software may have been freely downloaded, you must obtain a license before using it in any production environment
	 The free use of this menu is only available to Non-Profit, Educational & Personal Web Sites who have obtained a license to use 
	 
	 Free, Commercial and Corporate Licenses are available from our website at http://www.milonic.co.uk/menu/supportcontracts.php
	 You also need to include a link back to http://www.milonic.co.uk/ if you use the free license
	 
	 All Copyright notices MUST remain in place at ALL times
	 If you cannot comply with all of the above requirements, please contact us to arrange a license waiver
*/



//The following line is critical for menu operation, and MUST APPEAR ONLY ONCE.
menunum=0;menus=new Array();_d=document;function addmenu(){menunum++;menus[menunum]=menu;}function dumpmenus(){mt="<scr"+"ipt language=JavaScript>";for(a=1;a<menus.length;a++){mt+=" menu"+a+"=menus["+a+"];"}mt+="<\/scr"+"ipt>";_d.write(mt)}
//Please leave the above line intact. The above also needs to be enabled if it not already enabled unless you have more than one _array.js file


////////////////////////////////////
// Editable properties START here //
////////////////////////////////////

timegap=500                   // The time delay for menus to remain visible
followspeed=5                 // Follow Scrolling speed
followrate=50                 // Follow Scrolling Rate
suboffset_top=5               // Sub menu offset Top position
suboffset_left=10             // Sub menu offset Left position



PlainStyle=[                  // PlainStyle is an array of properties. You can have as many property arrays as you need
"FFFFFF",                     // Mouse Off Font Color
"000099",                     // Mouse Off Background Color (use zero for transparent in Netscape 6)
"000099",                     // Mouse On Font Color
"CCCCCC",                     // Mouse On Background Color
"666666",                     // Menu Border Color
"12",                         // Font Size (default is px but you can specify mm, pt or a percentage)
"normal",                     // Font Style (italic or normal)
"bold",                       // Font Weight (bold or normal)
"Verdana, Tahoma, Arial, Helvetica",// Font Name
4,                            // Menu Item Padding or spacing
"arrow.gif",                  // Sub Menu Image (Leave this blank if not needed)
1,                            // 3D Border & Separator bar
"FFFF00",                     // 3D High Color
"CCFFFF",                     // 3D Low Color
"purple",                     // Current Page Item Font Color (leave this blank to disable)
"pink",                       // Current Page Item Background Color (leave this blank to disable)
,                             // Top Bar image (Leave this blank to disable)
"CCCCCC",                     // Menu Header Font Color (Leave blank if headers are not needed)
"000099",                     // Menu Header Background Color (Leave blank if headers are not needed)
,                             // Menu Item Separator Color
]


addmenu(menu=[
"DragMenu",                   // Menu Name - This is needed in order for this menu to be called
160,                          // Menu Top - The Top position of this menu in pixels
10,                           // Menu Left - The Left position of this menu in pixels
150,                          // Menu Width - Menus width in pixels
1,                            // Menu Border Width
,                             // Screen Position - here you can use "center;left;right;middle;top;bottom" or a combination of "center:middle"
PlainStyle,                   // Properties Array - this array is declared higher up as you can see above
1,                            // Always Visible - allows this menu item to be visible at all time (1=on or 0=off)
,                             // Alignment - sets this menu elements text alignment, values valid here are: left, right or center
"Fade(duration=0.2);Shadow(color=777777, Direction=135, Strength=5)",// Filter - Text variable for setting transitional effects on menu activation - see above for more info
0,                            // Follow Scrolling Top Position - Tells this menu to follow the user down the screen on scroll placing the menu at the value specified.
0,                            // Horizontal Menu - Tells this menu to display horizontaly instead of top to bottom style (1=on or 0=off)
0,                            // Keep Alive - Keeps the menu visible until the user moves over another menu or clicks elsewhere on the page (1=on or 0=off)
,                             // Position of TOP sub image left:center:right
,                             // Set the Overall Width of Horizontal Menu to specified width or 100% and height to a specified amount
0,                            // Right To Left - Used in Hebrew for example. (1=on or 0=off)
0,                            // Open the Menus OnClick - leave blank for OnMouseover (1=on or 0=off)
,                             // ID of the div you want to hide on MouseOver (useful for hiding form elements)
,                             // Background image for menu Color must be set to transparent for this to work
0,                            // Scrollable Menu
,                             // Miscellaneous Menu Properties
,"You Can Drag Me!","# type=header;dragable=1;",,,0
,"Sub Menu","show-menu=SubMenu","#",,0
,"Sample 1","# ",,,0
,"Sample 2","# ",,,0
])


addmenu(menu=[
"SubMenu",
,
,
100,
1,
,
PlainStyle,
0,
,
"Fade(duration=0.2);Shadow(color=777777, Direction=135, Strength=5)",
0,
0,
0,
,
,
0,
0,
,
,
0,
,
,"Sample 1","# ",,,0
,"Sample 2","# ",,,0
])




dumpmenus();
	