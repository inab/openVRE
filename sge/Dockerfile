# Use an official Debian image as the base image
FROM debian:bullseye-20241111-slim

# Set non-interactive mode for apt-get
ENV DEBIAN_FRONTEND noninteractive

# Install necessary packages: gnupg, vim, parallel, g++
RUN apt-get update -y  \
    && apt-get install -y \
        gnupg2 \
        vim \
        parallel \
        g++ \
        wget \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Add Docker's official GPG key:
RUN apt-get update
RUN apt-get install -y ca-certificates curl
RUN install -m 0755 -d /etc/apt/keyrings
RUN curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
RUN chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
RUN echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian \
    $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
    tee /etc/apt/sources.list.d/docker.list > /dev/null
RUN apt-get update
RUN apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

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
    && echo "application:application" | chpasswd

# Make sure host and container share the same GID for group 'docker', bc it has reading permissions to the socket file
ARG DOCKER_GROUP
RUN groupmod -g $DOCKER_GROUP docker
RUN usermod -aG docker application

# Add setup script and set permissions
ADD setup_gridengine.sh /usr/local/bin/setup_gridengine.sh
ADD setup_submitter.sh  /usr/local/bin/setup_submitter.sh
ADD modify_config.sh  /usr/local/bin/modify_config.sh
ADD modify_sched_config.sh  /usr/local/bin/modify_sched_config.sh
ADD run_sched_logger.sh  /usr/local/bin/run_sched_logger.sh
RUN chmod +x /usr/local/bin/setup_gridengine.sh
RUN chmod +x /usr/local/bin/setup_submitter.sh
RUN chmod +x /usr/local/bin/modify_config.sh
RUN chmod +x /usr/local/bin/modify_sched_config.sh
RUN chmod +x /usr/local/bin/run_sched_logger.sh

ARG SUBMITTER_HOSTNAME
RUN echo "$SUBMITTER_HOSTNAME" > /home/application/submit_hosts.list
RUN cat /home/application/submit_hosts.list


CMD /usr/local/bin/setup_gridengine.sh && /usr/local/bin/modify_config.sh && /usr/local/bin/modify_sched_config.sh && /usr/local/bin/setup_submitter.sh /home/application/submit_hosts.list && (/usr/local/bin/run_sched_logger.sh &) && tail -f /var/spool/gridengine/qmaster/messages /var/spool/gridengine/execd/sgecore/messages
