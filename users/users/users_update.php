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
//vaibhav
if(JFolder::exists(JPATH_BASE.DS.'components'.DS.'com_xipt'))
{
require_once( JPATH_SITE .DS.'components'.DS.'com_xipt'.DS.'api.xipt.php');
}

class UsersApiResourceUsers_update extends ApiResource
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
		//assign userid to update
		$userid = $data['userid'];
		//chk data
		if($data['email']=="" ) 
		{
			$validated  = false;
			$error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Email cannot be blank");  
		} elseif( false == $this->isValidEmail( $data['email'] ) ) {
			$validated  = false;
		  $error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Please set valid email id eg.(example@gmail.com). Check 'email' field in request");
	
		}
	        
		if( $data['pass_word']=="" ) 
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
		{	
			//to update user for joomla
			global $message;
			jimport('joomla.user.helper');
			$authorize 	= & JFactory::getACL();
			$user 		= clone(JFactory::getUser());
			
			$user->set('id', $data['userid']);
			$user->set('username', $data['username1']);
			$user->set('password', $data['pass_word'] );
			$user->set('name', $data['name']);
			$user->set('email', $data['email']);
 
			// password encryption
			$salt  = JUserHelper::genRandomPassword(32); 
			$crypt = JUserHelper::getCryptedPassword($user->password, $salt);
			$user->password = "$crypt:$salt";
			
			// true on success, false otherwise
			if(!$user->save()) 
			{
				$message="not created because of ".$user->getError();
				return false;
			}
			else
			{
				$message="username-".$user->username." is updated";
		
			}	
			
			if(isset($data['field']))
		    { 	
			$fields = $data['field'];
		    //to access model function
		    $profileModel =& CFactory::getModel('profile');
	    	//using update function
			foreach($fields as $id=>$value)
			{
				//to get fieldcode 
				$fieldcode = $profileModel->getFieldCode($id);
				$profileModel->updateUserData( $fieldcode , $userid , $value );
			
			}
			//using saveprofile function
			//$val = $profileModel->saveProfile($userid, $fields);
			//$userid = $user->id;
			}
			$result = array('user id '=>$userid,'message'=>$message);
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
		$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";

    	if ( eregi( $pattern, $email )) {
    	  return true;
      } else {
        return false;
      }   
	}
	
}
