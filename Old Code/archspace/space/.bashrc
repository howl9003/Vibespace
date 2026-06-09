#
# Sample .bashrc
#
# 3 Apr 1997, Choi Jun Ho <junker@jazz.snu.ac.kr>
#

# The profile that all logins get before using their own .profile.

trap ""  2 3

#	Login and -su shells get /etc/profile services.
#	-rsh is given its environment in its .profile.

# your file creation mask
umask 022

# some sh-control variables
set ignoreeof
set filec
set nohup

# don't generate core, because it is only nasty for many users
ulimit -c 0

# aliases
alias	l='ls -AsCF'
alias   ll='ls -laFg |less'
alias   ls='ls -F'
#alias   ls='gnuls -N -F --show-control-chars'
alias   dir='ls -laF | less'
alias   h='history'
alias	work='ps -ef | grep '
alias   du='du -k'

# environment variables
export PATH=/bin:/usr/local/bin:/usr/X11R6/bin:/usr/bin:${HOME}/bin:/sbin:/usr/sbin
export PS1='\h:\w\$ '
#export TERM=vt100
export DOTDIR=$HOME/News
export NNTPSERVER=news.snu.ac.kr
export MANPATH=/usr/share/man:/usr/X11R6/man:/usr/local/man
# favorite editor
export EDITOR=/usr/bin/vi
export VISUAL=/usr/bin/vi
# default locale
#export LANG=ko_KR.EUC

# other environments
# by CHOI Junho <junker@jazz.snu.ac.kr>
LESS="-EMr"; export LESS  
LESSCHARDEF="8bcccbcc18b95.33b95.b"; export LESSCHARDEF

# to avoid perl warning
PERL_BADLANG=C; export PERL_BADLANG

## 입력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#HANX_INPUT_DISABLE=0; export HANX_INPUT_DISABLE
# 출력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#HANX_OUTPUT_DISABLE=0; export HANX_OUTPUT_DISABLE
# 두벌식은 2, 세벌식은 3 
#HANX_INPUT_KEYBOARD=2; export HANX_INPUT_KEYBOARD
#HANX_FONT="-kaist-philgi-bold-r-normal--*-*-*-*-c-*-*-*"; export HANX_FONT_TRY
#HANX_FONT_TRY=1; export HANX_FONT_TRY

# ko-gtk-1.0.4h 
GTK_DEFAULT_FONTSET='-*-helvetica-medium-r-*-*-12-*-*-*-*-*-iso8859-1,-hanyang-kodig-medium-r-normal--12-120-72-72-c-120-ksc5601.1987-0'; export GTK_DEFAULT_FONTSET
GTK_KEYBOARD=2; export GTK_KEYBOARD    # 2-bul keyboard

# for proposed hangul keyboard scheme, for hanterm and ami
HANGUL_KEYBOARD_TYPE=2; export HANGUL_KEYBOARD_TYPE

# for Netscape-ko, FreeBSD
KO_NETSCAPE_USE_LD_PRELOAD=yes; export KO_NETSCAPE_USE_LD_PRELOAD

# x load image
#X_VIEWER="xv -geometry +1+1"; export X_VIEWER

# terminal setup
stty crt -istrip cs8 -parenb
stty erase ^H
stty kill 

# emacs-style editing mode
set -o emacs
