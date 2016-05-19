# RESTful API plugins

Plugins to be used with com_api component

RESTful api is simple joomla plugin, which is same as
any other joomla plugin, the difference is only type of plugin.
Type must be 'api'

## API plugin file structure
Following well known HTTP methods are commonly used in REST based architecture.

GET - Provides a read only access to a resource.

PUT - Used to create a new resource.

DELETE - Used to remove a resource.

POST - Used to update a existing resource or create a new resource.

## API plugin file structure
* language/en-GB - Resource folder having resource file, keep name same as plugin name.
	- en-GB.plg_api_users.ini - add plugin language constant.
	- en-GB.plg_api_users.sys.ini
* users - Resource folder having resource file, keep name same as plugin name.
	- login.php - Resource file
* users.php - plugin file
* users.xml - xml file 

You can add multiple resource in resource folder and use them for different purpose.

## Create .xml file
Set api group as 'api', add plagin name and other details.

```xml

<?xml version="1.0" encoding="utf-8"?>
<extension version="3.0.0" type="plugin" group="api" method="upgrade">
    <name>PLG_API_USERS</name>
    <version>1.6</version>
    <creationDate>10/11/2014</creationDate>
    <author></author> 
    <description>PLG_API_USERS_DESCRIPTION</description>
    <files>
        <filename plugin="your_plugin_name">your_plugin_name.php</filename>
        <folder>your_plugin_name(resource folder)</folder> 
    </files>
    <languages folder="language">
		<language tag="en-GB">en-GB/en-GB.plg_api_plugin_name.ini</language>
		<language tag="en-GB">en-GB/en-GB.plg_api_plugin_name.sys.ini</language>
	</languages>
	
</extension> 
```

## Create plugin entry file users.php file
This file is entry file for api plugin, Change plugin class name 'plgAPIUsers' as per your 
plugin name 'plgAPIYour_plugin_name'.

### Make resource function public
 
You can make resource method public by using setResourceAccess access as
$this->setResourceAccess('users', 'public', 'post') 
Here first param in your plugin name, second is status and last is method,
which is you want to make public. Means, it does not need authentication when 
called. 
  
```php

jimport('joomla.plugin.plugin');
//class structure example
//ex - class plgAPIUsers extends ApiPlugin
class plgAPIYour_plugin_name extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());
		ApiResource::addIncludePath(dirname(__FILE__).'/users');
		
		/*load language file for plugin frontend*/ 
		$lang = JFactory::getLanguage(); 
		$lang->load('plg_api_your_plugin_name', JPATH_ADMINISTRATOR,'',true);
		
		// Set the login resource to be public
		$this->setResourceAccess('your_plugin_name', 'public', 'post');

	}
}
```

## Create resource file login.php file
All resource file are placed in plugin resource folder.

```php

<?php
//class structure example
//ex - class UsersApiResourceLogin extends ApiResource
class Plugin_nameApiResourceResource_file_name extends ApiResource
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
