open Virtual-Research-Environment 
=========

VRE is a computational cloud-based working environment providing reseachers a web-based integrated access to a customizable set of pluggble resources: analysis tools, reference repositories, and visualizaters. 


### Flavors

Several research projects are adopting openVRE framework for a rapidly prototying their computational platforms:

| Project     | Title | Access |
| ----------- | ------------- | -------|
| MuG         | Multiscale Complex Genomics  | https://vre.multiscalegenomics.eu |
| OpenEBench  | ELIXIR Benchmarking Platform | https://openebench.bsc.es/vre |
| euCanSHare  | Computational environment for cardiovascular research | https://vre.eucanshare.bsc.es/ |
| IPC         | Individualized Paediatric Cancer Analysis Platform | https://vre.ipc-project.bsc.es/ |   


 
### Dependencies
- Web Server (e.g. Apache2)
- Mongo Client (mongodb-org-shell, mongodb-org-tools, php-mongo)
- PHP (>=7.3)
- Composer : https://getcomposer.org/doc/00-intro.md

### Code structure

- composer.json: 3rd party software requirements
- [config](./config) : configuration files 
	- [settings](./config/globals.inc.php.sample) sample for the global application settings
	- [bootstrap](./config/bootstrap.php) bootstrap VRE application
- [install](./install) : installation instructions and data 
	- [install](./install/README.md) instructions
	- [database](./install/database) skeleton with structural collections
	- [data](./install/data) datasets and sample schemas
- [public](./public) : web application elements
	- **web pages**
	- [home](./public/home) pages for 'Homepage' section
	- [workspace](./public/workspace) pages for 'User Workspace' section
	- [getdata](./public/getdata) pages for 'Get Data' section
	- [launch](./public/launch) pages for 'Run Tool / Visualizers' section
	- [help](./public/help) pages for 'Help' section
	- [helpdesk](./public/helpdesk) pages for 'Helpdesk' section
	- [admin](./public/admin) pages for 'Admin' section
	- [user](./public/user) pages for 'User Profile' section
	- [cookies](./public/cookies) notification
	- **libraries**
	- [applib](./public/applib) : pages' backend
	- [assets](./public/assets) : pages' client scripts
	- [phplib](./public/phplib) : VRE libraries and classes
	- [htmlib](./public/htmlib) : html templates
	- **content**
	- [tools](./public/tools) : web form, assets and summart page for integrated tools
	- [visualizers](./public/visualizers) : visualizers code
- [scripts](./scripts) : maintainance scripts
