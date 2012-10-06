<?php
/**
 * @package	API
 * @version 1.5
 * @author 	Brian Edgerton
 * @link 	http://www.edgewebworks.com
 * @copyright Copyright (C) 2011 Edge Web Works, LLC. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
jimport('joomla.user.helper');
jimport( 'joomla.application.component.helper' );
require_once( JPATH_SITE .DS.'components'.DS.'com_community'.DS.'libraries'.DS.'core.php');
require_once( JPATH_SITE .DS.'libraries'.DS.'joomla'.DS.'filesystem'.DS.'folder.php');
require_once( JPATH_ADMINISTRATOR .DS.'components'.DS.'com_users'.DS.'models'.DS.'user.php');
//vaibhav
if(JFolder::exists(JPATH_BASE.DS.'components'.DS.'com_xipt'))
{
require_once( JPATH_SITE .DS.'components'.DS.'com_xipt'.DS.'api.xipt.php');
}

class UsersApiResourceUsers_delete extends ApiResource
{
	
	public function post()
	{  
		//print_r("in post");die;
		
		$error_messages         = array();
		$fieldname		= array();
		$response               = NULL;
		$validated              = true;
		$userid = NULL;
		$data	= JRequest::get('post');
		//for rest api only
		unset($data['format']);
		unset($data['resource']);
		unset($data['app']);
		unset($data['password']);
		unset($data['username']);
		//
		$userid = $data['userid'];
		
		$db = JFactory::getDBO();
				
		$query = "DELETE u, um, cu, cf
				  FROM #__users AS u
				  LEFT JOIN #__user_usergroup_map AS um ON um.user_id = u.id
				  LEFT JOIN #__community_users AS cu ON cu.userid = u.id
				  LEFT JOIN #__community_fields_values AS cf ON cf.user_id = u.id
				  WHERE u.id =".$userid."";
		           
		$db->setQuery($query);                     
		//$data1 = $db->loadResult();
		 if (!$db->query())
		  {
                 $this->setError( $db->getErrorMsg() );
                 return false;
          }
          else
          {
			   $this->plugin->setResponse( "User of id = ".$userid." deleted" ); 
		  }              
				
			    
	}
		 
		
}
