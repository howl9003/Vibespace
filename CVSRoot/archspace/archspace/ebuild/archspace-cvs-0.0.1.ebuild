 
# Copyright 2004-2004 ArchCave Development Team
# Distributed under the terms of the GNU General Public License v2
# $Header: /var/cvsroot/archspace/ebuild/archspace-cvs-0.0.1.ebuild,v 1.3 2004/09/20 08:18:39 brian Exp $

inherit cvs

ECVS_USER="anonymous"
ECVS_SERVER="68.11.248.40:/var/cvsroot"
ECVS_MODULE="archspace"

DESCRIPTION="Archspace CVS code"
SRC_URI=""
HOMEPAGE="http://as.kenisware.org/"
LICENSE="GPL-2"

SLOT="0"
KEYWORDS="x86 ~amd64 ~ppc"
IUSE=""
S=${WORKDIR}/${ECVS_MODULE}
DEPEND="<net-www/apache-2.0
	dev-db/mysql
	dev-libs/pth"

src_install() {
	sh $S/install.sh
}

pkg_postinst() {
	echo
	einfo "At least one configuration file in /etc/archspace needs updating."
	einfo "Please make sure your /etc/apache/conf/apache.conf has a line"
	einfo "that includes 'conf/addon-mondules/*.conf' so that the AS module gets loaded"
	einfo "Use '/etc/init.d/ArchspacePortal' start to start the service"
	einfo "Config is in /etc/archspace"
	einfo "Logs are in /var/log/archspace"
	einfo "Please update /etc/archspace/archspace.config"
	echo
}
