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
		$dev_id = $app->input->get('device_id','','STRING');
		$nval = $app->input->get('notify_val',1,'INT');
		
		
		//DB Create steps
		$db = FD::db();
		
		$query = "SHOW COLUMNS FROM #__social_gcm_users LIKE 'type'";
		$db->setQuery($query);
		$db->query();
		$rows = $db->loadObjectList();
		
		if(count($rows))
		{
			$state = $this->tnotify($log_user, $dev_id, $nval);
		}
		else
		{
			$query_a = "ALTER TABLE #__social_gcm_users ADD type text";
			$db->setQuery($query_a);
			$val = $db->query();
			
			if($val)
			{
				$state = $this->tnotify($log_user, $dev_id, $nval);
			}
			else
			{
				$result->success = 0;
				$result->message = 'old package, please update api package';
				return $result;
			}
		}
		$result->success= $state;
		$result->message = ( $state && $nval )?'Notification on':'Notification off';
		
		return $result;
	}
	
	public function tnotify($log_user, $dev_id , $val)
	{
		//DB Create steps
		$db = FD::db();
		
		$query1 = "SELECT id  FROM #__social_gcm_users WHERE device_id LIKE '%".$dev_id."%'";
		$db->setQuery($query1);
		$db->query();
		$id = $db->loadResult();
		
		$query_a = "UPDATE #__social_gcm_users SET send_notify = '".$val."' WHERE id = ".$id;

		$db->setQuery($query_a);
		return $val = $db->query();
	}
	
	//do notification setting
	public function send_notif()
	{
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$user=FD::user($log_user);
		$reg_id = $app->input->get('reg_id','','STRING');
		$sender_id = $app->input->get('sender_id','','STRING');
		$server_key = $app->input->get('server_key','','STRING');
		$bundle_id = $app->input->get('bundle_id','','STRING');
		$type = $app->input->get('type','','STRING');
		$res = new stdClass;
		
		//DB Create steps
		$db = FD::db();
		//Create a new query object.
		$query = $db->getQuery(true);
		$inserquery = $db->getQuery(true);
		//Get date.
		$now = new DateTime();
		$currentdate=$now->format('Y-m-d H:i:s'); 
		
		$query = "CREATE TABLE IF NOT EXISTS `#__social_gcm_users` (
		`id` int(10) NOT NULL AUTO_INCREMENT,
		`device_id` text NOT NULL,
		`bundle_id` text NOT NULL,
		`sender_id` text NOT NULL,
		`server_key` text NOT NULL,
		`type` text NOT NULL,
		`user_id` int(20) NOT NULL, 
		`send_notify` int(20) DEFAULT 1, 
		`created_date` datetime, 
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";		
		$db->setQuery($query);
		$db->query();
		
		//Getting database values to check current user is login again or he change his device then only adding device to database
		$checkval = $db->getQuery(true);
		$checkval->select($db->quoteName(array('device_id', 'sender_id', 'server_key', 'user_id','bundle_id','type')));
		$checkval->from($db->quoteName('#__social_gcm_users'));
		$db->setQuery($checkval);
		$results = $db->loadObjectList();
		foreach($results as $notfn)
		{
			if($notfn->user_id==$log_user && $notfn->device_id==$reg_id)
			{
				$res->message = "Your device is already register to server.";
				$res->status=false;
				return $res;
			}
		}
		//Insert columns now.
		$columns = array('device_id','sender_id','server_key','user_id','bundle_id','created_date','type');
		//Insert values.
		$values = array($db->quote($reg_id),$db->quote($sender_id),$db->quote($server_key),$db->quote($user->id),$db->quote($bundle_id),$db->quote($currentdate),$db->quote($type));
		//Prepare the insert query.
		$inserquery->insert($db->quoteName('#__social_gcm_users'))->columns($db->quoteName($columns))->values(implode(',', $values));
		$db->setQuery( $inserquery );
		$result = $db->query();
		$res->message = "Your device is register to server.";
		$res->status=$result;
		return $res;
	}
	public function delete_notif()
	{
		$app = JFactory::getApplication();
		$reg_id = $app->input->get('reg_id','','STRING');
		//DB steps
		$db = FD::db();
		//Getting database values to check current user is login again or he change his device then only adding device to database
		$query = $db->getQuery(true);
		// delete all custom keys for user 1001.
		$conditions = " ".$db->quoteName('device_id') ." LIKE '%".$reg_id."%' "; 
		$query->delete($db->quoteName('#__social_gcm_users'));
		$query->where($conditions); 
		$db->setQuery($query);
		//~ echo $query;
		//~ die();		 
		$result = $db->execute();
		return $result;			
	}
}

