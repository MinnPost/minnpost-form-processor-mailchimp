<?php
/**
 * The template for managing settings in user's account
 *
 */
?>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="m-form m-form-standalone m-form-minnpost-form-processor-mailchimp<?php echo $form['classes']; ?>">
	<input type="hidden" name="minnpost_form_processor_mailchimp_nonce" value="<?php echo $form['newsletter_nonce']; ?>">
	<?php if ( 0 !== $form['user'] ) : ?>
		<input type="hidden" name="user_id" value="<?php echo $form['user']->ID; ?>">
		<input type="hidden" name="first_name" value="<?php echo $form['user']->first_name; ?>">
		<input type="hidden" name="last_name" value="<?php echo $form['user']->last_name; ?>">
		<input type="hidden" name="email" value="<?php echo $form['user']->user_email; ?>">
		<?php if ( isset( $form['user']->mailchimp_status ) ) : ?>
			<input type="hidden" name="mailchimp_status" value="<?php echo $form['user']->mailchimp_status; ?>">
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( '' !== $form['action'] ) : ?>
		<input type="hidden" name="action" value="<?php echo esc_attr( $form['action'] ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $form['redirect_url'] ) : ?>
		<input type="hidden" name="redirect_url" value="<?php echo esc_url( $form['redirect_url'] ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $form['groups_available'] ) : ?>
		<input type="hidden" name="groups_available" value="<?php echo esc_html( $form['groups_available'] ); ?>">
	<?php endif; ?>
	<?php echo $form['image']; ?>
	<?php echo $form['content_before']; ?>
	<?php echo $message; ?>

	<?php foreach ( $form['group_fields'] as $category ) : ?>
		<fieldset class="m-form-item m-form-item-<?php echo $category['type']; ?>">
			<label><?php echo $category['name']; ?>:</label>
			<?php if ( isset( $category['contains'] ) ) : ?>
				<div class="checkboxes">
					<?php foreach ( $category[ $category['contains'] ] as $item ) : ?>
						<?php
						if ( true === $item['default'] ) {
							$checked = ' checked';
						} else {
							$checked = '';
						}
						?>
						<label><input name="groups_submitted[]" type="checkbox" value="<?php echo $item['id']; ?>"<?php echo $checked; ?>><?php echo $item['name']; ?></label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</fieldset>
	<?php endforeach; ?>
	<button type="submit" name="subscribe" class="a-button a-button-next a-button-choose"><?php echo __( 'Save Email Preferences', 'minnpost-mailchimp-form-processor' ); ?></button>
	<?php echo $form['content_after']; ?>
</form>
