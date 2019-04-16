<?php
/**
 * The template for showing a logged in user their current MailChimp settings
 *
 */
?>
<?php if ( ! empty( $form['group_fields'] ) ) : ?>
	<dl class="a-user-emails">
		<?php foreach ( $form['group_fields'] as $category ) : ?>
			<dt><?php echo $category['name']; ?></dt>
			<?php if ( isset( $category['contains'] ) ) : ?>
				<dd><?php echo implode( ', ', array_column( $category[ $category['contains'] ], 'name' ) ); ?></dd>
			<?php endif; ?>
		<?php endforeach; ?>
	</dl>
<?php endif; ?>
