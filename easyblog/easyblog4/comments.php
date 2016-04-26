<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.user.user');
jimport( 'simpleschema.easyblog.person' );
jimport( 'simpleschema.easyblog.blog.post' );
jimport( 'simpleschema.easyblog.blog.comment' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceComments extends ApiResource
{

	public function get() {

		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$id = $input->get('id', null, 'INT');
		$comments = array();

		// If we have an id try to fetch the blog
		$blog = EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $id );
		
		if (!$blog->id) {
			$this->plugin->setResponse( $this->getErrorResponse(404, JText::_( 'PLG_API_EASYBLOG_INVALID_BLOG' )) );
			return;
		}

		$rows = $model->getBlogComment($id);

		foreach ($rows as $row) {

			$item = new CommentSimpleSchema;
			$item->commentid = $row->id;
			$item->postid = $row->post_id;
			$item->title = $row->title;
			$item->text = EasyBlogCommentHelper::parseBBCode($row->comment);
			$item->textplain = strip_tags(EasyBlogCommentHelper::parseBBCode($row->comment));
			$item->created_date = $row->created;
			$item->created_date_elapsed = EasyBlogDateHelper::getLapsedTime( $row->created );
			$item->updated_date = $row->modified;
			
			// Author
			$item->author->name = isset($row->poster->nickname) ? $row->poster->nickname : $row->name;
			$item->author->photo = isset($row->poster->avatar) ? $row->poster->avatar : 'default_blogger.png';
			$item->author->photo = JURI::root() . 'components/com_easyblog/assets/images/' . $item->author->photo;
			$item->author->email = $row->email;
			$item->author->website = isset($row->poster->url) ? $row->poster->url : $row->url;
			
			$comments[] = $item;
		}
		
		$this->plugin->setResponse( $comments );
		
	}
	
	public function post() {
		$app = JFactory::getApplication();
		$my = $this->plugin->getUser();
		$config = EasyBlogHelper::getConfig();
		$acl = EasyBlogACLHelper::getRuleSet();
		$post = $app->input->post->getArray();

		if( empty($acl->rules->allow_comment) && (empty($my->id) && !$config->get('main_allowguestcomment')) )
		{
			$this->plugin->setResponse( $this->getErrorResponse(500, JText::_('COM_EASYBLOG_NO_PERMISSION_TO_POST_COMMENT')) );
		}

		$isModerated = false;
		$parentId = isset($post['parent_id']) ? $post['parent_id'] : 0;
		$commentDepth = isset($post['comment_depth']) ? $post['comment_depth'] : 0;
		$blogId = isset($post['id']) ? $post['id'] : 0;
		$subscribeBlog = isset($post['subscribe-to-blog']) ? true : false;

		if (!$blogId) {
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Invalid Blog') );
		}
		
		// @task: Cleanup posted values.
		array_walk($post, array($this, '_trim') );
		array_walk($post, array($this, '_revertValue') );

		if( !$config->get( 'comment_require_email' ) && !isset( $post['esemail'] ) )
		{
			$post['esemail']	= '';
		}

		// @task: Run some validation tests on the posted values.
		if(! $this->_validateFields($post))
		{
			$this->plugin->setResponse( $this->getErrorResponse(500, $this->err[0]) );
		}

		// @task: Akismet detection service.
		if( $config->get( 'comment_akismet' ) )
		{
			$data = array(
					'author'    => $post['esname'],
					'email'     => $post['esname'],
					'website'   => JURI::root() ,
					'body'      => $post['comment'] ,
					'permalink' => EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $post['id'] )
				);

			if( EasyBlogHelper::getHelper( 'Akismet' )->isSpam( $data ) )
			{
				$this->plugin->setResponse( $this->getErrorResponse(500, JText::_('COM_EASYBLOG_SPAM_DETECTED_IN_COMMENT')) );
			}
		}

		// @task: Retrieve the comments model
		$model		= EasyBlogHelper::getModel( 'Comment' );

		// @task: Retrieve the comment's table
		$comment	= EasyBlogHelper::getTable( 'Comment' );

		// We need to rename the esname and esemail back to name and email.
		$post['name']	= $post['esname'];
		$post['email']	= $post['esemail'];

		unset($post['esname']);
		unset($post['esemail']);

		// @task: Bind posted values into the table.
		$comment->bindPost( $post );

		// @task: Process registrations
		$registerUser	= isset( $post[ 'esregister' ] ) ? true : false;
		$fullname 		= isset( $post[ 'name' ] ) ? $post['name'] : '';
		$username 		= isset( $post[ 'esusername' ] ) ? $post[ 'esusername' ] : '';
		$email			= $post[ 'email' ];
		$message 		= '';
		$newUserId 		= 0;

		// @task: Process registrations if necessary
		if( $registerUser && $my->id <= 0 )
		{
			$state		= $this->processRegistrations( $post , $username , $email , $ajax );

			if( !is_numeric( $state ) )
			{
				$this->plugin->setResponse( $this->getErrorResponse(500, $state) );
			}

			$newUserId	= $state;
		}

		$totalComments 		= empty( $post[ 'totalComment' ] ) ? 1 : $post[ 'totalComment' ];

		$date 	= EasyBlogHelper::getDate();

		$comment->set( 'created' 	, $date->toMySQL() );
		$comment->set( 'modified'	, $date->toMySQL() );
		$comment->set( 'published'	, 1 );
		$comment->set( 'parent_id'	, $parentId );
		$comment->set( 'sent'		, 0 );
		$comment->set( 'created_by'	, $my->id );

		// @rule: Update the user's id if they have just registered earlier.
		if( $newUserId != 0 )
		{
			$comment->set( 'created_by' , $newUserId );
		}

		// @rule: Update publish status if the comment requires moderation
		if( ($config->get( 'comment_moderatecomment') == 1) || ($my->id == 0 && $config->get( 'comment_moderateguestcomment') == 1) )
		{
			$comment->set( 'published' , EBLOG_COMMENT_STATUS_MODERATED );
			$isModerated	= true;
		}

		$blog	= EasyBlogHelper::getTable( 'Blog' );
		$blog->load($blogId);

		// If moderation for author is disabled, ensure that the comment is published.
		// If the author is the owner of the blog, it should never be moderated.
		if( !$config->get( 'comment_moderateauthorcomment' ) && $blog->created_by == $my->id )
		{
			$comment->set( 'published' , 1 );
			$isModerated	= false;
		}

		if( !$comment->store() )
		{
			$this->plugin->setResponse( $this->getErrorResponse(500, JText::_( 'PLG_API_EASYBLOG_COMMENT_PROBLEM_MESSAGE' )) );
		}

		// @rule: Process subscription for blog automatically when the user submits a new comment and wants to subscribe to the blog.
		if( $subscribeBlog && $config->get( 'main_subscription' ) && $blog->subscription )
		{
			$isSubscribed   = false;
			$userId     	= $my->id;
			$blogModel		= EasyblogHelper::getModel('Blog');

			if( $userId == 0 )
			{
				$sid	= $blogModel->isBlogSubscribedEmail( $blog->id , $email );

				if( empty( $sid ) )
				{
					$isSubscribed = $blogModel->addBlogSubscription( $blog->id , $email, '', $fullname );
				}
			}
			else
			{
				$sid	= $blogModel->isBlogSubscribedUser( $blog->id , $userId , $email);
				if( !empty( $sid ) )
				{
					// @task: User found, update the email address
					$blogModel->updateBlogSubscriptionEmail($sid, $userId, $email);
				}
				else
				{
					$isSubscribed = $blogModel->addBlogSubscription( $blog->id , $email, $userId, $fullname);
				}
			}
		}

		$row 		= $comment;
		$creator	= EasyBlogHelper::getTable( 'Profile' );
		$creator->load( $my->id );

		$row->poster		= $creator;
		$row->comment   	= nl2br($row->comment);
		$row->comment   	= EasyBlogCommentHelper::parseBBCode($row->comment);
		$row->depth 		= (is_null($commentDepth)) ? '0' : $commentDepth;
		$row->likesAuthor   = '';

		// @rule: Process notifications
		$comment->processEmails( $isModerated , $blog );

		//update the sent flag to sent
		$comment->updateSent();

		// @TODO - Move this to a map comment function
		$item = new CommentSimpleSchema;
		$item->commentid = $comment->id;
		$item->postid = $comment->post_id;
		$item->title = $comment->title;
		$item->text = EasyBlogCommentHelper::parseBBCode($comment->comment);
		$item->textplain = strip_tags(EasyBlogCommentHelper::parseBBCode($comment->comment));
		$item->created_date = $comment->created;
		$item->created_date_elapsed = EasyBlogDateHelper::getLapsedTime( $comment->created );
		$item->updated_date = $comment->modified;
		
		// Author
		$item->author->name = isset($comment->poster->nickname) ? $comment->poster->nickname : $comment->name;
		$item->author->photo = isset($comment->poster->avatar) ? $comment->poster->avatar : 'default_blogger.png';
		$item->author->photo = JURI::root() . 'components/com_easyblog/assets/images/' . $item->author->photo;
		$item->author->email = $comment->email;
		$item->author->website = isset($comment->poster->url) ? $comment->poster->url : $comment->url;
		
		$this->plugin->setResponse( $item );

	}
	
	public static function getName() {
		
	}
	
	public static function describe() {
		
	}
	

	function _validateFields($post)
	{
		$config = EasyBlogHelper::getConfig();
		$my     = JFactory::getUser();

		if( !isset( $post[ 'comment' ] ) )
		{
			return false;
		}

		if(JString::strlen($post['comment']) == 0)
		{
			$this->err[0]	= JText::_('COM_EASYBLOG_COMMENT_IS_EMPTY');
			$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_COMMENT' );
			return false;
		}

		if( $config->get('comment_requiretitle') && (JString::strlen($post['title']) == 0 || $post['title'] == JText::_('COM_EASYBLOG_TITLE')))
		{
			$this->err[0]	= JText::_( 'COM_EASYBLOG_COMMENT_TITLE_IS_EMPTY' );
			$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_TITLE' );
			return false;
		}

		if(isset($post['esregister']) && isset($post['esusername']))
		{
			if(JString::strlen($post['esusername']) == 0 || $post['esusername'] == JText::_('COM_EASYBLOG_USERNAME'))
			{
				$this->err[0]	= JText::_('COM_EASYBLOG_SUBSCRIPTION_USERNAME_IS_EMPTY');
				$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_ESUSERNAME' );
				return false;
			}
		}

		if(JString::strlen($post['esname']) == 0 || $post['esname'] == JText::_('COM_EASYBLOG_NAME'))
		{
			$this->err[0]	= JText::_('COM_EASYBLOG_COMMENT_NAME_IS_EMPTY');
			$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_ESNAME' );
			return false;
		}


		// @rule: Only check for valid email when the email is really required
		if( $config->get( 'comment_require_email' ) && (JString::strlen($post['esemail']) == 0 || $post['esemail'] == JText::_('COM_EASYBLOG_EMAIL') ) )
		{
			$this->err[0]	= JText::_('COM_EASYBLOG_COMMENT_EMAIL_IS_EMPTY');
			$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_ESEMAIL' );
			return false;
		}
		else if( isset( $post['subscribe-to-blog']) && (JString::strlen($post['esemail']) == 0 || $post['esemail'] == JText::_('COM_EASYBLOG_EMAIL') ))
		{
			$this->err[0]	= JText::_('COM_EASYBLOG_COMMENT_EMAIL_IS_EMPTY');
			$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_ESEMAIL' );
			return false;
		}
		else
		{
			if( (! EasyBlogHelper::getHelper( 'email' )->isValidInetAddress( $post['esemail'] )) && ($config->get( 'comment_require_email' ) || isset( $post['subscribe-to-blog']) ))
			{
				$this->err[0]	= JText::_('COM_EASYBLOG_COMMENT_EMAIL_INVALID');
				$this->err[1]	= JText::_( 'PLG_API_EASYBLOG_ESEMAIL' );
				return false;
			}
		}

		return true;
	}

	function _trim(&$text)
	{
		$text = JString::trim($text);
	}

	function _revertValue(&$text)
	{
		if( $text == JText::_('COM_EASYBLOG_TITLE') ||
			$text == JText::_('COM_EASYBLOG_USERNAME') ||
			$text == JText::_('COM_EASYBLOG_NAME') ||
			$text == JText::_('COM_EASYBLOG_EMAIL') ||
			$text == JText::_('COM_EASYBLOG_WEBSITE'))
		{
			$text = '';
		}
	}

}
