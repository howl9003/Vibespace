#	$Id: dot.profile,v 1.7.2.5 1998/06/30 14:38:05 kuriyama Exp $
#
# .profile - Bourne Shell startup script for login shells
#
# see also sh(1), environ(7).
#

# remove /usr/games or /usr/X11R6/bin if you want
PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/games:/usr/local/bin:/usr/X11R6/bin:$HOME/bin; export PATH

# Setting TERM is normally done through /etc/ttys.  Do only override
# if you're sure that you'll never log in via telnet or xterm or a
# serial line.
# Use cons25l1 for iso-* fonts
# TERM=cons25; 	export TERM

BLOCKSIZE=K;	export BLOCKSIZE
EDITOR=vi;   	export EDITOR
PAGER=more;  	export PAGER
# make mail(1) happy:
crt=24;		export crt

# other environments
# by CHOI Junho <junker@jazz.snu.ac.kr>
LESS="-ficesr";	export LESS
LESSCHARDEF="8bcccbcc18b95.33b95.b"; export LESSCHARDEF

# to avoid perl warning
PERL_BADLANG=0;	export PERL_BADLANG

## 입력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#HANX_INPUT_DISABLE=0; export HANX_INPUT_DISABLE
# 출력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#HANX_OUTPUT_DISABLE=0; export HANX_OUTPUT_DISABLE
# 두벌식은 2, 세벌식은 3
#HANX_INPUT_KEYBOARD=2; export HANX_INPUT_KEYBOARD
#HANX_FONT="-kaist-philgi-bold-r-normal--*-*-*-*-c-*-*-*"; export HANX_FONT_TRY
#HANX_FONT_TRY=1; export HANX_FONT_TRY

# ko-gtk-1.0.4h
GTK_DEFAULT_FONTSET='-adobe-helvetica-medium-r-*-*-12-*-*-*-*-*-iso8859-1,-hanyang-kodig-medium-r-normal--12-120-72-72-c-120-ksc5601.1987-0'; export GTK_DEFAULT_FONTSET
GTK_KEYBOARD=2; export GTK_KEYBOARD    # 2-bul keyboard

# set ENV to a file invoked each time sh is started for interactive use.
ENV=$HOME/.shrc; export ENV

# emacs-style editing mode
set -o emacs
