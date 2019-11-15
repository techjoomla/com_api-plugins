# IMPORTANT NOTE:

## Soon, the plugins from this repo will be moved to either a seperate repo for each plugin 
## or 
## to a repsective plugin-parent's project repo as listed below

### API plugins for Techjoomla Extensions

_JTicketing (com_jticketing)_

- https://github.com/techjoomla/com_api-plugins

_SocialAds (com_socialads)_

- https://github.com/techjoomla/com_api-plugins

### API plugins for Techjoomla Infra Extensions

_TJ Activity Stream_

- https://github.com/techjoomla/com_activitystream/tree/master/plugins/api/tjactivity

_TJ Dashboard_

- https://github.com/techjoomla/tj-dashboard/tree/master/src/plugins/api/tjdashboard

_com_ewallet [unreleased]_

- https://github.com/techjoomla/com_ewallet/tree/master/src/plugins/api

_com_importer [unreleased]_

https://github.com/techjoomla/tj-importer/tree/master/plugins/api

_TJReports_

- https://github.com/techjoomla/com_tjreports/tree/release-1.1.5/tjreports/plugins/api/reports [use this]

- https://github.com/techjoomla/plg_api_tjreports [deprecated]

_TJUCM_

- https://github.com/techjoomla/com_tjucm/tree/master/src/components/com_tjucm/plugins/api

### API plugins for Joomla Core Extensions

_com_content_

- https://github.com/techjoomla/com_api-plugins

_com_categories_

- https://github.com/techjoomla/com_api-plugins

_com_users_

- https://github.com/techjoomla/plg_api_users [use this]

- https://github.com/techjoomla/com_api-plugins [deprecated]

### API plugins for 3rd Party Extensions

_com_easysocial_

- https://github.com/techjoomla/plg_api_easysocial [use this]

- https://github.com/techjoomla/com_api-plugins [deprecated]

_com_easyblog_

- https://github.com/techjoomla/plg_api_easyblog [use this]

- https://github.com/techjoomla/com_api-plugins [deprecated]

_com_aec_

- https://github.com/techjoomla/com_api-plugins

_com_akeebasubs_

- https://github.com/techjoomla/com_api-plugins

_com_redshop_

- https://github.com/techjoomla/com_api-plugins

# Impotant NOTE Ends

# RESTful API plugins

Plugins to be used with com_api component. To add additional resources to the API, plugins need to be created. Each plugin can provide multiple API resources. Plugins are a convenient way to group several resources. Eg: A single plugin could be created for Quick2Cart with separate resources for products, cart, checkout, orders etc.

## API URLs
The URL to access any route is of the format - 
```http
/index.php?option=com_api&format=raw&app=<plugin name>&resource=<resource name>&key=<key>
```
## Authentication
Currently API token based authentication is supported. The token needs to be passed as a HTTP POST or get variable with the name ```key```. This will be changed at some point to be transmitted via a header.

## CRUD Operations
Each resorce can support the GET, POST, DELETE & PUT (needs some work) operations. These are exposed by creating methods of the same name, i.e. get() post() put() and delete() in each of the resources. This way, if a resouce URL is accessed via HTTP POST , the post() method is called, and similarly for the rest.

## Typical API plugin file structure
* language/en-GB - Resource folder having resource file, keep name same as plugin name.
	- en-GB.plg_api_users.ini - add plugin language constant.
	- en-GB.plg_api_users.sys.ini
* users - Resource folder having resource file, keep name same as plugin name.
	- login.php - Resource file
* users.php - plugin file
* users.xml - xml file 

You can add multiple resource in resource folder and use them for different purpose.

## Create plugin entry file users.php file
This is the entry file for the API plugin, the things that re deifned in the file are resource locations, and making certain resources public. Below is the code for the file - 

```php
jimport('joomla.plugin.plugin');
//class structure example
class plgAPIUsers extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());
		
		// Set resource path
		ApiResource::addIncludePath(dirname(__FILE__).'/users');
		
		// Load language files
		$lang = JFactory::getLanguage(); 
		$lang->load('plg_api_your_plugin_name', JPATH_ADMINISTRATOR,'',true);
		
		// Set the login resource to be public
		$this->setResourceAccess('your_plugin_name', 'public', 'post');

	}
}
```

## Create resource file login.php file
Although you can place the resource files anywhere, the recommended approach is to place them within a folder inside your plugin.  Below is example code for a resource file. Notice how the methods get() and post() are implemented. The methods will tpically return an array or an object which will be automatically converted to JSON, so the resource does not need to convert to JSON.

```php

<?php
//class structure example
//ex - class UsersApiResourceLogin extends ApiResource
class UsersApiResourceLogin extends ApiResource
{
	public function get()
	{
		// Add your code here
		 
		$this->plugin->setResponse( $result );
	}

	public function post()
	{
		// Add your code here
		
		$this->plugin->setResponse( $result );
	}
}
```

### Make some resources public
 
It is possible to make certain resource method public by using the setResourceAccess() access method as
```php
$this->setResourceAccess('users', 'public', 'post') 
```

The first parameter is the resource name, second is status (should be public to make it public and last is method,
which is you want to make public. Setting a resource public will mean that the URL will not need any authentication.
  

## Create .xml file
Finally create a manifest XML so that your plugin can be installed. Set group as 'api', add plugin name and other details.

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension version="3.0.0" type="plugin" group="api" method="upgrade">
    <name>YourPlugin</name>
    <version>1.6</version>
    <creationDate>10/11/2014</creationDate>
    <author></author> 
    <description></description>
    <files>
        <filename plugin="your_plugin_name">your_plugin_name.php</filename>
        <folder>your_plugin_name</folder> 
    </files>
    <languages folder="language">
		<language tag="en-GB">en-GB/en-GB.plg_api_plugin_name.ini</language>
		<language tag="en-GB">en-GB/en-GB.plg_api_plugin_name.sys.ini</language>
	</languages>
	
</extension> 
```
