<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;
use Joomla\CMS\Factory;



class AecApiResourceUserlist extends ApiResource
{
	public function get()
	{
		$limitstart = Factory::getApplication()->input->get('limitstart',0);
		$limit = Factory::getApplication()->input->get('limit',20);	
        $userid = Factory::getApplication()->input->get('id',0);	 
		$name = Factory::getApplication()->input->get('name','');			
		$username = Factory::getApplication()->input->get('username','');
		$email = Factory::getApplication()->input->get('email','');
		$plan_status = Factory::getApplication()->input->get('plan_status','');	
        $ordering = Factory::getApplication()->input->get('ordering','registerDate');	 
		$orderingdir = Factory::getApplication()->input->get('orderingdir','ASC');			
			

		$db = Factory::getDbo();
		//total active users		
		$query = "SELECT count(*) FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id ";
        /*if($plan_status != '')
		{
         $query .="WHERE a.status = '{$plan_status}'";

		}*/ 

		$db->setQuery( $query );
		$total = $db->loadResult(); 
		
		$query = "SELECT a.*, b.name, b.username, b.email, c.name AS plan_name FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id LEFT JOIN #__acctexp_plans AS c ON a.plan = c.id  ";

		$where = array();
	
		
		if ( $userid)
		{

			$where[] = "b.id ={$userid}";
		}
		if ($name)
		{

			$where[] = " b.name LIKE '%{$name}%' ";
		}
		if ($username !='' )
		{
			$where[] = " b.username LIKE '%{$username}%' ";
		}
		if ($email)
		{
			$where[] = "b.email = '{$email}'";
		}	
	
		if($plan_status != '')
		{
           $where[] = "a.status = '{$plan_status}'";
		}
		
		$where 		= count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' ;		
		$query .= $where;	
		$query .= " ORDER BY {$ordering} {$orderingdir} LIMIT {$limitstart},{$limit}";        
//die("in userlist api");
		$db->setQuery( $query );
		$users = $db->loadObjectList();
        $pln = new stdClass;
        foreach($users as $key=>$val)
		{
          $pln->plan = $val->plan;
		  $pln->id = $val->id;
          $pln->type = $val->type;
          $pln->status = $val->status;
          $pln->signup_date = $val->signup_date;
          $pln->expiration = $val->expiration;    
	
			unset($val->params);	
			unset($val->primary);
			unset($val->plan_name);
			unset($val->recurring);
			unset($val->lifetime);
			unset($val->customparams);
			unset($val->lastpay_date);
			unset($val->eot_date);
			unset($val->eot_cause);
			unset($val->plan);	
			unset($val->id);
			unset($val->type);
			unset($val->status);
			unset($val->signup_date);
			unset($val->expiration);
			unset($val->cancel_date);
			$val->id = $val->userid;	
			$val->plan = $pln;
    	  		
    		unset($val->userid);	

            $users[$key] =  $val; 
		}   

        $data = array('total'=>$total,'count'=>count($users),'data'=>$users);    

		$this->plugin->setResponse($data);


	}

	/**
	 * This is not the best example to follow
	 * Please see the category plugin for a better example
	 */
	public function post()
	{
		
		$this->plugin->setResponse( "this is post data" );
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

  /*function qry()
 {
    $db = Factory::getDbo();
    $qry = "SELECT count(*) FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id WHERE ((a.status = 'Active' || a.status = 'Trial'))";
    $db->setQuery( $query );
	$total = $db->loadResult(); 
}*/
	
}
