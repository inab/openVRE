# Use an official Debian image as the base image
FROM debian:bullseye-slim

# Set non-interactive mode for apt-get
ENV DEBIAN_FRONTEND noninteractive

# Install necessary packages: gnupg, vim, parallel, g++
RUN apt-get update -y  \
    && apt-get install -y \
        gnupg2 \
        vim \
        parallel \
        g++ \
        docker.io \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy the local keys into the Docker image
COPY keys /keys

# Import GPG keys
RUN gpg --import /keys/AA8E81B4331F7F50.asc \
    && gpg --import /keys/DCC9EFBF77E11517.asc \
    && gpg --import /keys/112695A0E562B32A.asc \
    && gpg --import /keys/04EE7237B7D453EC.asc \
    && gpg --import /keys/EF0F382A1A7B6500.asc \
    && gpg --import /keys/648ACFD622F3D138.asc \
    && gpg --import /keys/DCC9EFBF77E11517.asc \
    && gpg --import /keys/54404762BBB6E853.asc \
    && gpg --import /keys/0E98404D386FA1D9.asc

# Update the package lists and install GridEngine packages
RUN apt-get update -y && apt-get install -y gridengine-exec gridengine-master gridengine-qmon

# Create a new user 'application' and set password
RUN useradd -m application \
    && echo "application:application" | chpasswd \
    && usermod -aG docker root

# Add setup script and set permissions
ADD setup_gridengine.sh /usr/local/bin/setup_gridengine.sh
RUN chmod +x /usr/local/bin/setup_gridengine.sh

# Add entry to /etc/hosts
#RUN echo "172.21.0.14 front_end.dockerized_vre_open-vre" >> /etc/hosts
#RUN echo "172.21.0.14 front_end" >> /etc/hosts

#USER application

CMD /usr/local/bin/setup_gridengine.sh && qconf -as front_end.dockerized_vre_local_net && qconf -mconf  && tail -f /var/spool/gridengine/qmaster/messages

