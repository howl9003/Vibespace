#
# Sample csh .login file
#
# 3 Apr 1997 Choi Jun Ho <junker@jazz.snu.ac.kr>
#
# it is invoked only when login
#

# default path
#set path = (/sbin /bin /usr/sbin /usr/local/mysql/bin /usr/bin /usr/games /u/teTeX/bin /usr/local/bin /usr/X11R6/bin $HOME/bin)
setenv MANPATH "/usr/share/man:/usr/X11R6/man:/usr/local/man"

# Interviews settings
#setenv CPU "FREEBSD"
#set path = ($path /usr/local/interviews/bin/$CPU)
#setenv MANPATH "${MANPATH}:/usr/local/interviews/man"

# 8-bit locale (Korea)
#setenv LANG ko_KR.EUC

# A righteous umask
umask 22

# enable RTS/CTS flow control and set delete character
stty crt erase 

# pass 8-bit character
stty -istrip -parenb cs8

# run fetchmail to get mails
#if ($OSTYPE == "FreeBSD") then
#  fetchmail -d 180 >& /dev/null
#endif

# invoke 'fortune'
[ -x /usr/games/fortune ] && /usr/games/fortune
