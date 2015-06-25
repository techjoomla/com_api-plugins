<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/sharing/sharing.php';
//require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceSocial_share extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->get_content());
	}
	public function post()
	{
		$this->plugin->setResponse($this->put_data());
	}
	
	//data for get social share data
	public function get_content()
	{
	$obj = new SocialSharing();
	$data = $obj->getVendors();
	return $data;	
	}
	
	//social share using easysocial method
	public function put_data()
	{
	$app = JFactory::getApplication();
	$recep = $app->input->get('recipient','','STRING');
	$stream_id = $app->input->get('stream_uid',0,'INT');
	$msg = $app->input->get('message','','STRING');
	
	$res = new stdClass;
	
	if(!$stream_id)
	{
		$res->success = 0;
		$res->message = "Empty stream id not allowed";
	}
	
	//create sharing url
	$sharing = FD::get( 'Sharing', array( 'url' => FRoute::stream( array( 'layout' => 'item', 'id' => $stream_id, 'external' => true, 'xhtml' => true ) ), 'display' => 'dialog', 'text' => JText::_( 'COM_EASYSOCIAL_STREAM_SOCIAL' ) , 'css' => 'fd-small' ) );
	$tok = base64_encode($sharing->url);

	if( is_string( $recep ) )
		{
			$recep = explode( ',', FD::string()->escape( $recep ) );
		}

		if( is_array( $recep ) )
		{
			foreach( $recep as &$recipient )
			{
				$recipient = FD::string()->escape( $recipient );
				
				if(!JMailHelper::isEmailAddress( $recipient ) )
				{
				
					return false;
				}
			}
		}		
		$msg	= FD::string()->escape( $msg );

		// Check for valid data
		if( empty( $recep ) )
		{
			return false;
		}

		if( empty( $tok ) )
		{
			return false;
		}
		$session	= JFactory::getSession();

		$config		= FD::config();

		$limit		= $config->get( 'sharing.email.limit', 0 );

		$now		= FD::date()->toUnix();

		$time		= $session->get( 'easysocial.sharing.email.time' );

		$count		= $session->get( 'easysocial.sharing.email.count' );

		if( is_null( $time ) )
		{
			$session->set( 'easysocial.sharing.email.time', $now );
			$time = $now;
		}

		if( is_null( $count ) )
		{
			$session->set( 'easysocial.sharing.email.count', 0 );
		}

		$diff		= $now - $time;

		if( $diff <= 3600 )
		{
			if( $limit > 0 && $count >= $limit )
			{
				return false;
			}
			$count++;
			$session->set( 'easysocial.sharing.email.count', $count );
		}
		else
		{
			$session->set( 'easysocial.sharing.email.time', $now );
			$session->set( 'easysocial.sharing.email.count', 1 );
		}
		$library = FD::get( 'Sharing' );
		$result = $library->sendLink( $recep, $tok, $msg );
		
		$res->success = 1;
		$res->message = "share successful";
		$res->result = $result;
		
		return $res;
	}	
}		
