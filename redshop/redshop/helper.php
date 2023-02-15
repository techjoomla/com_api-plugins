<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;

class Sale_Data 
{  
  function __construct() 
    {
      
    }
   public function total($startdate,$enddate)
   {
            $db = Factory::getDbo();
           
		    $query = "SELECT SUM(order_total) 
		               FROM #__redshop_orders 
		               WHERE DATE(FROM_UNIXTIME(mdate)) BETWEEN '".$startdate."'AND '".$enddate."'
		               AND order_status='s' AND order_payment_status='Paid'"; 
		            	                     	 
		              
	        $db->setQuery( $query ); 
			$total = $db->loadResult();
			if(!$total)	 
				{ 
				 $total = 0;
				 }
			//$total = number_format($total, 2, '.', '');	 
            
             return $total; 
   
   
   }
   public function projected_sale($startdate,$enddate,$total)
   {
            $db = Factory::getDbo();
            $query = "SELECT DATEDIFF('".$enddate."', '".$startdate."')+1";
		    $db->setQuery( $query ); 
			$tday = $db->loadResult();
			$tday = $tday;
			$query = "SELECT DATEDIFF(CURDATE(),'".$startdate."')+1";
		    $db->setQuery( $query ); 
			$day = $db->loadResult();
			if($tday==0)
			{
			  $tday=1;
			}
			if($day==0)
			{
			  $day=1;
			}
			//calculation of projected_sale
			
			$projected_sale = $total/$day*$tday;
			
			return $projected_sale;
			
   
   }
   public function compress($value)
	{
	   if($value>9999)
	   {
	     $value = $value/1000;
	     $value = number_format($value, 2, '.', '');
	     $value .='K'; 
	     return $value;
	   } 
	    else if($value>=99999)
	   {
	     $value = $value/100000;
	     $value .='Lc'; 
	     return $value;
	    }
	   $value = number_format($value, 2, '.', ''); 
	   return $value;
	}
	

}



?>
