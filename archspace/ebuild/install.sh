#!/bin/sh
echo "Setting up Portage Overlay and Directories"
echo "PORTDIR_OVERLAY=/usr/local/portage" >> /etc/make.conf
mkdir -pv /usr/local/portage/net-www/archspace-cvs
echo "Copying over ebuild"
cp -Rvp * /usr/local/portage/net-www/archspace-cvs
echo "You're all good now! just use emerge like normal =)"
