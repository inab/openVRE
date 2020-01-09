Virtual Research Environment 
=========

VRE is a web-based collaborative working environment providing its users with a set of ready-to-use services (datasets, analysis tools and visualizaters) for supporting a research lifecycle. It is written in PHP, HTML and Javascript.

### Flavors

Several research projects has adopted the VRE framework for its project-specific purposes:

- MuG : https://vre.multiscalegenomics.eu
- OpenEBench : https://openebench.bsc.es/vre

### Dependencies
- Web Server (e.g. Apache2)
- Mongo Client (mongodb-org-shell, mongodb-org-tools, php-mongo)
- PHP (>=7.3)
- composer : https://getcomposer.org/doc/00-intro.md

### Code structure

- composer.json: 3rd party software requirements
- [config](./config) : configuration files 
	- [settings](.config/globals.inc.php.sample) sample for the global application settings
	- [bootstrap](.config/bootstrap.php) bootstrap VRE application
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
