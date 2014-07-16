<?php

defined('_JEXEC') or die( 'Restricted access' );
jimport('joomla.user.user');

require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

class UsersApiResourceUsers extends ApiResource
{
	
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

		// If we have an id try to fetch the user
		if ($id = $input->get('id')) {
			$user = JUser::getInstance($id);
			
			if (!$user->id) {
				$this->plugin->setResponse( $this->getErrorResponse(404, 'User not found') );
				return;
			}
			
			$this->plugin->setResponse( $user );			
		} else {
			$model = new UsersModelUsers;
			$users = $model->getItems();
			
			foreach ($users as $k => $v) {
				unset($users[$k]->password);
			}
			
			$this->plugin->setResponse( $users );
		}
	}
	
}
