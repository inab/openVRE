# Dockerized OpenVRE

Deployment of a full openVRE-based analysis platform, including:

- openVRE module (frontend and backend): https://github.com/inab/openVRE/tree/dockerized
- Keycloak Authentication server
- Cluster SGE 

## Architecture

![architecture openvre (1)](https://user-images.githubusercontent.com/57795749/201643520-3e7b6cdf-b6c4-4985-9385-9a7b738174eb.png)
=======
# new_dockerized_vre



## Getting started

Cloning this directory in your system: 



```
git clone https://gitlab.bsc.es/disc4all/openvre/new_vre.git

cd new_vre

mv new_vre dockerized_vre

cd dockerized_vre 

docker compose build

docker compose up -d 

```

## Fixes 


Manually entering the SGEcore container and modify the docker group permission:

```

docker exec -ti sgecore /bin/bash

in the container -> groupmod -g 120 docker

in the container -> usermod -aG docker application

```
