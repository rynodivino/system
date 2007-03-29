<?php include('header.php'); ?>
<div id="content-area">
	<div class="dashboard-block c3" id="welcome">
	<?php
	if ( isset( $result ) ) {
		switch( $result ) {
			case 'success':
				_e('<p class="update">Your post has been saved.</p>');
				break;
		}
	}
	if ( isset( $slug ) ) {
		$post= Post::get( array( 'slug' => $slug, 'status' => Post::status('any') ) );
	}
	else {
		$post= new Post();
	}
	$tags= htmlspecialchars( Utils::implode_quoted( ',', $post->tags ) );
	
	?>
	<form name="create-content" id="create-content" method="post" action="<?php URL::out( 'admin', 'page=publish' ); ?>">
		<div class="dashboard-block c3 publish">
			<h4>Title</h4>
			<input type="text" name="title" id="title" size="100%" value="<?php echo $post->title; ?>" />

			<h4>Content</h4>
			<textarea name="content" id="content" rows="20"><?php echo $post->content; ?></textarea>
			
			<h4>Tags</h4>
			<div id="tagbox">
				<input type="text" name="tags" class="right" id="tags" value="<?php echo $tags; ?>" />
				<p>Type a new tag or select from the list of existing tags below:</p>
			</div>

			<h4>Page Settings</h4>
			<ul>
				<?php
				$statuses= Post::list_post_statuses();
				foreach ( $statuses as $name => $value ) {
					if ('any' == $name) {
						continue;
					}
					?>
					<li><label><input type="radio" name="status" id="<?php echo $name; ?>" value="<?php echo Post::status($name); ?>" <?php echo ($post->status == Post::status($name) ) ? 'checked="checked"' : ''; ?> ><?php echo $name; ?></label></li>
				<?php
				}
				?>
			</ul>
			<p class="right"><input type="submit" name="submit" id="submit" value="Save!"></p>
		</div>
		<input type="hidden" name="type" value="<?php echo $type; ?>">
		<?php if ( $post->slug != '' ) { ?>
		<input type="hidden" name="slug" id="slug" value="<?php echo $post->slug; ?>">
		<?php } ?>
	</form>
	</div>
</div>
<?php include('footer.php'); ?>
