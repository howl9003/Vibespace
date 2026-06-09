# Chora .mo files makefile
#
# $Horde: chora/po/Makefile,v 1.2.2.4 2002/04/06 16:44:44 jan Exp $
#

APPLICATION = chora
HORDE = ../..
MSGFMT = msgfmt --statistics -c -v -o
MSGFMTSOL = msgfmt -v -o
MSGCOMM = msgcomm --more-than=0 -s -o -

all: install

install: *.po
	@echo "Checking for os ... ${OSTYPE}"; \
	if test "${OSTYPE}" = "solaris"; then \
		echo "You'll probably get some warnings on Solaris. This is normal."; \
	fi; \
	for LOCALE in `ls *.po | sed 's/\..*//g'`; do \
		if test "$${LOCALE}.po" = "messages.po"; then \
			continue; \
		fi; \
		echo "Compiling locale $${LOCALE}:"; \
		if ./shtool mkdir -p ../locale/$${LOCALE}/LC_MESSAGES; then \
			if test -r ${HORDE}/po/$${LOCALE}.po; then \
				if test "${OSTYPE}" = "solaris"; then \
					if ${MSGFMTSOL} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po ${HORDE}/po/$${LOCALE}.po; then \
						echo "  ... done"; \
						echo; \
					else \
						echo "  ... FAILED"; \
						echo; \
					fi \
				else \
					if ${MSGCOMM} $${LOCALE}.po ${HORDE}/po/$${LOCALE}.po | ${MSGFMT} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo -; then \
						echo "  ... done"; \
						echo; \
					else \
						echo "  ... FAILED"; \
						echo; \
					fi \
				fi; \
			else \
				echo "Warning: Horde po file for $${LOCALE} does not exist."; \
				if ${MSGFMT} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po; then \
					echo "  ... done"; \
					echo; \
				else \
					echo "  ... FAILED"; \
					echo; \
				fi \
			fi \
		else \
			echo "Could not create locale directory for $${LOCALE}."; \
		fi \
	done;
