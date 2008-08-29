<?php

/**
 * Undelete Class
 * 
 * This class provides undelete functionality for posts and comments, and
 * provides a trashcan interface for restoring items.
 *
 **/

class Undelete extends Plugin
{
	/**
	 * function info
	 * Returns information about this plugin
	 * @return array Plugin info array
	 **/
	function info()
	{
		return array (
			'name' => 'Undelete',
			'url' => 'http://habariproject.org/',
			'author' => 'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'version' => '1.0',
			'description' => 'Stores deleted items in a virtual trashcan to support undelete functionality.',
			'license' => 'Apache License 2.0',
		);
	}

	/**
	 * function action_plugin_activation
	 * adds the "deleted" status type to the poststatus table
	 * when this plugin is activated.
	**/
	public function action_plugin_activation( $file )
	{
		if ( realpath( $file ) == __FILE__ ) {
			Post::add_new_status( 'deleted', true );
			Options::set( 'undelete__style', '#primarycontent .deleted { background-color: #933; text-decoration: line-through; }' );
		}
	}
	
	/**
	 * function actions_plugins_loaded
	 * Executes after all plugins are loaded
	 **/
	public function action_plugins_loaded()
	{
		//Utils::debug('ok');
	}

	/**
	 * function filter_allow_post_delete
	 * This function is executed when the filter "before_post_delete" is 
	 * called just before a post is to be deleted.
	 * This filter should return a boolean value to indicate whether
	 * the post should be deleted or not.
	 * @param Boolean Whether to delete the post or not
	 * @param Post The post object to potentially delete
	 * @return Boolean Whether to delete the post or not
	 **/
	function filter_post_delete_allow( $result, $post )
	{
		// all we need to do is set the post status to "deleted"
		// and then return false.  The Post::delete() method will
		// see the false return value, and simply return, leaving
		// the post in the database.
		// However, we should capture the current status and save
		// it in a postinfo record, so that undelete can restore
		// it to that status
		if($post->status != Post::status('deleted')) {
			$post->info->prior_status= $post->status;
			$post->status= Post::status('deleted');
			$post->update();
			return false;
		} else {
			return true;
		}
	}
	
	function filter_post_actions($actions, $post) {
		if($post->status == Post::status('deleted')) {
			$actions['remove']['label']= _t('Delete forever');
			$actions['remove']['title']= _t('Permanently delete this post');
		}
		
		return $actions;
	}

	/**
	 * function undelete_post
	 * This function reverts a post's status from 'deleted' to whatever
	 * it previously was.
	**/
	function undelete_post( $post_id )
	{
		$post= Post::get( array( 'id' => $post_id ) );
		$post->status= $post->info->prior_status;
		unset( $post->info->prior_status );
		$post->update();
	}

/*
	public function filter_rewrite_rules()
	{
	}
*/

	public function filter_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[]= _t( 'Configure' );
			$actions[]= _t( 'Empty Trash' );
		}
		return $actions;
	}

	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
			case _t( 'Configure' ):
				$ui= new FormUI( strtolower( get_class( $this ) ) );
				$ui->append( 'text', 'style', 'option:undelete__style', _t( 'Style declaration for deleted content:' ) );
				$ui->append( 'submit', 'save', 'Save' );
				$ui->on_success( array( $this, 'updated_config' ) );
				$ui->out();
				break;
				
			case _t( 'Empty Trash' ):
				$ui= new FormUI( strtolower( get_class( $this ) ) );
				$ui->append( 'static', 'explanation', _t('Pressing this button will permanently delete all posts from the virtual trash can. You cannot undo this.') );
				$ui->append( 'submit', 'delete', 'Delete All' );
				$ui->on_success( array( $this, 'deleted_all' ) );
				$ui->out();
				break;
			}
			
		}
	}

	public function updated_config( $ui )
	{
		$ui->save();
		return false;
	}
	
	public function deleted_all( $ui )
	{
		$count= self::delete_all();
		
		Session::notice(sprintf( _t('Permanently deleted %d posts'), $count));
		return false;
	}
	
	// This method will permanently delete all posts stored in the trash can
	function delete_all() {
		$posts= Posts::get(array('status' => Post::status('deleted'), 'nolimit' => TRUE));
		
		$count= 0;
		
		foreach($posts as $post) {
			$post->delete();
			$count++;
		}
		
		return $count;
	}
	
	// this method will inject some CSS into the <head>
	// so that deleted posts will show up differently
	function action_template_header()
	{
		// only show the style to logged in users
		if ( User::identify() !== false ) {
			echo '<style type="text/css">';
			Options::out( 'undelete__style' );
			echo '</style>';
		}
	}
}

?>
