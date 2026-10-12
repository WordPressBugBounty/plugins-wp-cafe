<?php
namespace WpCafe\CustomerAccounts\Email;

if ( ! defined( 'ABSPATH' ) ) exit;

use Ens\Flow\Flow;
use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Seeds the published "Account created" email flow once.
 *
 * Its own guard, because the core seeder's flag is already set on live sites.
 * Never recreates a flow the admin deleted. Mirrors the Pro waitlist seeder.
 *
 * @package WpCafe/CustomerAccounts/Email
 */
class Account_Flow_Seeder implements Hookable_Service_Contract {
	const SEEDED_OPTION  = 'wpcafe_account_created_flow_seeded';
	const FLOW_POST_TYPE = 'wpc-flow';
	const FLOW_NAME      = 'Account created';

	/**
	 * @return void
	 */
	public function register() {
		// admin_init runs after every `init`, so the email SDK is set up.
		add_action( 'admin_init', [ $this, 'maybe_seed' ] );
	}

	/**
	 * @return void
	 */
	public function maybe_seed() {
		if ( get_option( self::SEEDED_OPTION ) || ! class_exists( Flow::class ) ) {
			return;
		}

		if ( ! $this->flow_exists() ) {
			try {
				$flow = new Flow( 'wpc', 0 );
				$flow->set_props(
					[
						'name'        => self::FLOW_NAME,
						'trigger'     => Account_Created_Trigger::VALUE,
						'flow_config' => $this->config(),
						'status'      => 'publish',
					]
				);
				$flow->save();
			} catch ( \Throwable $e ) {
				// The guard stays unset, so a later request retries.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[WPCafe] Could not create the "Account created" email flow: ' . $e->getMessage() );
				return;
			}
		}

		update_option( self::SEEDED_OPTION, true );
	}

	/**
	 * @return bool
	 */
	private function flow_exists() {
		$ids = get_posts(
			[
				'post_type'              => self::FLOW_POST_TYPE,
				'post_status'            => 'any',
				'title'                  => self::FLOW_NAME,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		return ! empty( $ids );
	}

	/**
	 * Trigger → email → end, in the node/edge shape the SDK and builder expect.
	 *
	 * @return array{nodes: array, edges: array}
	 */
	private function config() {
		$admin_email = get_option( 'admin_email' );

		// Markup stays out of the translatable strings.
		$body = '<p><strong>' .
			/* translators: %s: customer name. */
			sprintf( __( 'Hi %s,', 'wp-cafe' ), '{%account_name%}' ) . '</strong></p>' .
			'<p>' . __( 'Thanks for your booking. We made an account for you, so you can see your bookings in one place.', 'wp-cafe' ) . '</p>' .
			'<p><strong>' . __( 'Username:', 'wp-cafe' ) . '</strong> {%account_username%}</p>' .
			'<p><a href="{%account_set_password_url%}"><strong>' . __( 'Set your password', 'wp-cafe' ) . '</strong></a></p>' .
			'<p>' . __( 'If the link does not work, copy this address into your browser:', 'wp-cafe' ) . '<br>{%account_set_password_url%}</p>' .
			'<p>' . __( 'Already set it? Sign in here:', 'wp-cafe' ) . ' <a href="{%account_login_url%}">{%account_login_url%}</a></p>' .
			/* translators: %s: site name. */
			'<p>' . sprintf( __( 'The %s Team', 'wp-cafe' ), '{%site_name%}' ) . '</p>';

		return [
			'nodes' => [
				[
					'id'       => 'node_1',
					'type'     => 'trigger',
					'name'     => 'trigger',
					'data'     => [
						'label'        => 'trigger: ' . Account_Created_Trigger::VALUE,
						'subtitle'     => 'On "Account Created" event fires',
						'triggerValue' => Account_Created_Trigger::VALUE,
					],
					'position' => [ 'x' => 300, 'y' => 100 ],
				],
				[
					'id'       => 'node_3',
					'type'     => 'action',
					'name'     => 'email',
					'data'     => [
						'actionType'   => 'send_email',
						'label'        => 'send_email',
						'subtitle'     => 'From: ' . $admin_email,
						'operator'     => '=',
						'value'        => '',
						'receiverType' => 'customer_email',
						'from'         => $admin_email,
						/* translators: %s: site name. */
						'subject'      => sprintf( __( 'Your account at %s is ready', 'wp-cafe' ), '{%site_name%}' ),
						'body'         => $body,
					],
					'position' => [ 'x' => 317, 'y' => 292 ],
				],
				[
					'id'       => 'end_1',
					'type'     => 'end',
					'name'     => 'end',
					'data'     => [
						'label'    => 'end_flow',
						'subtitle' => 'Automation stops here',
					],
					'position' => [ 'x' => 317, 'y' => 463 ],
				],
			],
			'edges' => [
				[
					'id'        => 'edge_node_1-node_3',
					'type'      => 'smoothstep',
					'markerEnd' => [ 'type' => 'arrowclosed' ],
					'source'    => 'node_1',
					'target'    => 'node_3',
					'data'      => [ 'animated' => false ],
				],
				[
					'id'        => 'edge_node_3-end_1',
					'type'      => 'smoothstep',
					'markerEnd' => [ 'type' => 'arrowclosed' ],
					'source'    => 'node_3',
					'target'    => 'end_1',
					'data'      => [ 'animated' => false ],
				],
			],
		];
	}
}
