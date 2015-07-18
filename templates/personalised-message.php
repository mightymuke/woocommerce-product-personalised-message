<p class="personalised-message" style="clear:both; padding-top: .5em;">
	<label><?php echo str_replace( array( '{textarea}', '{price}' ), array( $textarea, $price_text ), wp_kses_post( $personalised_message_label ) ); ?></label>
</p>
