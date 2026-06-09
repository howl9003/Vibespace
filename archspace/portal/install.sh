#!/bin/sh

echo "Installing Archspace Portal..."
echo "Creating Directories..."
mkdir /etc/archspace
mkdir /var/log/archspace
mkdir /usr/include/archspace
echo "Copying files..."
cp ./etc/config /etc/archspace
cp ./src/bsdport.h /usr/include/archspace
cp ./bin/ArchspacePortal /etc/init.d
cp ./bin/initialize_portal /etc/archspace
cd ./src
echo "Setting Platform..."
./set_platform linux
cd lib
echo "Compiling Archspace Portal..."
make clean && make
cd ../elib
make clean && make
cd ../server
make clean && make install
echo "Setting up mysql Database..."
cd ../../bin
./initialize_portal