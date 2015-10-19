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
jimport('joomla.application.component.model');
jimport( 'joomla.application.component.model' );
jimport( 'joomla.database.table.user' );

use Joomla\Registry\Registry;

require_once JPATH_SITE .'/components/com_content/models/article.php';
//require_once JPATH_SITE .'/components/com_content/models/categories.php';
require_once JPATH_ADMINISTRATOR .'/components/com_categories/models/categories.php';
require_once JPATH_SITE .'/libraries/legacy/model/list.php';
require_once JPATH_SITE .'/components/com_content/helpers/query.php';

class ArticlesApiResourceCategory extends ApiResource
{
	public function get()
	{
		//init variable
		$app = JFactory::getApplication();
		//get data
		$catid		= $app->input->get('id', 0, 'INT');
		$limitstart	= $app->input->get('limitstart', 0, 'INT');
		$limit	= $app->input->get('limit', 10, 'INT');
		
		$cat_obj = new CategoriesModelCategories();
		$jlist = new JModelList();
		
		$config = JFactory::getConfig();
		$old_limit = $config->get('list_limit');
        $config->set('list_limit', 0);
		
		//$jlist->setState('list.start', 0);
		//$jlist->setState('list.limit', 10);
//print_r($config);die("in api");
		$rows = $cat_obj->getItems();

		$items = array();
		
		//format data
		$obj = new BlogappSimpleSchema();

		foreach( $rows as $row )
		{
			$items[] = $obj->mapCategory($row);
		}
		
		$config->set('list_limit', $old_limit);
		
		$items = array_slice($items,$limitstart,$limit);
		
		$this->plugin->setResponse($items);
	}
	
	public function post()
	{  
		$this->plugin->setResponse("use post method");
		//~ 
		//~ $error_messages         = array();
		//~ $fieldname		= array();
		//~ $response               = NULL;
		//~ $validated              = true;
		//~ $userid = NULL;
		//~ $data	= JRequest::get('post');
		//~ //for rest api only
		//~ unset($data['format']);
		//~ unset($data['resource']);
		//~ unset($data['app']);
		//~ unset($data['key']);
		//~ //assign userid to update
		//~ $userid = $data['id'];
		//~ //chk data
		//~ if($data['email']=="" ) 
		//~ {
			//~ $validated  = false;
			//~ $error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Email cannot be blank");  
		//~ } elseif( false == $this->isValidEmail( $data['email'] ) ) {
			//~ $validated  = false;
		  //~ $error_messages[] = array("id"=>1,"fieldname"=>"email","message"=>"Please set valid email id eg.(example@gmail.com). Check 'email' field in request");
	//~ 
		//~ }
	        //~ 
		//~ if( $data['password']=="" ) 
		//~ {
			//~ $validated  = false;
			//~ $error_messages[] = array("id"=>1,"fieldname"=>"password","message"=>"Password cannot be blank");
		//~ }
	        //~ 
		//~ if( $data['name']=="" or $data['username']=="" ) 
		//~ {
			//~ $validated  = false;
			//~ $error_messages[] = array("id"=>1,"fieldname"=>"name","message"=>"Name cannot be blank");
		//~ }	        
	//~ 
		//~ if( true == $validated)
		//~ {	
			//~ //to update user for joomla
			//~ global $message;
			//~ jimport('joomla.user.helper');
			//~ $authorize 	= & JFactory::getACL();
			//~ $user 		= clone(JFactory::getUser());
			//~ 
			//~ $user->set('id', $data['id']);
			//~ $user->set('username', $data['username']);
			//~ $user->set('password', $data['password'] );
			//~ $user->set('name', $data['name']);
			//~ $user->set('email', $data['email']);
 //~ 
			//~ // password encryption
			//~ $salt  = JUserHelper::genRandomPassword(32); 
			//~ $crypt = JUserHelper::getCryptedPassword($user->password, $salt);
			//~ $user->password = "$crypt:$salt";
			//~ 
			//~ // true on success, false otherwise
			//~ if(!$user->save()) 
			//~ {
				//~ $message="not created because of ".$user->getError();
				//~ return false;
			//~ }
			//~ else
			//~ {
				//~ $message="username-".$user->username." is updated";
		//~ 
			//~ }	
			//~ 
			//~ if(isset($data['field']))
		    //~ { 	
			//~ $fields = $data['field'];
		    //~ //to access model function
		    //~ $profileModel =& CFactory::getModel('profile');
	    	//~ //using update function
			//~ foreach($fields as $id=>$value)
			//~ {
				//~ //to get fieldcode 
				//~ $fieldcode = $profileModel->getFieldCode($id);
				//~ $profileModel->updateUserData( $fieldcode , $userid , $value );
			//~ 
			//~ }
			//~ 
			//~ }
			//~ $result = array('user id '=>$userid,'message'=>$message);
			//~ $result =($userid) ? $result : $message; 
		//~ 
			//~ $this->plugin->setResponse($result);
		//~ 
		//~ }
		//~ else
		//~ {			
			//~ $this->plugin->setResponse($error_messages);//print_r($error_messages);	die("validate mail2222"); 
		//~ }
		   
	}
	
}
