<?php
/**
 * @package	API
 * @version 1.5
 * @author 	Brian Edgerton
 * @link 	http://www.edgewebworks.com
 * @copyright Copyright (C) 2011 Edge Web Works, LLC. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.user.helper');
jimport( 'joomla.application.component.helper' );
jimport( 'joomla.application.component.model' );
jimport( 'joomla.database.table.user' );
require_once( JPATH_SITE .DS.'components'.DS.'com_community'.DS.'libraries'.DS.'core.php');
require_once( JPATH_SITE .DS.'libraries'.DS.'joomla'.DS.'filesystem'.DS.'folder.php');
//vishal
if(JFolder::exists(JPATH_BASE.DS.'components'.DS.'com_xipt'))
{
require_once( JPATH_SITE .DS.'components'.DS.'com_xipt'.DS.'api.xipt.php');
}

class UsersApiResourceUsers extends ApiResource
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
		unset($data['password']);
		unset($data['username']);
		//
		//$userid = $data['userid'];
		$fields = $data['field'];
		
		//chk data
		if($data['email']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Email cannot be blank");  
		} elseif( false == $this->isValidEmail( $data['email'] ) ) {
			$validated  = false;
		  $error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Please set valid email id eg.(example@gmail.com). Check 'email' field in request");
	
		}else
		{
			//check mail is registerd
			$isemail =& CFactory::getModel('register'); 
			$found = $isemail->isEmailExists(array('email'=>$data['email'] ));
			if($found)
			{ 
					$validated  = false;
					$error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"email already exist and user is registered");
			 }
		}
	        
		if( $data['password1']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("id"=>1,"fieldname"=>"password","message"=>"Password cannot be blank");
		}
	        
		if( $data['name']=="" or $data['username1']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("id"=>1,"fieldname"=>"name","message"=>"Name cannot be blank");
		}	        
	
		if( true == $validated)
		{	//to create new user for joomla
		
			global $message;
			jimport('joomla.user.helper');
			$authorize 	= & JFactory::getACL();
			$user 		= clone(JFactory::getUser());
			$user->set('username', $data['username1']);
			$user->set('password', $data['password1'] );
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
			//create profile
			$profileModel =& CFactory::getModel('profile');
			$val = $profileModel->saveProfile($userid, $fields);
			//result message
			$result = array('user id '=>$userid,'message'=>$message);
			$result =($userid) ? $result : $message; 
		
			$this->plugin->setResponse($result);
			
		
		}
		else
		{
			
			$this->plugin->setResponse($error_messages);//print_r($error_messages);	die("validate mail2222"); 
		}
		
		
			    
	}
		 
	public function put()
	{ 
		$data	= JRequest::get('post');
		
		//for rest api only
		unset($data['format']);
		unset($data['resource']);
		unset($data['app']);
		unset($data['password']);
		unset($data['username']);
		//
		$userid = $data['userid'];
		$fields = $data['field'];
		
		print_r($data);die;
		
		
		$this->plugin->setResponse( "in the put method" ); 
	}
	
	 public function delete()
	{    	
   	   $this->plugin->setResponse( "in the delete method" ); 
	}
	
	function isValidEmail( $email )
	{
		$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";

    	if ( eregi( $pattern, $email ) )
    	 {
			return true;
		  } else {
        return false;
      }   
	}
	
}
