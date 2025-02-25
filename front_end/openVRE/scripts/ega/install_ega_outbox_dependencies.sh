#!/bin/bash

#Remove the previous alternatives

echo "installing dependencies"
apt update
apt -y install curl jq ca-certificates pkg-config git gcc-10 make automake autoconf libtool bzip2 zlib1g-dev libssl-dev libedit-dev ninja-build cmake udev libc6-dev python3 python3-pip libglib2.0-dev libatlas-base-dev sshfs

#gcc-10 --version
#sudo update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-10 10

gcc --version

pip install -U pip
pip install meson

echo "installing libfuse"

git clone https://github.com/libfuse/libfuse.git
cd libfuse
git checkout fuse-3.16.2
mkdir build
cd build
meson ..
ninja
ninja install

echo "installing crypt4ghfs"

pip install --upgrade pip wheel
ln -s $(which pip) /usr/bin/pip
pip install git+https://github.com/inab/crypt4ghfs.git@v1.2.1

#echo "user_allow_other" >> /usr/local/etc/fuse.conf
echo "user_allow_other" >> /etc/fuse.conf

mkdir -p /encrypted_files && chown application:application /encrypted_files
mkdir -p /clean_files && chown application:application /clean_files
