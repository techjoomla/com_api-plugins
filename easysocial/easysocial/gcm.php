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

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceGcm extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->toggleNotify());
	}
	public function post()
	{
		$this->plugin->setResponse($this->send_notif());
	}
	public function delete()
	{
		$this->plugin->setResponse($this->delete_notif());
	}
	public function toggleNotify()
	{
		$result = new stdClass();
		$state = false;
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$dev_id = $app->input->get('device_id','','RAW');
		$nval = $app->input->get('notify_val',1,'INT');
		
		//DB Create steps
		$db = FD::db();

		if( $dev_id == "null" || $dev_id == null )
		{
			$result->message = JText::_( 'PLG_API_EASYSOCIAL_NO_DEVICE_ID' );
			$result->success = $state;
		}
		else
		{
			$state = $this->tnotify($log_user, $dev_id, $nval);
			$result->success= $state;
			$result->message = ( $state && $nval )?JText::_( 'PLG_API_EASYSOCIAL_NOTIFICATION_ON' ):JText::_( 'PLG_API_EASYSOCIAL_NOTIFICATION_OFF' );
		}			
		return $result;
	}
	
	public function tnotify($log_user, $dev_id , $val)
	{
		//DB Create steps
		$db = FD::db();
		
		$query1 = "SELECT id  FROM #__acpush_users WHERE device_id LIKE '%".$dev_id."%'";
		$db->setQuery($query1);
		$db->query();
		$id = $db->loadResult();
		$query_a = "UPDATE #__acpush_users SET active = ".$val." WHERE id = ".$id;
		$db->setQuery($query_a);
		return $val = $db->query();
	}
	
	//do notification setting
	public function send_notif()
	{
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$user=FD::user($log_user);
		$reg_id = $app->input->get('device_id','','STRING');

		$type = $app->input->get('type','','STRING');
		$res = new stdClass;
			
		//not allow empty device id for registration
		if( $reg_id == "null" || $reg_id == null )
		{
			$res->message = JText::_( 'PLG_API_EASYSOCIAL_NO_DEVICE_ID' );
			$res->status=false;
			return $res;
		}
		
		//DB Create steps
		$db = FD::db();
		//Create a new query object.
		$query = $db->getQuery(true);
		$inserquery = $db->getQuery(true);
		//Get date.
		$now = new DateTime();
		$currentdate=$now->format('Y-m-d H:i:s'); 
				
		//Getting database values to check current user is login again or he change his device then only adding device to database
		$checkval = $db->getQuery(true);
		$checkval->select($db->quoteName('id'));
		$checkval->from($db->quoteName('#__acpush_users'));
		$checkval->where("device_id LIKE '%".$reg_id."%' AND type = "."'".$type."'");

		$db->setQuery($checkval);

		$ids_dev = $db->loadResult();
		if($ids_dev)
		{
			$res->message = JText::_( 'PLG_API_EASYSOCIAL_DEVICE_ALREADY_REGISTERED_MESSAGE' );
			$res->status=false;
			return $res;
		}
		else
		{
		
		//Insert columns now.
		$columns = array('device_id','user_id','created_on','type','active');
		//Insert values.
		$values = array($db->quote($reg_id),$db->quote($user->id),$db->quote($currentdate),$db->quote($type),1);
		//Prepare the insert query.
		$inserquery->insert($db->quoteName('#__acpush_users'))->columns($db->quoteName($columns))->values(implode(',', $values));

		$db->setQuery( $inserquery );
		$result = $db->query();
		$res->message = JText::_( 'PLG_API_EASYSOCIAL_DEVICE_REGISTER_MESSAGE' );
		$res->status=$result;
		return $res;
		}
	}
	public function delete_notif()
	{
		$app = JFactory::getApplication();
		$reg_id = $app->input->get('device_id','','STRING');
			
		//not allow empty device id for registration
		if( $reg_id == "null" || $reg_id == null )
		{
			return false;
		}
		
		//DB steps
		$db = FD::db();
		
		//Getting database values to check current user is login again or he change his device then only adding device to database
		$query = $db->getQuery(true);
		
		// delete all custom keys for user 1001.
		$conditions = " ".$db->quoteName('device_id') ." LIKE '%".$reg_id."%' "; 
		$query->delete($db->quoteName('#__acpush_users'));
		$query->where($conditions); 
		$db->setQuery($query);
		$result = $db->execute();
		return $result;
	}
}
