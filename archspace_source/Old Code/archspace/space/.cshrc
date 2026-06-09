##
## Sample csh/tcsh .cshrc file
##
## It also can be used in tcsh.
##
## 2 Apr 1997 
## 8 Jul 1999 CHOI Junho <cjh@kr.freebsd.org>
##

## It can be used in mixed with Linux

# in case of non-interactive shell, most of things can be skipped
if ($?prompt) then
##
## some aliases
##

# show history
alias  h		'history 25'

# list jobs
alias  j		'jobs -l'

# ls
alias  la		'ls -a'
alias  lf		'ls -FA'
alias  ll		'ls -laFg |more'
alias  l		'ls -al'
alias  li		'ls -aCF'

alias cp		'cp -i'

# NNTP-only tin
alias  tin		'tin -r'
# read newgroups only in ~/.newsrc, much faster
#alias  tin		'tin -r -n -q'
alias	gnus		'emacs -f gnus'

# easy popd
alias  popd		'popd \!*; cd .'

# delete backup files
alias  delbaks    	'rm #* *~ *.bak'

# launch hanemacs in favorite font
alias  hanemacs  	 'hanemacs -fn -schumacher-clean-bold-r-normal--16-160-75-75-c-80-iso8859-1 -hfn -kaist-iyagi-bold-r-normal--16-160-75-75-c-160-johabsh-1'

# in original hanterm, KSC5601-1987.0 fonts must be used with -ks
# not needed in autofont or XF8632 version
#alias   hanterm	   'hanterm -ks'

# to read hangul messages in HLaTeX-0.95 
#alias  latex      	'latex \!* | ~/bin/thmf'

# don't display unnecessary chars in a2ps variant
#alias  a2ps	   	'a2ps -nv'
#alias  nh2ps		'nh2ps -nv -J -KHGraphic \!* | nh2ps_opt'

# etc
#alias	more		less
alias	vi		vim

##
## enviroment variables
##

# your usenet news folder 
setenv DOTDIR 		$HOME/News

# Archspace CVSROOT
setenv CVSROOT ':pserver:cvscommiter@cvsroot.archmage.co.kr:/bbs/cvs'

# your news server
setenv NNTPSERVER 	news.dacom.co.kr
#setenv NNTPSERVER 	news.kornet.nm.kr

# your terminal type(not preferred)
#setenv TERM 		vt100

# to see hangul in less
#setenv	LESS 		"-ficesr"
setenv	LESS		"-EMr"
setenv	LESSCHARDEF 	"8bcccbcc18b95.33b95.b"

# your favorite editor
setenv VISUAL  		/usr/bin/vi
#setenv VISUAL  	/usr/local/bin/vim
#setenv VISUAL  	/usr/local/bin/helvis
#setenv	EDITOR		/usr/bin/ee
setenv	EDITOR		/usr/bin/vi
#setenv	EDITOR		/usr/local/bin/vim
#setenv	EDITOR		/usr/local/bin/helvis


# default block size
setenv	BLOCKSIZE	K

# when OpenWindoes(XView) is installed
setenv OPENWINHOME 	/usr/X11R6

# your locale(must be fully qualified name)
#setenv LANG  ko_KR.EUC

# to avoid perl warning
setenv PERL_BADLANG C

# to get mozilla source
alias mozcvs 'setenv CVSROOT :pserver:anonymous@cvs-mirror.mozilla.org:/cvsroot'

# to get QT source
alias qtcvs 'setenv CVSROOT :pserver:cjhkrfre@cvs.troll.no:/cvs'

# to get glib/gtk source
alias gtkcvs 'setenv CVSROOT :pserver:anonymous@anoncvs.gnome.org:/cvs/gnome'

# to get FreeBSD source
alias anoncvs 'setenv CVSROOT anoncvs@anoncvs.freebsd.org:/cvs'

# to get hanterm source
alias hantermcvs 'setenv CVSROOT :pserver:cvs@elf.kaist.ac.kr:/usr/cvsroot'

# to get FreeBSD source, locally
alias freebsdcvs 'setenv CVSROOT /home/ncvs'

# hanX
#
## 입력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#setenv HANX_INPUT_DISABLE 0
# 출력을 불가능으로 설정하기 위해서는 1 을 설정합니다.
#setenv HANX_OUTPUT_DISABLE 0
# 두벌식은 2, 세벌식은 3
setenv HANX_INPUT_KEYBOARD 2
# 폰트 설정에 대한 자세한 내용은 /usr/local/share/doc/HanX-3.3 디렉토리를
# 참고하시기 바랍니다.
setenv HANX_FONT "-kaist-philgi-bold-r-normal--*-*-*-*-c-*-*-*"
setenv HANX_FONT_TRY 1

# for HWPX/R4
#setenv HNCDIR /usr/local/hwpx

# for gtk-1.0.4h
setenv GTK_DEFAULT_FONTSET '-adobe-helvetica-bold-r-*-*-12-*-*-*-*-*-iso8859-1,-ksg-gtr-medium-r-normal--12-110-75-75-c-120-ksc5601.1987-0'
setenv GTK_KEYBOARD	   2 	# 두벌식

# for qt-1.40 hangul patch
setenv QT_KEYBOARD	2
#setenv QT_HANFONT '-ksg-gtr-medium-r-normal--12-110-75-75-c-120-ksc5601.1987-0'
setenv QT_HANFONT '-hanyang-kodig-medium-r-normal--12-120-72-72-c-120-ksc5601.1987-0'

# for proposed hangul keyboard scheme
setenv HANGUL_KEYBOARD_TYPE 2

# KDE
setenv KDEDIR	/usr/local

# temp
setenv OPENWINHOME /usr/X11R6

# for Netscape-ko, FreeBSD version 2 not using libansi
#setenv KO_NETSCAPE_USE_LD_PRELOAD yes

# bc default scale
#setenv BC_ENV_ARGS $HOME/.bcrc

# x load image
#setenv X_VIEWER="xv -geometry +1+1"

set filec
set nohup
set history = 100
set savehist = 100
set mail = (/var/mail/$USER)
set path=(/bin /usr/local/bin /usr/X11R6/bin /usr/bin ~/bin /sbin /usr/sbin ~/local/bin /u/local/bin /usr/local/hwpx/bin /usr/local/sbin /usr/jp/bin /usr/ml/bin /u/bsd/bin /space/mysql/bin /usr/local/jdk1.1.8/bin)
set ignoreeof

# your file creation mask
umask 022
# prevent making nasty core dumps
unlimit core

# to pass 8-bit char
stty -istrip -parenb cs8

  # use GNU ls for Korean file name
  #alias  ls		'gnuls -N -F --show-control-chars'
  # ncftp 2.x is named as ncftp2 in FreeBSD
  alias  ncftp      	'ncftp2'

  # pager
  setenv PAGER more

  # launch kterm, japanese terminal
  alias  kterm	   	'env LANG=ja_JP.EUC kterm'

##
## sh-dependent setup
##
if ($?tcsh) then
  # tcsh-only stuff
 
  # set prompt
  set prompt="%B%m%b:%~%# "

  # enables auto-complete file listing
  set autolist

  # enables auto-expand
  set autoexpand

  # enables auto spell correction
  set autocorrect

  # kind of correction. cmd means command correction
  set correct=cmd

  # use ... instead of /<skipped> in %c prompt 
  set ellipsis

  # don't logout
  set autologout=0

  # classify symbolic links.
  # > : link to directory
  # @ : link to file
  # & : non-exist destination
  set listlinks

  # setup complete file
  source ~/.complete 

  # M-j (M == meta key == ESC) rotates completion choices forward.
  bindkey '^[j' complete-word-fwd
  bindkey '^[k' complete-word-back

  # auto-magical history expansion by space key.
  # eg)   % man tcsh
  #       % vi .tcshrc
  #       % !m <space>
  #    -> % man tcsh
  bindkey ' ' magic-space

  # specify to not rebound to self-insert-command
  setenv  NOREBIND

    # C-w delete 'word'
    bindkey '' backward-delete-word
    # ^? is binded to backward-delete-char by default, so fix it
    bindkey '^?' backward-delete-word

else
  # traditional csh
  # an old-csh style prompt
  alias   cd     'cd \!*;set prompt="`hostname`:$cwd% "'
  cd
endif

else

# non-interactive shell
#
# set path only if not interactive shell.
# we don't need set it everytime we login, because .login sets correct path.

set path = (/sbin /bin /usr/sbin /usr/bin /usr/games /usr/local/bin /usr/X11R6/bin $HOME/bin /u/local/bin)

endif

set cdpath = (~/ ~/src ~/src/apps ~/src/libs)
