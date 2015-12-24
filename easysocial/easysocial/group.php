<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/fields.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceGroup extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getGroup());
	}

	public function post()
	{
		//print_r($FILES);die("in post grp api");
	   $this->plugin->setResponse($this->CreateGroup());
	}
	
	public function delete()
	{
		$app = JFactory::getApplication();
		
		$group_id = $app->input->get('id',0,'INT');
		$valid = 1;
		$result = new stdClass;
		
		$group	= FD::group( $group_id );

		if( !$group->id || !$group_id )
		{
			$result->status = 0;
			$result->message = 'Invalid Group';
			$valid = 0;
		}

		// Only allow super admins to delete groups
		$my 	= FD::user($this->plugin->get('user')->id);

		if( !$my->isSiteAdmin() && !$group->isOwner())
		{
			$result->status = 0;
			$result->message = 'You are not admin / Owner of group to delete group';
			$valid = 0;
		}
		
		if($valid)
		{
			// Try to delete the group
			$group->delete();
			
			$result->status = 1;
			$result->message = 'Group deleted successfully';
		}
		
		$this->plugin->setResponse($result);
	}
	//function use for get friends data
	function getGroup()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$group_id = $app->input->get('id',0,'INT');
		$other_user_id = $app->input->get('user_id',0,'INT'); 
		
		$userid = ($other_user_id)?$other_user_id:$log_user->id;
		$data = array();
		
		$user = FD::user($userid);
		
		$mapp = new EasySocialApiMappingHelper();
	
		$grp_model = FD::model('Groups');
		
		if($group_id)
		{
			$group[] = FD::group($group_id);
			
			//$pth = FD::photo($group[0]->creator_uid,'',$group[0]->id);
		
			$data['data'] = $mapp->mapItem($group,'group',$log_user->id);
		}
		return( $data );
	}
	
	//function for create new group
	function CreateGroup()
	{
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$user = FD::user($log_user->id);
		
		$config	= FD::config();
		
		//create group post structure
		$grp_data = array();
		$result = new stdClass;
		$valid = 1;
		
		$title  = $app->input->get('title',null,'STRING');
		$parmalink  = $app->input->get('parmalink',null,'STRING');
		$description  = $app->input->get('description',null,'STRING');
		$type  = $app->input->get('type',0,'INT');
		$categoryId  = $app->input->get('category_id',0,'INT');
		
		$avtar_pth = '';
		$avtar_scr = '';
		$avtar_typ = '';
		$phto_obj = null;

		if(!empty($_FILES['file']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper();
			//ckecking upload cover
			//$phto_obj = $upload_obj->uploadPhoto($log_user->id,'group');
			$phto_obj = $upload_obj->ajax_avatar($_FILES['file']);
			$avtar_pth = $phto_obj['temp_path'];
			$avtar_scr = $phto_obj['temp_uri'];
			$avtar_typ = 'upload';
			$avatar_file_name = $_FILES['file']['name']; 
		}

		$cover_data = null;
		
		if(!empty($_FILES['cover_file']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper();
			//ckecking upload cover
			$cover_data = $upload_obj->ajax_cover($_FILES['cover_file'],'cover_file');
			//$phtomod	= FD::model( 'Photos' );
			//$cover_obj = $upload_obj->uploadCover($log_user->id,'group');
			//$cover_data = $phtomod->getMeta($cover_obj->id, SOCIAL_PHOTOS_META_PATH);
			//
		}
	
		//

		//check title
		if(empty($title) || $title == null)
		{
			$valid = 0;
			$result->status = 0;
			$result->message[] = "Invalid group name";
			
		}
		
		//check parmalink
		if(empty($parmalink) || $parmalink == null)
		{
			$valid = 0;
			$result->status = 0;
			$result->message[] = "Invalid parmalink";
		}
		
		//check description
		if(empty($description) || $description == null)
		{
			$valid = 0;
			$result->status = 0;
			$result->message[] = "Empty description not allowed";
		}
		
		//check group type
		if(empty($type) || $type == 0)
		{
			$valid = 0;
			$result->status = 0;
			$result->message[] = "Please Add group type";
		}
		
		if(!$valid)
		{
			return $result;
		}
		else
		{
				// create steps
				$db    = FD::db();
				
				$group = FD::table('Group');
				FD::import('admin:/includes/group/group');
				$group = new SocialGroup();
				
				// Load front end's language file
				FD::language()->loadSite();

				$category = FD::table('GroupCategory');
				$category->load($categoryId);

				// Get the steps
				$stepsModel = FD::model('Steps');
				$steps = $stepsModel->getSteps($categoryId, SOCIAL_TYPE_CLUSTERS);

				// Get the fields
				$lib = FD::fields();
				$fieldsModel = FD::model('Fields');

				/*$post = $this->input->post->getArray();
				$args = array(&$post, &$group, &$errors);*/

				// query written due to commented function not working
				$query = "SELECT a.id,a.unique_key	FROM `#__social_fields` AS `a` 
						LEFT JOIN `#__social_apps` AS `b` ON `b`.`id` = `a`.`app_id`
						LEFT JOIN `#__social_fields_steps` AS `d` ON `a`.`step_id` = `d`.`id` 
						WHERE `a`.`step_id` = '".$steps[0]->id."' ORDER BY `d`.`sequence` ASC,`a`.`ordering` ASC";
				
				$db->setQuery( $query );

				$field_ids	= $db->loadAssocList();

				/*foreach ($steps as $step) {

					if ($group->id) {
						$step->fields 	= $fieldsModel->getCustomFields(array('step_id' => $step->id, 'data' => true, 'dataId' => $group->id, 'dataType' => SOCIAL_TYPE_GROUP));
					}
					else {
						$step->fields 	= $fieldsModel->getCustomFields(array('step_id' => $step->id));
					}

					
				}*/ 
				
				foreach($field_ids as $field)
				{
					$grp_data['cid'][] = $field['id'];
					
					switch($field['unique_key'])
					{
						case 'HEADER': break;
						case 'TITLE':	$grp_data['es-fields-'.$field['id']] = $title; 
										break;
						case 'PERMALINK':	$grp_data['es-fields-'.$field['id']] = $parmalink;
											break;
						case 'DESCRIPTION':	$grp_data['es-fields-'.$field['id']] = $description;
											break;
						case 'TYPE':	$grp_data['group_type'] = $type;
										break;
						case 'URL':		$grp_data['es-fields-'.$field['id']] = $app->input->get('website',0,'STRING');
										break;
						case 'PHOTOS':	$grp_data['photo_albums'] = $app->input->get('photo_album',0,'INT');
										break;
						case 'NEWS':	$grp_data['es-fields-'.$field['id']] = $app->input->get('announcements',0,'INT');
										break;
						case 'DISCUSSIONS': $grp_data['es-fields-'.$field['id']] = $app->input->get('discussions',0,'INT');
											break;
						
						case 'AVATAR':	$grp_data['es-fields-'.$field['id']] = Array
												(
													'source' =>$avtar_scr, 
													'path' =>$avtar_pth,
													'data' => '',
													'type' => $avtar_typ,
													'name' => $avatar_file_name
												);
										break;
						case 'COVER':	$grp_data['es-fields-'.$field['id']] = Array
												(
													'data' =>$cover_data,
													'position' =>'{"x":0.5,"y":0.5}' 
												);
										break;
					}
				}
			
				//for check group exceed limit
				if( !$user->getAccess()->allowed( 'groups.create' ) && !$user->isSiteAdmin() )
                               {
                                       $valid = 0;
                                       $result->status = 0;
                                       $result->message[] = "You are not allow to create the group";
                                       return $result;
                               }
                               
                               // Ensure that the user did not exceed their group creation limit        
                               if ($user->getAccess()->intervalExceeded('groups.limit', $user->id) && !$user->isSiteAdmin()) 
                               {
                                       $valid = 0;
                                       $result->status = 0;
                                       $result->message[] = "Group creation limit exceeds";
                                       return $result;
                               }

				// Get current user's info
				$session    = JFactory::getSession();
				// Get necessary info about the current registration process.
				$stepSession	= FD::table( 'StepSession' );
				$stepSession->load( $session->getId() );
				$stepSession->uid = $categoryId;

				// Load the group category
				$category 	= FD::table( 'GroupCategory' );
				$category->load( $stepSession->uid );

				$sequence = $category->getSequenceFromIndex($stepSession->step, SOCIAL_GROUPS_VIEW_REGISTRATION);

				// Load the current step.
				$step 		= FD::table( 'FieldStep' );
				$step->load(array('uid' => $category->id, 'type' => SOCIAL_TYPE_CLUSTERS, 'sequence' => $sequence));

				// Merge the post values
				$registry 	= FD::get( 'Registry' );
				$registry->load( $stepSession->values );

				// Load up groups model
				$groupsModel		= FD::model( 'Groups' );

				// Get all published fields apps that are available in the current form to perform validations
				$fieldsModel 		= FD::model( 'Fields' );
				$fields				= $fieldsModel->getCustomFields( array( 'step_id' => $step->id, 'visible' => SOCIAL_GROUPS_VIEW_REGISTRATION ) );

				// Load json library.
				$json 	= FD::json();

				// Retrieve all file objects if needed
				//$files 		= JRequest::get( 'FILES' );
				
				
				$token      = FD::token();
				
				$disallow = array($token, 'option', 'cid', 'controller', 'task', 'option', 'currentStep');
	
				foreach( $grp_data as $key => $value )
				{
					if (!in_array($key, $disallow))
					{
						if( is_array( $value ) )
						{
							$value  = FD::json()->encode( $value );
						}
						$registry->set( $key , $value );
					}
				}

				// Convert the values into an array.
				$data		= $registry->toArray();
			
				$args       = array( &$data , &$stepSession );

				// Perform field validations here. Validation should only trigger apps that are loaded on the form
				// @trigger onRegisterValidate
				$fieldsLib			= FD::fields();

				// Get the trigger handler
				$handler			= $fieldsLib->getHandler();
				
				// Get error messages
				$errors		= $fieldsLib->trigger( 'onRegisterValidate' , SOCIAL_FIELDS_GROUP_GROUP , $fields , $args, array( $handler, 'validate' ) );

				// The values needs to be stored in a JSON notation.
				$stepSession->values   = $json->encode( $data );
				
				$stepSession->created 	= FD::date()->toMySQL();

				$group 	= $groupsModel->createGroup( $stepSession );

				if($group->id)
				{
					$result->status = 1;
					$result->id = $group->id;
					$this->addTostream($user,$group);
				}
				else
				{
					$result->status = 0;
					$result->id = 0;
					$result->message = 'unable to create group';
				}
				
				return $result;
		}
	}
	
	public function addTostream($my,$group,$registry)
	{
			$stream				= FD::stream();
			$streamTemplate		= $stream->getTemplate();

			// Set the actor
			$streamTemplate->setActor( $my->id , SOCIAL_TYPE_USER );

			// Set the context
			$streamTemplate->setContext( $group->id , SOCIAL_TYPE_GROUPS );

			$streamTemplate->setVerb( 'create' );
			$streamTemplate->setSiteWide();
			$streamTemplate->setAccess( 'core.view' );
			$streamTemplate->setCluster($group->id, SOCIAL_TYPE_GROUP, $group->type );

			// Set the params to cache the group data
			$registry	= FD::registry();
			$registry->set( 'group' , $group );

			// Set the params to cache the group data
			$streamTemplate->setParams( $registry );

			// Add stream template.
			$stream->add( $streamTemplate );
			
			return true;
	}
	
}
