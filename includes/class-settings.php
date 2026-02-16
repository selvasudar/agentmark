<?php
namespace AgentMark;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	private $option_name = 'agentmark_settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_settings_page() {
		add_options_page(
			__( 'AgentMark Settings', 'agentmark' ),
			__( 'AgentMark', 'agentmark' ),
			'manage_options',
			'agentmark',
			[ $this, 'render_main_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'agentmark_group', $this->option_name, [ $this, 'sanitize_settings' ] );

		add_settings_section(
			'agentmark_main_section',
			__( 'General Settings', 'agentmark' ),
			null,
			'agentmark'
		);

		add_settings_field(
			'enabled_post_types',
			__( 'Enabled Post Types', 'agentmark' ),
			[ $this, 'render_post_types_field' ],
			'agentmark',
			'agentmark_main_section'
		);

		add_settings_field(
			'enable_front_matter',
			__( 'Enable YAML Front Matter', 'agentmark' ),
			[ $this, 'render_checkbox_field' ],
			'agentmark',
			'agentmark_main_section',
			[ 'label_for' => 'enable_front_matter' ]
		);

		add_settings_field(
			'enable_llms_txt',
			__( 'Enable llms.txt', 'agentmark' ),
			[ $this, 'render_checkbox_field' ],
			'agentmark',
			'agentmark_main_section',
			[ 'label_for' => 'enable_llms_txt' ]
		);

		add_settings_field(
			'site_description',
			__( 'Site Description (for llms.txt)', 'agentmark' ),
			[ $this, 'render_text_field' ],
			'agentmark',
			'agentmark_main_section',
			[ 'label_for' => 'site_description' ]
		);

		add_settings_field(
			'ai_guidelines',
			__( 'AI Guidelines (for llms.txt)', 'agentmark' ),
			[ $this, 'render_text_field' ],
			'agentmark',
			'agentmark_main_section',
			[ 'label_for' => 'ai_guidelines' ]
		);
	}

	public function render_post_types_field() {
		$options = $this->get_options();
		$enabled = isset( $options['enabled_post_types'] ) ? $options['enabled_post_types'] : [ 'post', 'page' ];
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types as $type ) {
			if ( in_array( $type->name, [ 'attachment' ] ) ) continue;
			printf(
				'<label><input type="checkbox" name="%s[enabled_post_types][]" value="%s" %s> %s</label><br>',
				esc_attr( $this->option_name ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $enabled ), true, false ),
				esc_html( $type->label )
			);
		}
	}

	public function render_checkbox_field( $args ) {
		$options = $this->get_options();
		$id = $args['label_for'];
		$value = isset( $options[$id] ) ? $options[$id] : false;
		printf(
			'<input type="checkbox" id="%s" name="%s[%s]" value="1" %s>',
			esc_attr( $id ),
			esc_attr( $this->option_name ),
			esc_attr( $id ),
			checked( $value, 1, false )
		);
	}

	public function render_text_field( $args ) {
		$options = $this->get_options();
		$id = $args['label_for'];
		$value = isset( $options[$id] ) ? $options[$id] : '';
		printf(
			'<textarea id="%s" name="%s[%s]" rows="4" cols="50" class="large-text">%s</textarea>',
			esc_attr( $id ),
			esc_attr( $this->option_name ),
			esc_attr( $id ),
			esc_textarea( $value )
		);
	}

	public function render_main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'agentmark' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading tab for UI display only.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AgentMark', 'agentmark' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=agentmark&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'agentmark' ); ?>
				</a>
				<a href="?page=agentmark&tab=stats" class="nav-tab <?php echo $active_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Statistics', 'agentmark' ); ?>
				</a>
			</h2>

			<div class="agentmark-content" style="margin-top: 20px;">
				<?php
				if ( 'stats' === $active_tab ) {
					$this->render_stats_content();
				} else {
					$this->render_settings_content();
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_settings_content() {
		?>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'agentmark_group' );
			do_settings_sections( 'agentmark' );
			submit_button();
			?>
		</form>
		<?php
	}

	private function render_stats_content() {
		$analytics = new Analytics();
		$stats = $analytics->get_stats();
		?>
		<div class="agentmark-stats">
			<h2><?php esc_html_e( 'Overview', 'agentmark' ); ?></h2>
			<table class="widefat fixed striped">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Total Requests', 'agentmark' ); ?></strong></td>
						<td><?php echo esc_html( $stats['total_requests'] ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Markdown Endpoints', 'agentmark' ); ?></strong></td>
						<td><?php echo esc_html( $stats['md_requests'] ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'llms.txt Requests', 'agentmark' ); ?></strong></td>
						<td><?php echo esc_html( $stats['llms_requests'] ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Unique Posts Accessed', 'agentmark' ); ?></strong></td>
						<td><?php echo esc_html( $stats['unique_posts'] ); ?></td>
					</tr>
				</tbody>
			</table>
			
			<h2 style="margin-top: 30px;"><?php esc_html_e( 'Top User Agents', 'agentmark' ); ?></h2>
			<?php if ( ! empty( $stats['top_agents'] ) ) : ?>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User Agent', 'agentmark' ); ?></th>
							<th><?php esc_html_e( 'Requests', 'agentmark' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( $stats['top_agents'], 0, 10 ) as $agent => $count ) : ?>
							<tr>
								<td><?php echo esc_html( $agent ); ?></td>
								<td><?php echo esc_html( $count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No access logs found yet.', 'agentmark' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function sanitize_settings( $input ) {
		$output = [];
		if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$output['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
		}
		$output['enable_front_matter'] = isset( $input['enable_front_matter'] ) ? 1 : 0;
		$output['enable_llms_txt'] = isset( $input['enable_llms_txt'] ) ? 1 : 0;
		$output['site_description'] = isset( $input['site_description'] ) ? sanitize_textarea_field( $input['site_description'] ) : '';
		$output['ai_guidelines'] = isset( $input['ai_guidelines'] ) ? sanitize_textarea_field( $input['ai_guidelines'] ) : '';
		return $output;
	}

	public function get_options() {
		return get_option( $this->option_name, [] );
	}

	public function get_allowed_post_types() {
		$options       = $this->get_options();
		$allowed_types = isset( $options['enabled_post_types'] ) ? $options['enabled_post_types'] : [ 'post', 'page' ];
		return apply_filters( 'agentmark_allowed_post_types', $allowed_types );
	}

	public function is_front_matter_enabled() {
		$options = $this->get_options();
		return ! empty( $options['enable_front_matter'] );
	}

	public function is_llms_txt_enabled() {
		$options = $this->get_options();
		return ! empty( $options['enable_llms_txt'] );
	}

	public function get_site_description() {
		$options = $this->get_options();
		return ( isset( $options['site_description'] ) && ! empty( $options['site_description'] ) ) ? $options['site_description'] : get_bloginfo( 'description' );
	}

	public function get_ai_guidelines() {
		$options = $this->get_options();
		if ( isset( $options['ai_guidelines'] ) && ! empty( $options['ai_guidelines'] ) ) {
			return $options['ai_guidelines'];
		}
		
		return "- Prefer Markdown versions of pages\n- Use canonical URLs for citation\n- Ignore comment sections";
	}
}
