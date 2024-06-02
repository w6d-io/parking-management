<?php

namespace ParkingManagement\Admin;

use ParkingManagement\ParkingManagement;

class Pages
{
	public static function management(): void
	{
		$pm = ParkingManagement::get_current();
		echo '<div class="wrap pkmgmt-parking-management-config">';
		echo sprintf('<h2>%s</h2>',
			esc_html(__('Parking Management', 'parking-management')),
		);
		if ($pm === null) {
			$_REQUEST['message'] = 'Failed to get config';
			do_action('pkmgmt_admin_notices');
		} else {
			do_action('pkmgmt_admin_notices');
			self::config_form($pm);
		}
		echo '</div>';
	}

	public static function notices_message($page): void
	{
		if ($page != 'pkmgmt') {
			return;
		}
		if (empty($_REQUEST['message']))
			return;
		$message = $_REQUEST['message'];
		if ('saved' == $message)
			$message = esc_html(__('Site saved.', 'parking-management'));
		if (empty($message))
			return;

		echo sprintf('<div id="message" class="updated"><p>%s</p></div>', esc_html($message));

	}

	private static function config_form(ParkingManagement $pm): void
	{
		global $plugin_page;

		echo '<form action="" method="post">';
		self::config_form_hidden($plugin_page, $pm->id);

		echo '</form>';
	}

	private static function config_form_hidden(string $page, int $id): void
	{
		echo sprintf('
		<input type="hidden" name="page" value="%s" />
		<input type="hidden" id="post_ID" name="post_ID" value="%d" />
		<input type="hidden" id="hidden-action" name="action" value="save" />
		<input type="hidden" id="pkmgmt-locale" name="pkmgmt-locale" value="fr_FR">
		',
			$page,
			$id
		);
	}

	private static function config_form_header(ParkingManagement $pm): void {
		if (current_user_can('pkmgmt_edit', $pm->id)) {
			$disabled = '';
			wp_nonce_field('pkmgmt-save_' . $pm->id);
		} else
			$disabled = ' disabled="disabled"';
		$holder = array(
			'title_placeholder' => esc_html(__("Title", 'parking-management')),
			'title' => esc_attr( $pm->title ),
			'name_placeholder' => esc_html(__("Name", 'parking-management')),
			'name' => esc_attr( $pm->name )
		);
		echo '<div id="titlediv">';
		echo sprintf('<input type="text" class="wide" id="pkmgmt-title" placeholder="%s" name="pkmgmt-title" size="80" value="%s" %s />', $holder['title_placeholder'],$holder['title'],$disabled );
		echo '</div>';
		$header = <<< EOD
<div id="titlediv">
		<input type="text" class="wide" id="pkmgmt-title" placeholder="{$holder['title_placeholder']}" name="pkmgmt-title" size="80" value="{$holder['title']}" {$disabled} />

		<p class="tagcode">
			{$holder['name_placeholder']}<br />
			<input type="text" class="wide" id="pkmgmt-name" name="pkmgmt-name" size="80" value="{$holder['name']}"<?php echo $disabled; ?> />
		</p>
		<?php if ( ! {$pm->initial()} ) : ?>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy and paste this code into your home page to include pre booking form.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-home-form-anchor-text" value='[parking-management-home-form id="<?php echo {$pm->id} ; ?>" title="<?php echo esc_attr( {$pm->name}  ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-anchor-text" onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content to add payplug code.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-payplug-anchor-text" value='[parking-management-payplug  id="<?php echo {$pm->id} ; ?>" title="<?php echo esc_attr( {$pm->name} ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content to add paypal code.", 'parking-management' ) ); ?><br />

			<input type="text" id="pkmgmt-paypal-anchor-text" value='[parking-management-paypal  id="<?php echo {$pm->id}; ?>" title="<?php echo esc_attr( {$pm->name} ); ?>"]' onfocus="this.select();" readonly class="wide wp-ui-text-highlight code" />
		</p>
		<?php endif; ?>
	<?php if ( current_user_can( 'pkmgmt_admin_cap', {$pm->id} ) ) : ?>
		<div class="save-pkmgmt">
			<input type="submit" class="button-primary" name="pkmgmt-save" value="<?php echo esc_attr( __( 'Save', 'parking-management' ) ); ?>" />
		</div>
	<?php endif; ?>


	</div>
EOD;

	}

	private static function ()
	{

	}

}
