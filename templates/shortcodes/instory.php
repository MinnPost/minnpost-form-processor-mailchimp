<?php
/**
 * The template for in-story newsletter subscribe forms
 *
 */
?>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="m-form m-form-instory m-form-minnpost-form-processor-mailchimp<?php echo $form['classes']; ?>">
	<input type="hidden" name="minnpost_form_processor_mailchimp_nonce" value="<?php echo $form['newsletter_nonce']; ?>">
	<?php if ( 0 !== $form['user'] ) : ?>
		<input type="hidden" name="user_id" value="<?php echo $form['user']->ID; ?>">
		<input type="hidden" name="mailchimp_user_id" value="<?php echo $form['user']->mailchimp_user_id; ?>">
		<input type="hidden" name="first_name" value="<?php echo $form['user']->first_name; ?>">
		<input type="hidden" name="last_name" value="<?php echo $form['user']->last_name; ?>">
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
	<?php if ( ! empty( $form['groups_available'] ) ) : ?>
		<?php if ( is_array( $form['groups_available'] ) ) : ?>
				<?php foreach ( $form['groups_available'] as $group ) : ?>
					<input type="hidden" name="groups_available[]" value="<?php echo esc_attr( $group ); ?>">
				<?php endforeach; ?>
		<?php else : ?>
			<input type="hidden" name="groups_available" value="<?php echo esc_attr( $form['groups_available'] ); ?>">
		<?php endif; ?>
	<?php endif; ?>

	<?php include_once( plugin_dir_path( __FILE__ ) . '../template-parts/honeypots.php' ); ?>
	
	<div class="m-form-container">
		<?php echo $form['image']; ?>
		<?php echo $form['content_before']; ?>
		<?php echo $form['message']; ?>
		<fieldset>
			<div class="a-input-with-button a-button-sentence">
				<input type="email" name="email" value="<?php echo isset( $form['user']->user_email ) ? $form['user']->user_email : ''; ?>" placeholder="Your email address" required>
				<button type="submit" name="subscribe" class="a-button a-button-next a-button-choose"><?php echo $form['button_text']; ?></button>
			</div>
		</fieldset>
		<?php echo $form['content_after']; ?>
	</div>
</form>
