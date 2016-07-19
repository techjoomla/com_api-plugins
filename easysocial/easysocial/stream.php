<?php
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
FD::import('site:/controllers/controller');

class EasysocialApiResourceStreams extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse("Use method post");
	}
	
	public function post()
	{
		$this->plugin->setResponse($this->processAction());
	}		

    public function processAction()
	{
		$app = JFactory::getApplication();
		$target_id = $app->input->get('target_id',0,'INT');
		$action = $app->input->get('action',0,'STRING');
		return $res = ($action == "hide")?$this->hide($target_id):$this->delete($target_id);
	}	

    public function hide($target_id)
	{
		$res = new stdClass();
	
        // If id is null, throw an error.
		if(!$target_id)
		{
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_ERROR_UNABLE_TO_LOCATE_ID');
		}

        // Get logged in user
        $my 	= FD::user();

		// Load the stream item.
		$item 	= FD::table( 'Stream' );
		$item->load( $target_id );

        // Check if the user is allowed to hide this item
        if( !$item->hideable() )
		{
            $res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_STREAM_NOT_ALLOWED_TO_HIDE');
		}

        // Get the model
        $model 	= FD::model( 'Stream' );
		$state	= $model->hide( $target_id , $my->id );
    
        if($state)
		{
			$res->success = 1;
			$res->message = JText::_('PLG_API_EASYSOCIAL_HIDE_NEWSFEED_ITEM');
		}
		else
		{
			$res->success = 0;
			$res->message = JText::_('PLG_API_EASYSOCIAL_HIDE_NEWSFEED_ITEM_ERROR');
		}
		return $res;
	}

    public function delete($target_id)
	{
        $res = new stdClass();
        
        // If id is invalid, throw an error.
        if(!$target_id)
		{
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_ERROR_UNABLE_TO_LOCATE_ID');
		}

		// Get logged in user
		$my = FD::user();
        $access = $my->getAccess();

		// Load the stream item.
		$item = FD::table('Stream');
		$item->load($target_id);
        $state = $item->delete();

		if($state)
		{
			$res->success = 1;
			$res->message = JText::_('PLG_API_EASYSOCIAL_DELETE_NEWSFEED_ITEM');
		}
		else
		{
			$res->success = 0;
			$res->message = JText::_('PLG_API_EASYSOCIAL_DELETE_NEWSFEED_ITEM_ERROR');
		}
		return $res;
    }
}
?>
