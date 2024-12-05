echo "installing dependencies"
apt -y install ca-certificates pkg-config git gcc make automake autoconf libtool bzip2 zlib1g-dev libssl-dev libedit-dev ninja-build cmake udev libc6-dev python3 python3-pip libglib2.0-dev libatlas-base-dev sshfs
pip install -U pip
pip install meson

echo "installing libfuse"

git clone https://github.com/libfuse/libfuse.git
cd libfuse
git checkout fuse-3.10.0
mkdir build
cd build
meson ..
ninja
ninja install

echo "installing crypt4ghfs"

pip install --upgrade pip wheel
ln -s $(which pip) /usr/bin/pip
pip install git+https://github.com/inab/crypt4ghfs.git@v1.2.1

echo "user_allow_other" >> /usr/local/etc/fuse.conf

mkdir -p /tmp/ega_clean && chown application:application /tmp/ega_clean