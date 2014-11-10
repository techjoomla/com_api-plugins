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


class AecApiResourceSetusertoplan extends ApiResource
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
		//$new_expiry = JRequest::getString('date',0);		 
	
		$obj = new stdClass();
		//validate plan
        $plans = SubscriptionPlanHandler::getPlanList();	    
$muser = metaUserDB::getIDbyUserid($userid);
		
//$pplan = metaUserDB::getPreviousPlan($muser);
//$uplan = metaUserDB::getUsedPlans($muser);
//print_r($new_expiry);die;
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
        else
		{ 
			
			$plan = new SubscriptionPlan( $db );
			$plan->load( $planid ); 
//print_r($plan->params['full_period']);die;	
			//check user is metauser
			/*if ( is_a( $user, 'metaUser' ) ) {
			$metaUser = $user;
			} elseif( is_a( $user, 'Subscription' ) ) {
			$metaUser = new metaUser( $user->userid );

			$metaUser->focusSubscription = $user;
			}*/


			$metaUser = new metaUser( $userid );   
			$renew	= $metaUser->is_renewing();

			//$metaUser->focusSubscription->lifetime;
			$metaUser->focusSubscription->plan = $planid;
            $metaUser->focusSubscription->status = 'Active'; 
			$metaUser->temporaryRFIX();
			
			//$metaUser->focusSubscription->lifetime = 1;
			//set expiration
			$now = (int) gmdate('U');
			//$current = strtotime($new_expiry);
			//$metaUser->focusSubscription->expiration = $new_expiry;			


			//$metaUser->objSubscription->expiration = $new_expiry;	

			$reply = $metaUser->focusSubscription->storeload();
			
			if($reply && $planid)
			{
			$history = new logHistory( $db );
			$obj->success = 1;
			$obj->message = "User added to plan";	
			}
		}	

        $this->plugin->setResponse($obj);

	}

	public function put()
	{
		// Simply call post as K2 will just save an item with an id
		/*$this->post();

		$response = $this->plugin->get( 'response' );
		if ( isset( $response->success ) && $response->success ) {
			JResponse::setHeader( 'status', 200, true );
			$response->code = 200;
			$this->plugin->setResponse( $response );
		}*/
	}

  function qry()
 {
    $db = &JFactory::getDBO();
    $qry = "SELECT count(*) FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id WHERE ((a.status = 'Active' || a.status = 'Trial'))";
    $db->setQuery( $query );
	$total = $db->loadResult(); 
}
	
}
