<?php
/**
 * Admin settings page view
 *
 * @package OW_Google_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap owgr-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'owgr_messages' ); ?>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'Setup Guide', 'ow-google-reviews' ); ?></h2>

		<div class="owgr-notice owgr-notice--warning">
			<p>
				<strong><?php esc_html_e( 'Testing mode limitation', 'ow-google-reviews' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'This plugin uses an OAuth project in Google Cloud Testing mode. Google issues refresh tokens that expire after 7 days in Testing mode, so you will need to click "Connect Google Account" again roughly once a week. This is a temporary workaround until you move the project to Production and complete Google verification.', 'ow-google-reviews' ); ?>
			</p>
		</div>

		<ol class="owgr-steps">
			<li>
				<?php esc_html_e( 'Create a project in the', 'ow-google-reviews' ); ?>
				<a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Cloud Console', 'ow-google-reviews' ); ?></a>.
			</li>
			<li>
				<?php esc_html_e( 'Enable the', 'ow-google-reviews' ); ?>
				<strong><?php esc_html_e( 'Google My Business API', 'ow-google-reviews' ); ?></strong>.
				<?php esc_html_e( 'If you cannot find it by name, enable it directly at', 'ow-google-reviews' ); ?>
				<a href="https://console.cloud.google.com/apis/library/mybusiness.googleapis.com" target="_blank" rel="noopener noreferrer">https://console.cloud.google.com/apis/library/mybusiness.googleapis.com</a>.
			</li>
			<li>
				<?php esc_html_e( 'Go to APIs & Services &rarr; OAuth consent screen. Choose', 'ow-google-reviews' ); ?>
				<strong><?php esc_html_e( 'External', 'ow-google-reviews' ); ?></strong>
				<?php esc_html_e( 'and leave the project in', 'ow-google-reviews' ); ?>
				<strong><?php esc_html_e( 'Testing', 'ow-google-reviews' ); ?></strong>.
				<?php esc_html_e( 'Add your own Google account email as a', 'ow-google-reviews' ); ?>
				<strong><?php esc_html_e( 'Test User', 'ow-google-reviews' ); ?></strong>.
			</li>
			<li>
				<?php esc_html_e( 'Go to Credentials &rarr; Create Credentials &rarr; OAuth Client ID &rarr; Web Application. Paste the Authorized Redirect URI below into the redirect box.', 'ow-google-reviews' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Paste the Client ID and Client Secret into the fields below, save, then click Connect Google Account.', 'ow-google-reviews' ); ?>
			</li>
		</ol>

		<div class="owgr-field">
			<label><?php esc_html_e( 'Authorized Redirect URI', 'ow-google-reviews' ); ?></label>
			<input type="text" readonly value="<?php echo esc_url( OWGR_Google_API::get_redirect_uri() ); ?>" class="regular-text" onclick="this.select();">
			<p class="description"><?php esc_html_e( 'Copy this exact URL into Google Cloud.', 'ow-google-reviews' ); ?></p>
		</div>
	</div>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'API Credentials', 'ow-google-reviews' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'owgr_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="owgr_client_id"><?php esc_html_e( 'Client ID', 'ow-google-reviews' ); ?></label></th>
					<td><input type="text" id="owgr_client_id" name="owgr_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="owgr_client_secret"><?php esc_html_e( 'Client Secret', 'ow-google-reviews' ); ?></label></th>
					<td><input type="password" id="owgr_client_secret" name="owgr_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="owgr_maps_url"><?php esc_html_e( 'Google Maps Profile URL', 'ow-google-reviews' ); ?></label></th>
					<td>
						<input type="url" id="owgr_maps_url" name="owgr_maps_url" value="<?php echo esc_url( $maps_url ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Paste the public Google Maps URL for this business. Used in the review schema sameAs property.', 'ow-google-reviews' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="owgr_test_mode"><?php esc_html_e( 'Test Mode', 'ow-google-reviews' ); ?></label></th>
					<td>
						<label for="owgr_test_mode">
							<input type="checkbox" id="owgr_test_mode" name="owgr_test_mode" value="1" <?php checked( '1', $test_mode ); ?>>
							<?php esc_html_e( 'Show placeholder Lorem Ipsum reviews instead of live reviews. Schema output is disabled in test mode.', 'ow-google-reviews' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Credentials', 'ow-google-reviews' ) ); ?>
		</form>
	</div>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'OAuth Connection', 'ow-google-reviews' ); ?></h2>
		<?php if ( $auth_url ) : ?>
			<p>
				<a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect Google Account', 'ow-google-reviews' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Enter and save your Client ID first to generate the connection link.', 'ow-google-reviews' ); ?></p>
		<?php endif; ?>

		<?php if ( $api->get_access_token() ) : ?>
			<p class="owgr-status owgr-status--success"><?php esc_html_e( 'Access token stored.', 'ow-google-reviews' ); ?></p>
			<p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ow-google-reviews&owgr_action=disconnect' ), 'owgr_disconnect' ) ); ?>" class="button owgr-button--danger" onclick="return confirm('<?php echo esc_js( __( 'Reset the Google connection? This will not delete stored reviews.', 'ow-google-reviews' ) ); ?>');">
					<?php esc_html_e( 'Reset Connection', 'ow-google-reviews' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'Business Profile Selection', 'ow-google-reviews' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Fetch accounts and locations automatically, or enter them manually if you already know the IDs.', 'ow-google-reviews' ); ?></p>

		<p>
			<button type="button" id="owgr-fetch-accounts" class="button">
				<?php esc_html_e( 'Fetch Accounts', 'ow-google-reviews' ); ?>
			</button>
			<button type="button" id="owgr-fetch-locations" class="button" disabled>
				<?php esc_html_e( 'Fetch Locations', 'ow-google-reviews' ); ?>
			</button>
		</p>

		<div id="owgr-accounts-wrap" style="display:none;">
			<label for="owgr-account-select"><?php esc_html_e( 'Account', 'ow-google-reviews' ); ?></label>
			<select id="owgr-account-select"></select>
		</div>

		<div id="owgr-locations-wrap" style="display:none;">
			<label for="owgr-location-select"><?php esc_html_e( 'Location', 'ow-google-reviews' ); ?></label>
			<select id="owgr-location-select"></select>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ow-google-reviews' ) ); ?>">
			<?php wp_nonce_field( 'owgr_save_account_location' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="owgr_account_id"><?php esc_html_e( 'Account ID', 'ow-google-reviews' ); ?></label></th>
					<td><input type="text" id="owgr_account_id" name="owgr_account_id" value="<?php echo esc_attr( $account_id ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="owgr_location_id"><?php esc_html_e( 'Location ID', 'ow-google-reviews' ); ?></label></th>
					<td><input type="text" id="owgr_location_id" name="owgr_location_id" value="<?php echo esc_attr( $location_id ); ?>" class="regular-text"></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Account & Location', 'ow-google-reviews' ), 'secondary', 'owgr_save_account_location' ); ?>
		</form>
	</div>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'Sync Status', 'ow-google-reviews' ); ?></h2>
		<p>
			<strong><?php esc_html_e( 'Stored reviews:', 'ow-google-reviews' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $review_count ) ); ?>
		</p>
		<?php if ( $last_sync ) : ?>
			<p>
				<strong><?php esc_html_e( 'Last sync:', 'ow-google-reviews' ); ?></strong>
				<?php echo esc_html( human_time_diff( strtotime( $last_sync ), current_time( 'timestamp' ) ) ); ?>
				<?php esc_html_e( 'ago', 'ow-google-reviews' ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $connected ) : ?>
			<p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ow-google-reviews&owgr_action=sync_now' ), 'owgr_sync_now' ) ); ?>" class="button">
					<?php esc_html_e( 'Sync Reviews Now', 'ow-google-reviews' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Complete the OAuth connection and select a location before syncing.', 'ow-google-reviews' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="owgr-card">
		<h2><?php esc_html_e( 'Gutenberg Blocks', 'ow-google-reviews' ); ?></h2>
		<p><?php esc_html_e( 'Two blocks are available in the editor once setup is complete:', 'ow-google-reviews' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Recent Google Reviews', 'ow-google-reviews' ); ?></strong> — <?php esc_html_e( 'Show a configurable number of the latest reviews with an optional "View all" button.', 'ow-google-reviews' ); ?></li>
			<li><strong><?php esc_html_e( 'All Google Reviews', 'ow-google-reviews' ); ?></strong> — <?php esc_html_e( 'Display every stored review. Ideal for a dedicated reviews page.', 'ow-google-reviews' ); ?></li>
		</ul>
	</div>
</div>
