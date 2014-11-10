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


class AecApiResourcePlanslist extends ApiResource
{
	public function get()
	{
		require_once JPATH_SITE .'/components/com_acctexp/acctexp.class.php';
		$limitstart = JRequest::getInt('limitstart',0);
		$limit = JRequest::getInt('limit',20);	
        $active = JRequest::getInt('active',0);	 
		$visible = JRequest::getInt('visible',0);			
		$name = JRequest::getVar('name','');
		$pattern = '/' . preg_quote($name, '/') . '/';      

        $limit = ($limit>100)?100:$limit;
 
		$t_plans = SubscriptionPlanHandler::getFullPlanList();
		$plans	= SubscriptionPlanHandler::getFullPlanList($limitstart,$limit);
        $data = array();
        $data["total"] = count($t_plans);
        $sel_plan = array();  
		foreach($plans as $k=>$val)
		{

			//$val->group_id = ItemGroupHandler::getItemListItem($val);
			unset($val->params);    
			unset($val->custom_params);    
			unset($val->restrictions);
			unset($val->micro_integrations);
			unset($val->lifetime);
			unset($val->email_desc);

			if($active && $visible && $val->active == 1 && $val->visible == 1 )
			  {
                 $sel_plan[$val->id] = $val;
              }
			elseif($active && $val->active == $active && $val->visible != 0 )
              {

				 $sel_plan[$val->id] = $val;
              }
			elseif($visible && $val->visible == $visible && $val->active != 0)
              {
		
				 $sel_plan[$val->id] = $val;
              }
              elseif($visible ==0 && $active == 0 && $name == '' )
               {
				  $sel_plan[$val->id] = $val;					
               }
		            	
		}
		//$match = preg_match($pattern, $val->name);
            $name_arr = array(); 
			foreach($sel_plan as $k=>$v)
			{

				if(preg_match($pattern, $v->name))
				{	
			
				$name_arr[$v->id] = $v;
				} 
				$sel_plan =$name_arr;		
			}			
	            

		
		$data['count']=count($sel_plan);
        $data['users']=$sel_plan;    
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

  function qry()
 {
    $db = &JFactory::getDBO();
    $qry = "SELECT count(*) FROM #__acctexp_subscr AS a INNER JOIN #__users AS b ON a.userid = b.id WHERE ((a.status = 'Active' || a.status = 'Trial'))";
    $db->setQuery( $query );
	$total = $db->loadResult(); 
}
	
}
