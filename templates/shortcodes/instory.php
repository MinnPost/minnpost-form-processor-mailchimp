<?php
/**
 * The template for in-story newsletter subscribe forms
 *
 */
?>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="m-form m-form-instory">
	<input type="hidden" name="mp_newsletter_form_nonce" value="<?php echo $form['newsletter_nonce']; ?>">
	<?php if ( 0 !== $form['user'] ) : ?>
		<input type="hidden" name="user_id" value="<?php echo $form['user']->ID; ?>">
		<input type="hidden" name="first_name" value="<?php echo $form['user']->first_name; ?>">
		<input type="hidden" name="last_name" value="<?php echo $form['user']->last_name; ?>">
	<?php endif; ?>
	<input type="hidden" name="action" value="newsletter_shortcode">
	<?php if ( '' !== $form['redirect_url'] ) : ?>
		<input type="hidden" name="redirect_url" value="<?php echo esc_url( $form['redirect_url'] ); ?>">
	<?php endif; ?>
	<?php if ( ! empty( $form['lists'] ) ) : ?>
		<input type="hidden" name="newsletters_available[]" value="d89249e207">
		<input type="hidden" name="_newsletters[]" value="d89249e207">
	<?php endif; ?>
	<?php echo wpautop( $content ); ?>
	<?php echo $message; ?>
	<fieldset>
		<div class="m-field-group m-form-item m-form-item-signup">
			<input type="email" name="email" value="<?php echo isset( $form['user']->user_email ) ? $form['user']->user_email : ''; ?>" placeholder="Your email address">
			<button type="submit" name="subscribe" class="a-button a-button-next a-button-choose"><?php echo __( 'Subscribe', 'minnpost-mailchimp-form-processor' ); ?></button>
		</div>
	</fieldset>
</form>
