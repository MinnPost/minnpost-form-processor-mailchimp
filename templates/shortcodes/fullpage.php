<?php
/**
 * The template for full newsletter subscribe pages
 *
 */
?>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="m-form m-form-fullpage m-form-minnpost-form-processor-mailchimp<?php echo $form['classes']; ?>">
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
	<?php if ( '' !== $form['groups_available'] ) : ?>
		<input type="hidden" name="groups_available" value="<?php echo esc_html( $form['groups_available'] ); ?>">
	<?php endif; ?>
	<div class="m-form-container">
		<?php echo $form['image']; ?>
		<?php if ( isset( $form['user']->groups ) && ! empty( $form['user']->groups ) ) : ?>
			<p class="a-instructions"><?php echo __( 'Your email address currently receives the checked MinnPost newsletters. You can change them here.', 'minnpost-mailchimp-form-processor' ); ?></p>
		<?php else : ?>
			<p class="a-instructions"><?php echo __( 'Pick the newsletters you’d like to receive from us. We have checked a few options to start.', 'minnpost-mailchimp-form-processor' ); ?></p>
		<?php endif; ?>
		<?php echo $form['content_before']; ?>
		<?php echo $form['message']; ?>
			<?php if ( count( $form['group_fields'] ) > 1 ) : ?>
				<div class="m-subscribe-grouping">
					<section class="m-subscribe-items">
						<?php foreach ( $form['group_fields'] as $category ) : ?>
							<?php if ( isset( $category['contains'] ) ) : ?>
								<?php foreach ( $category[ $category['contains'] ] as $item ) : ?>
									<?php
									if ( true === $item['default'] ) {
										$checked = ' checked';
									} else {
										$checked = '';
									}
									?>
									<article>
										<label>
											<?php if ( '' !== $item['grouping'] ) : ?>
												<p class="a-newsletter-group"><?php echo $item['grouping']; ?></p>
											<?php else : ?>
												<p class="a-newsletter-group"><?php echo $category['name']; ?></p>
											<?php endif; ?>
											<input name="groups_submitted[]" type="checkbox" value="<?php echo $item['id']; ?>"<?php echo $checked; ?>>
											<?php if ( false === $form['hide_title'] || true === $form['show_title'] ) : ?>
												<h3 class="a-newsletter-title"><?php echo $item['name']; ?></h3>
											<?php endif; ?>
											<?php if ( false === $form['hide_description'] || true === $form['show_description'] ) : ?>
												<?php echo wpautop( $item['description'] ); ?>
											<?php endif; ?>
										</label>
									</article>
								<?php endforeach; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</section>
					<article class="m-subscribe">
						<div class="m-form-item">
							<label><?php echo __( 'Please enter your email address:', 'minnpost-mailchimp-form-processor' ); ?>
								<input type="email" name="email" value="<?php echo isset( $form['user']->user_email ) ? $form['user']->user_email : ''; ?>" required>
							</label>
							<button type="submit" name="subscribe" class="a-button a-button-next a-button-choose"><?php echo $form['button_text']; ?></button>
						</div>
						<aside class="m-form-after">
							<?php echo $form['content_after']; ?>
						</aside>
					</article>
				</div>
			<?php else : ?>
				<fieldset>
					<label for="full_page_email"><?php echo __( 'Email address:', 'minnpost-mailchimp-form-processor' ); ?></label>
					<div class="a-input-with-button a-button-sentence">
						<input type="email" name="email" id="full_page_email" value="<?php echo isset( $form['user']->user_email ) ? $form['user']->user_email : ''; ?>" required>
						<button type="submit" name="subscribe" class="a-button a-button-next a-button-choose"><?php echo $form['button_text']; ?></button>
					</div>
				</fieldset>
			<?php endif; ?>
		</fieldset>
	</div>
</form>
