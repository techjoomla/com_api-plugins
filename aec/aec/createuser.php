<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.user.helper');
jimport( 'joomla.application.component.helper' );
jimport( 'joomla.application.component.model' );
jimport( 'joomla.database.table.user' );

require_once( JPATH_SITE .DS.'libraries'.DS.'joomla'.DS.'filesystem'.DS.'folder.php');

/*if(JFolder::exists(JPATH_BASE.DS.'components'.DS.'com_xipt'))
{
require_once( JPATH_SITE .DS.'components'.DS.'com_xipt'.DS.'api.xipt.php');
}*/

class AecApiResourceCreateuser extends ApiResource
{
	
	public function post()
	{  
				
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
		unset($data['key']);
		//
		//$userid = $data['userid'];
		//$fields = $data['field'];
//print_r($_POST);	die;
		//chk data
		if($data['email']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("success"=>0,"id"=>16,"fieldname"=>"email","message"=>"Email cannot be blank");  
		} elseif( false == $this->isValidEmail( $data['email'] ) ) {
			$validated  = false;
		  $error_messages[] = array("success"=>0,"id"=>16,"fieldname"=>"email","message"=>"Please set valid email id eg.(example@gmail.com). Check 'email' field in request");
	
		}
		if( $data['password']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("success"=>0,"id"=>15,"fieldname"=>"password","message"=>"Password cannot be blank");
		}
	        
		if( $data['name']=="" or $data['username']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("success"=>0,"id"=>14,"fieldname"=>"name/username","message"=>"Name cannot be blank");
		}	        
	
		if( true == $validated)
		{	//to create new user for joomla
		
			global $message;
			jimport('joomla.user.helper');
			$authorize 	= & JFactory::getACL();
			$user 		= clone(JFactory::getUser());
			$user->set('username', $data['username']);
			$user->set('password', $data['password'] );
			$user->set('name', $data['name']);
			$user->set('email', $data['email']);
 
			// password encryption
			$salt  = JUserHelper::genRandomPassword(32); 
			$crypt = JUserHelper::getCryptedPassword($user->password, $salt);
			$user->password = "$crypt:$salt";

			// user group/type
			$user->set('id', '');
			$user->set('usertype', 'Registered');
			if(JVERSION >= '1.6.0')
			{
				$userConfig = JComponentHelper::getParams('com_users');
				// Default to Registered.
				$defaultUserGroup = $userConfig->get('new_usertype', 2);
				$user->set('groups', array($defaultUserGroup));
			}
			else
			$user->set('gid', $authorize->get_group_id( '', 'Registered', 'ARO' ));

			$date =& JFactory::getDate();
			$user->set('registerDate', $date->toMySQL());
		
			// true on success, false otherwise
			if(!$user->save()) 
			{
				$message="not created because of ".$user->getError();
				return false;
			}
			else
			{
				$message="created of username-".$user->username." and send mail of details please check";
		
			}	
			//$this->plugin->setResponse($user->id);
			$userid = $user->id;
			
			//result message
			$result = array('success'=>1,'user id '=>$userid,'username'=>$user->username,'message'=>$message);
			$result =($userid) ? $result : $message; 
		
			$this->plugin->setResponse($result);
			
		
		}
		else
		{
			
			$this->plugin->setResponse($error_messages);//print_r($error_messages);	die("validate mail2222"); 
		}
		
		
			    
	}
		 
	function isValidEmail( $email )
	{
		$pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";

    	if ( preg_match( $pattern, $email ) )
    	 {
			return true;
		  } else {
        return false;
      }   
	}
	
}
