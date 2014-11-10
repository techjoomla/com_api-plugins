<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
//function in file - /var/www/mppronline/administrator/components/com_acctexp/admin.acctexp.php


class AecApiResourceSetexpiration extends ApiResource
{
	public function get()
	{
		

	}

	/**
	 * This is not the best example to follow
	 * Please see the category plugin for a better example
	 */
	public function post()
	{
		
		require_once JPATH_SITE .'/components/com_acctexp/acctexp.class.php';
		
		$db = &JFactory::getDBO();	
		$app = JFactory::getApplication();

		$userid = JRequest::getInt('user_id',0);	 
		$planid = JRequest::getInt('plan_id',0);
		$new_expiry = JRequest::getString('date',0);		 
 		//convert date in format
        $new_expiry = date("Y-m-d h:i:s",strtotime($new_expiry)); 	    

		$obj = new stdClass();
		//validate plan
        $plans = SubscriptionPlanHandler::getPlanList();	    
		$muser = metaUserDB::getIDbyUserid($userid);
		$plnuser = SubscriptionPlanHandler::getPlanUserlist($planid);	
		
		if(!$userid) 
		{
			$obj->success = 0;
			$obj->code = 21;
			$obj->message = "invalid user id";
		}
		elseif(!$plans[array_search($planid, $plans)]) 
		{
			$obj->success = 0;
			$obj->code = 22;
			$obj->message = "invalid plan id";
		}		
        elseif($userid == $plnuser[array_search($userid, $plnuser)]) 
		{ 
		
			$plan = new SubscriptionPlan( $db );
			$plan->load( $planid ); 


			$metaUser = new metaUser( $userid );   
			$renew	= $metaUser->is_renewing();

			$lifetime		= $metaUser->focusSubscription->lifetime;
			$metaUser->focusSubscription->plan = $planid;
            $metaUser->focusSubscription->status = 'Active'; 
			$metaUser->temporaryRFIX();
			
			$metaUser->focusSubscription->lifetime = 0;
			//set expiration
			//$now = (int) gmdate('U');
			$metaUser->focusSubscription->expiration = $new_expiry;			
			//$metaUser->objSubscription->expiration = $new_expiry;	

			$reply = $metaUser->focusSubscription->storeload();
			
			if($reply && $planid)
			{
			
			$obj->success = 1;
			$obj->message = "Expiry updated";	
			}
		}
		else
		{
			$obj->success = 0;
			$obj->code = 31;
			$obj->message = "Plan not assigned to user";

        }		

        $this->plugin->setResponse($obj);


	}

	public function put()
	{
		
	}

  function qry()
 {
    /*$db = &JFactory::getDBO();
    $qry = "SELECT count(*) FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id WHERE ((a.status = 'Active' || a.status = 'Trial'))";
    $db->setQuery( $query );
	$total = $db->loadResult(); */
}
	
}
