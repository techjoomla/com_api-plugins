<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;

class RedshopApiResourceDetails extends ApiResource
{
	public function get()
	{
        	$startdate	= Factory::getApplication()->input->get('startdate');
        	$startdate .= '00:00:00';
			$enddate	= Factory::getApplication()->input->get('enddate');
        	$enddate .= ' 23:59:59';
			$config =Factory::getConfig();
            $offset = $config->getValue('config.offset');
            $startdate= Factory::getDate($startdate,$offset);
            $enddate= Factory::getDate($enddate,$offset);
           	$startdate = $startdate->toFormat('%F %T');  
            $enddate = $enddate->toFormat('%F %T');
					
		$db = Factory::getDbo();
		$query = "SELECT a.order_item_sku AS product_sku,b.product_name,SUM(a.product_quantity)AS quantity,
					   SUM(a.product_final_price) AS product_sales
					   FROM #__redshop_order_item AS a,#__redshop_product AS b WHERE a.product_id=b.product_id
					   AND a.order_id IN (SELECT order_id FROM #__redshop_orders 
		               WHERE order_status='S' AND order_payment_status='Paid')
		               AND FROM_UNIXTIME(a.mdate)
		               BETWEEN '$startdate'  AND '$enddate' 
		               GROUP BY a.order_item_sku ORDER BY product_sales DESC";
		               	
		$db->setQuery( $query );
		$details['data'] =array($db->loadObjectList());
		
		$query ="SELECT SUM(order_total)
		               FROM #__redshop_orders 
		               WHERE DATE(FROM_UNIXTIME(mdate))BETWEEN '".$startdate."'AND '".$enddate."'
		               AND order_status='s' AND order_payment_status='Paid'";
		$db->setQuery( $query ); 
		$total =(int)$db->loadResult();
		
		if($total>9999)
	   			{
	     			$total = $total/1000;
	     			$total .='K'; 
	     		}
	     	elseif(!$total)	 
				{ $total=0;}
				
		$details['total'] =array("total"=>$total);
		
		$this->plugin->setResponse($details);
				
		//$this->plugin->setResponse( 'Guru Katre' );
	}

	public function post()
	{
			$db = Factory::getDbo();
	   		
	   		require_once(dirname(__FILE__).DS.'helper.php');
				
			//get date from app
			$startdate = Factory::getApplication()->input->get('startdate');
			$enddate = Factory::getApplication()->input->get('enddate');
			
			$startdate .= ' 00:00:00';
			$enddate .= ' 23:59:59';
			
			$sdate = $startdate;
			$edate = $enddate;
			
			//get offset value					
			$config =Factory::getConfig();
            $offset = $config->getValue('config.offset');
            
            $startdate= Factory::getDate($startdate,$offset);
            $enddate= Factory::getDate($enddate,$offset);
            $startdate = $startdate->toFormat('%F %T');                                
            $enddate = $enddate->toFormat('%F %T');
		//query for product details
		$query = "SELECT a.order_item_sku AS product_sku,b.product_name,SUM(a.product_quantity)AS quantity,
					   SUM(a.product_final_price) AS product_sales
					   FROM #__redshop_order_item AS a,#__redshop_product AS b WHERE a.product_id=b.product_id
					   AND a.order_id IN (SELECT order_id FROM #__redshop_orders 
		               WHERE order_status='S' AND order_payment_status='Paid')
		               AND FROM_UNIXTIME(a.mdate)
		               BETWEEN '$startdate'  AND '$enddate' 
		               GROUP BY a.order_item_sku ORDER BY product_sales DESC";
		               	
		$db->setQuery( $query );
		$details['data'] =array($db->loadObjectList());
		$i = 0;
                   while(count($details['data'][0])>=$i)
                   {                        
                         if($details['data'][0][$i]->product_sales > 9999)
                         {
                         $value = $details['data'][0][$i]->product_sales/1000;
                         $value = number_format($value, 2, '.', '');
                         $value .='K';
                         $details['data'][0][$i]->product_sales = $value;
                        
                         } 
                         $i++;
                       }
		$total = Sale_Data::total($startdate,$enddate); 
		    $projected_sale = Sale_Data::projected_sale($sdate,$edate,$total);
		    $total = Sale_Data::compress($total);
		    $projected_sale = Sale_Data::compress($projected_sale);
					
		$details['total'] =array("total"=>$total);
		$details['projected_sale'] =array("projected_sale"=>$projected_sale);  	
		$this->plugin->setResponse($details);
	}

	public function put()
	{	
		$app = Factory::getApplication();
		$data = Factory::getApplication()->input->get('jform', array(), 'post', 'array');
		$context = 'com_content.edit.article';

		// Fake parameters
		$values	= (array) $app->getUserState($context.'.id');
		array_push($values, (int) $data['id']);
		$values = array_unique($values);
		$app->setUserState($context.'.id', $values);
		if ( !Factory::getApplication()->input->get( 'id' ) ) {
			$_POST['id'] = $data['id'];
			$_REQUEST['id'] = $data['id'];
		}

		// Simply call post as Joomla will just save an article with an id
		$this->post();

		$response = $this->plugin->get( 'response' );
		if ( isset( $response->success ) && $response->success ) {
			JResponse::setHeader( 'status', 200, true );
			$response->code = 200;
			$this->plugin->setResponse( $response );
		}
	}
}
