<?php
/**
 * @package Com_api
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
 
defined('_JEXEC') or die( 'Restricted access' );
jimport('joomla.user.user');

class AkeebasubsApiResourceSubscriptions extends ApiResource
{
	
	static public function routes() {
		$routes[] = 'users/';
		
		return $routes;
	}
	
	public function delete()
	{    	
   	   $this->plugin->setResponse( 'in delete' ); 
	}

	public function post()
	{    	
   	   $this->plugin->setResponse( 'in post' ); 
	}
	
	public function get() {
		
			$input = JFactory::getApplication()->input;
			$user = $this->plugin->getUser();
			$filters = array(
											'search'=>'',
											'title'=>'',
											'enabled'=>'',
											'level'=>'',
											'publish_up'=>'',
											'publish_down'=>'',
											'user_id',
											'paystate'=>'',
											'processor'=>'',
											'paykey'=>'',
											'since'=>'',
											'until'=>'',
											'contact_flag'=>'',
											'expires_from'=>'',
											'expires_to'=>'',
											'refresh'=>'',
											'groupbydate'=>'',
											'groupbyweek'=>'',
											'groupbylevel'=>'',
											'moneysum'=>'',
											'coupon_id'=>'',
											'filter_discountmode'=>'',
											'filter_discountcode'=>'',
											'nozero'=>'',
											'nojoins'=>''
											);

			if (!$user) {
				$this->plugin->setResponse( $this->getErrorResponse(404, JText::_('JERROR_ALERTNOAUTHOR')) );
				return;
			}

			$authorised = $user->authorise('core.manage', 'com_akeebasubs');
			
			if (!$authorised) {
				$this->plugin->setResponse( $this->getErrorResponse(404, JText::_('JERROR_ALERTNOAUTHOR')) );
				return;
			}
			
			$subscriptionsmodel = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel');
			$order = $input->get('filter_order','akeebasubs_subscription_id');
			$orderdir = $input->get('filter_order_Dir','DESC');
			if(!in_array($order, array_keys($subscriptionsmodel->getTable()->getData()))) $order = 'akeebasubs_subscription_id';
			$subscriptionsmodel->setState('filter_order', $order);
			$subscriptionsmodel->setState('filter_order_Dir', $orderdir);
			
			foreach ($filters as $filter=>$val) {
				$subscriptionsmodel->setState($filter, $input->get($filter, $val));
			}
			
			$subscriptionsmodel->limit($input->get('limit', 10))->limitstart($input->get('limit', 0));
			
			$this->plugin->setResponse( $subscriptionsmodel->getList() );
	}
	
}
