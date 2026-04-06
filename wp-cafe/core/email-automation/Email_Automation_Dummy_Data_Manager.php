<?php
namespace WpCafe\Email_Automation;

if ( ! defined( 'ABSPATH' ) ) exit;

use Exception;
use WpCafe\Contracts\Hookable_Service_Contract;
use Ens\Flow\Flow;

class Email_Automation_Dummy_Data_Manager implements Hookable_Service_Contract {

    /**
     * Static flag to ensure hook is registered only once
     *
     * @var bool
     */
    private static $hook_registered = false;

    /**
     * Register hooks for dummy data generation.
     *
     * @return void
     */
    public function register() {
        if ( self::$hook_registered ) {
            return;
        }

        self::$hook_registered = true;

        add_action( 'init', [ $this, 'generate_automation_flow_data' ], 999 );
    }

    /**
     * Generate dummy automation flows for email-automation
     *
     * @return array|null
     */
    public static function generate_automation_flow_data() {
        // Check if automation flows have already been added
        if ( wpc_get_option( 'automation_flow_added', false ) ) {
            return null;
        }

        $manager = new self();
        $result = $manager->create_automation_flows();

        // Mark as done to prevent duplicate flows
        if ( ! empty( $result['flow_ids'] ) && empty( $result['errors'] ) ) {
            wpc_update_option( 'automation_flow_added', true );
        }

        return $result;
    }

    /**
     * Create automation flows for email automation
     *
     * @return array Created flow IDs and errors
     */
    private function create_automation_flows() {
        $result = [
            'flow_ids' => [],
            'errors'   => [],
        ];

        $automation_flows = [
            $this->create_reservation_created_flow(),
            $this->create_reservation_created_admin_flow(),
            $this->create_reservation_cancelled_flow(),
            $this->create_reservation_reminder_flow(),
            $this->create_reservation_feedback_flow(),
        ];

        try {
            $identifier = 'wpc';
            foreach ( $automation_flows as $automation_flow ) {
                if ( $this->flow_exists_by_name( $automation_flow['name'], $identifier ) ) {
                    continue;
                }
                // Create flow directly using Flow class
                try {
                    $new_flow = new Flow( $identifier, 0 );
                    $new_flow->set_props( [
                        'name'        => $automation_flow['name'],
                        'trigger'     => $automation_flow['trigger'],
                        'flow_config' => $automation_flow['flow_config'],
                        'status'      => $automation_flow['status'],
                    ] );

                    $flow_id = $new_flow->save();

                    if ( $flow_id && ! is_wp_error( $flow_id ) ) {
                        $result['flow_ids'][] = $flow_id;
                    } else {
                        $result['errors'][] = 'Failed to create flow: ' . $automation_flow['name'];
                    }
                } catch ( Exception $flow_create_e ) {
                    $result['errors'][] = 'Exception creating flow: ' . $flow_create_e->getMessage();
                }
            }
        } catch ( Exception $e ) {
            $result['errors'][] = 'Failed to create automation flow: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if a flow with the given name already exists
     *
     * @param string $flow_name The name of the flow to check
     * @param string $identifier The identifier prefix for the post type (e.g., 'wpc')
     * @return bool True if flow exists, false otherwise
     */
    private function flow_exists_by_name( $flow_name, $identifier = 'wpc' ) {
        $post_type = $identifier . '-flow';

        // Get all flows and check titles manually since get_posts doesn't support exact title matching
        $existing_flows = get_posts( [
            'post_type'              => $post_type,
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ] );

        // Check if any of the existing flows has the exact same title
        foreach ( $existing_flows as $flow_id ) {
            $flow_title = get_the_title( $flow_id );
            if ( $flow_title === $flow_name ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create reservation created automation flow
     *
     * @return array Automation flow configuration
     */
    private function create_reservation_created_flow() {
        $body = '<p><strong>Hi {%reservation_name%},</strong></p>' .
'<p>Thank you for choosing us!</p>' .
'<p>Your reservation (<strong>ID:</strong> {%reservation_id%}) has been successfully created. Here are the details for your booking</p>' .
'<p><strong>Reservation Details</strong></p>' .
'<ul>' .
'<li><strong>Name:</strong> {%reservation_name%}</li>' .
'<li><strong>Email:</strong> {%reservation_email%}</li>' .
'<li><strong>Phone:</strong> {%reservation_phone%}</li>' .
'<li><strong>Date:</strong> {%reservation_date%}</li>' .
'<li><strong>Time:</strong> {%reservation_start_time%} – {%reservation_end_time%}</li>' .
'<li><strong>Guests:</strong> {%reservation_total_guests%}</li>' .
'<li><strong>Table:</strong> {%reservation_table_name%}</li>' .
'<li><strong>Branch:</strong> {%reservation_branch_name%} (ID: {%reservation_branch_id%})</li>' .
'<li><strong>Status:</strong> {%reservation_status%}</li>' .
'</ul>' .
'<h3><strong>Payment Information</strong></h3>' .
'<ul>' .
'<li><strong>Booking Amount:</strong> {%reservation_booking_amount%} {%reservation_currency%}</li>' .
'<li><strong>Total Price:</strong> {%reservation_total_price%} {%reservation_currency%}</li>' .
'<li><strong>Payment Method:</strong> {%reservation_payment_method%}</li>' .
'</ul>' .
'<h3><strong>Food Order</strong></h3>' .
'<p>{%reservation_food_order%}</p>' .
'<h3><strong>Invoice</strong></h3>' .
'<p>{%reservation_invoice%}</p>' .
'<p><strong>Notes from you:</strong></p>' .
'<p>{%reservation_notes%}</p>' .
'<p>If you need to modify or cancel your reservation, please contact us at this email or call our support line.</p>' .
'<p>We look forward to serving you soon!</p>' .
'<p>Warm regards,</p>' .
'<p><strong>The {%reservation_branch_name%} Team</strong></p>';

        return [
            'name'        => 'Reservation created notification',
            'trigger'     => 'reservation_created',
            'status'      => 'publish',
            'flow_config' => [
                'nodes' => [
                    [
                        'id'       => 'node_1',
                        'type'     => 'trigger',
                        'name'     => 'trigger',
                        'data'     => [
                            'label'        => 'trigger: reservation_created',
                            'subtitle'     => 'On "Reservation Created" event fires',
                            'triggerValue' => 'reservation_created',
                        ],
                        'position' => [
                            'x' => 300,
                            'y' => 100,
                        ],
                    ],
                    [
                        'id'       => 'end_1',
                        'type'     => 'end',
                        'name'     => 'end',
                        'data'     => [
                            'label'    => 'end_flow',
                            'subtitle' => 'Automation stops here',
                        ],
                        'position' => [
                            'x' => 317.231884057971,
                            'y' => 462.97584541062804,
                        ],
                    ],
                    [
                        'id'       => 'node_3',
                        'type'     => 'action',
                        'name'     => 'email',
                        'data'     => [
                            'actionType'   => 'send_email',
                            'label'        => 'send_email',
                            'subtitle'     => 'From: ' . get_option('admin_email'),
                            'operator'     => '=',
                            'value'        => '',
                            'receiverType' => 'customer_email',
                            'from'         => get_option('admin_email'),
                            'subject'      => 'Reservation completed',
                            'body'         => $body,
                        ],
                        'position' => [
                            'x' => 317.1714975845411,
                            'y' => 292.64734299516908,
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'id'        => 'edge_node_1-node_3',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_1',
                        'target'    => 'node_3',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_3-end_1',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_3',
                        'target'    => 'end_1',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create reservation created admin automation flow
     *
     * @return array Automation flow configuration
     */
    private function create_reservation_created_admin_flow() {
        $body = '<p><strong>New Reservation Alert</strong></p>' .
'<p>A new reservation has been created in your system.</p>' .
'<p><strong>Reservation Details</strong></p>' .
'<ul>' .
'<li><strong>Reservation ID:</strong> {%reservation_id%}</li>' .
'<li><strong>Customer Name:</strong> {%reservation_name%}</li>' .
'<li><strong>Email:</strong> {%reservation_email%}</li>' .
'<li><strong>Phone:</strong> {%reservation_phone%}</li>' .
'<li><strong>Date:</strong> {%reservation_date%}</li>' .
'<li><strong>Time:</strong> {%reservation_start_time%} - {%reservation_end_time%}</li>' .
'<li><strong>Guests:</strong> {%reservation_total_guests%}</li>' .
'<li><strong>Table:</strong> {%reservation_table_name%}</li>' .
'<li><strong>Branch:</strong> {%reservation_branch_name%}</li>' .
'<li><strong>Status:</strong> {%reservation_status%}</li>' .
'</ul>' .
'<p><strong>Pricing Information</strong></p>' .
'<ul>' .
'<li><strong>Booking Amount:</strong> {%reservation_booking_amount%} {%reservation_currency%}</li>' .
'<li><strong>Total Price:</strong> {%reservation_total_price%} {%reservation_currency%}</li>' .
'<li><strong>Payment Method:</strong> {%reservation_payment_method%}</li>' .
'</ul>' .
'<p><strong>Special Notes:</strong></p>' .
'<p>{%reservation_notes%}</p>';

        return [
            'name'        => 'Admin notification on new reservation',
            'trigger'     => 'reservation_created',
            'status'      => 'publish',
            'flow_config' => [
                'nodes' => [
                    [
                        'id'       => 'node_1',
                        'type'     => 'trigger',
                        'name'     => 'trigger',
                        'data'     => [
                            'label'        => 'trigger: reservation_created',
                            'subtitle'     => 'On "Reservation Created" event fires',
                            'triggerValue' => 'reservation_created',
                        ],
                        'position' => [
                            'x' => 300,
                            'y' => 100,
                        ],
                    ],
                    [
                        'id'       => 'end_1',
                        'type'     => 'end',
                        'name'     => 'end',
                        'data'     => [
                            'label'    => 'end_flow',
                            'subtitle' => 'Automation stops here',
                        ],
                        'position' => [
                            'x' => 317.231884057971,
                            'y' => 462.97584541062804,
                        ],
                    ],
                    [
                        'id'       => 'node_3',
                        'type'     => 'action',
                        'name'     => 'email',
                        'data'     => [
                            'actionType'   => 'send_email',
                            'label'        => 'send_email',
                            'subtitle'     => 'From: ' . get_option('admin_email'),
                            'operator'     => '=',
                            'value'        => '',
                            'receiverType' => 'admin_email',
                            'from'         => '',
                            'subject'      => 'New reservation created - {%reservation_id%}',
                            'body'         => $body,
                        ],
                        'position' => [
                            'x' => 317.1714975845411,
                            'y' => 292.64734299516908,
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'id'        => 'edge_node_1-node_3',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_1',
                        'target'    => 'node_3',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_3-end_1',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_3',
                        'target'    => 'end_1',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create reservation cancelled automation flow
     *
     * @return array Automation flow configuration
     */
    private function create_reservation_cancelled_flow() {
        $body = '<p><strong>Hi {%reservation_name%},</strong></p>' .
'<p>We are writing to confirm that your reservation has been cancelled.</p>' .
'<p><strong>Reservation Details</strong></p>' .
'<ul>' .
'<li><strong>Reservation ID:</strong> {%reservation_id%}</li>' .
'<li><strong>Date:</strong> {%reservation_date%}</li>' .
'<li><strong>Time:</strong> {%reservation_start_time%} - {%reservation_end_time%}</li>' .
'<li><strong>Table:</strong> {%reservation_table_name%}</li>' .
'<li><strong>Branch:</strong> {%reservation_branch_name%}</li>' .
'</ul>' .
'<p><strong>Pricing Information</strong></p>' .
'<ul>' .
'<li><strong>Booking Amount:</strong> {%reservation_booking_amount%} {%reservation_currency%}</li>' .
'<li><strong>Total Price:</strong> {%reservation_total_price%} {%reservation_currency%}</li>' .
'<li><strong>Payment Method:</strong> {%reservation_payment_method%}</li>' .
'</ul>' .
'<p><strong>Cancellation Notes:</strong></p>' .
'<p>{%reservation_notes%}</p>' .
'<p>If you have any questions about this cancellation, please feel free to contact us.</p>' .
'<p>Thank you,</p>' .
'<p><strong>The {%reservation_branch_name%} Team</strong></p>';

        return [
            'name'        => 'Reservation cancellation notification',
            'trigger'     => 'reservation_cancelled',
            'status'      => 'publish',
            'flow_config' => [
                'nodes' => [
                    [
                        'id'       => 'node_1',
                        'type'     => 'trigger',
                        'name'     => 'trigger',
                        'data'     => [
                            'label'        => 'trigger: reservation_cancelled',
                            'subtitle'     => 'On "Reservation Cancelled" event fires',
                            'triggerValue' => 'reservation_cancelled',
                        ],
                        'position' => [
                            'x' => 300,
                            'y' => 100,
                        ],
                    ],
                    [
                        'id'       => 'end_1',
                        'type'     => 'end',
                        'name'     => 'end',
                        'data'     => [
                            'label'    => 'end_flow',
                            'subtitle' => 'Automation stops here',
                        ],
                        'position' => [
                            'x' => 317.231884057971,
                            'y' => 462.97584541062804,
                        ],
                    ],
                    [
                        'id'       => 'node_3',
                        'type'     => 'action',
                        'name'     => 'email',
                        'data'     => [
                            'actionType'   => 'send_email',
                            'label'        => 'send_email',
                            'subtitle'     => 'From: ' . get_option('admin_email'),
                            'operator'     => '=',
                            'value'        => '',
                            'receiverType' => 'customer_email',
                            'from'         => get_option('admin_email'),
                            'subject'      => 'Your reservation has been cancelled',
                            'body'         => $body,
                        ],
                        'position' => [
                            'x' => 317.1714975845411,
                            'y' => 292.64734299516908,
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'id'        => 'edge_node_1-node_3',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_1',
                        'target'    => 'node_3',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_3-end_1',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_3',
                        'target'    => 'end_1',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create reservation reminder automation flow (1 hour before reservation)
     *
     * @return array Automation flow configuration
     */
    private function create_reservation_reminder_flow() {
        $body = '<p><strong>Hi {%reservation_name%},</strong></p>' .
'<p>This is a friendly reminder that your reservation is coming up in just 1 hour!</p>' .
'<p><strong>Reservation Details</strong></p>' .
'<ul>' .
'<li><strong>Date:</strong> {%reservation_date%}</li>' .
'<li><strong>Time:</strong> {%reservation_start_time%} – {%reservation_end_time%}</li>' .
'<li><strong>Guests:</strong> {%reservation_total_guests%}</li>' .
'<li><strong>Table:</strong> {%reservation_table_name%}</li>' .
'<li><strong>Branch:</strong> {%reservation_branch_name%}</li>' .
'</ul>' .
'<p>Please make sure to arrive on time. If you need to cancel or modify your reservation, please contact us as soon as possible at this email.</p>' .
'<p>We look forward to serving you!</p>' .
'<p>Warm regards,</p>' .
'<p><strong>The {%reservation_branch_name%} Team</strong></p>';

        return [
            'name'        => 'Reservation reminder - 1 hour before',
            'trigger'     => 'reservation_created',
            'status'      => 'publish',
            'flow_config' => [
                'nodes' => [
                    [
                        'id'       => 'node_1',
                        'type'     => 'trigger',
                        'name'     => 'trigger',
                        'data'     => [
                            'label'        => 'trigger: reservation_created',
                            'subtitle'     => 'On "Reservation Created" event fires',
                            'triggerValue' => 'reservation_created',
                        ],
                        'position' => [
                            'x' => 300,
                            'y' => 100,
                        ],
                    ],
                    [
                        'id'       => 'node_3',
                        'type'     => 'action',
                        'name'     => 'delay',
                        'data'     => [
                            'actionType'     => 'add_delay',
                            'label'          => 'add_delay',
                            'subtitle'       => 'Wait for 1 hours',
                            'operator'       => '=',
                            'value'          => '',
                            'delay'          => 1,
                            'delayUnit'      => 'hours',
                            'delayCondition' => 'before_reservation_date',
                        ],
                        'position' => [
                            'x' => 307.5,
                            'y' => 280,
                        ],
                    ],
                    [
                        'id'       => 'end_1',
                        'type'     => 'end',
                        'name'     => 'end',
                        'data'     => [
                            'label'    => 'end_flow',
                            'subtitle' => 'Automation stops here',
                        ],
                        'position' => [
                            'x' => 317.231884057971,
                            'y' => 462.97584541062804,
                        ],
                    ],
                    [
                        'id'       => 'node_4',
                        'type'     => 'action',
                        'name'     => 'email',
                        'data'     => [
                            'actionType'   => 'send_email',
                            'label'        => 'send_email',
                            'subtitle'     => 'From: ' . get_option('admin_email'),
                            'operator'     => '=',
                            'value'        => '',
                            'receiverType' => 'customer_email',
                            'from'         => get_option('admin_email'),
                            'subject'      => 'Reminder: Your reservation is coming up soon!',
                            'body'         => $body,
                        ],
                        'position' => [
                            'x' => 311.25,
                            'y' => 460,
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'id'        => 'edge_node_1-node_3',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_1',
                        'target'    => 'node_3',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_3-node_4',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_3',
                        'target'    => 'node_4',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_4-end_1',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_4',
                        'target'    => 'end_1',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create reservation feedback automation flow (1 day after reservation)
     *
     * @return array Automation flow configuration
     */
    private function create_reservation_feedback_flow() {
        $body = '<p><strong>Hi {%reservation_name%},</strong></p>' .
'<p>Thank you for choosing us for your recent reservation! We hope you had a wonderful experience at our restaurant.</p>' .
'<p><strong>Your Reservation Details</strong></p>' .
'<ul>' .
'<li><strong>Date:</strong> {%reservation_date%}</li>' .
'<li><strong>Time:</strong> {%reservation_start_time%} – {%reservation_end_time%}</li>' .
'<li><strong>Table:</strong> {%reservation_table_name%}</li>' .
'<li><strong>Branch:</strong> {%reservation_branch_name%}</li>' .
'</ul>' .
'<p>We would love to hear about your experience! Your feedback helps us improve our service and ensure we continue to provide excellent food and hospitality.</p>' .
'<p>Please let us know how we did and if there\'s anything we can improve. We truly value your opinion!</p>' .
'<p>Thank you for your business and we look forward to serving you again soon!</p>' .
'<p>Warm regards,</p>' .
'<p><strong>The {%reservation_branch_name%} Team</strong></p>';

        return [
            'name'        => 'Reservation feedback request - 1 day after',
            'trigger'     => 'reservation_created',
            'status'      => 'publish',
            'flow_config' => [
                'nodes' => [
                    [
                        'id'       => 'node_1',
                        'type'     => 'trigger',
                        'name'     => 'trigger',
                        'data'     => [
                            'label'        => 'trigger: reservation_created',
                            'subtitle'     => 'On "Reservation Created" event fires',
                            'triggerValue' => 'reservation_created',
                        ],
                        'position' => [
                            'x' => 300,
                            'y' => 100,
                        ],
                    ],
                    [
                        'id'       => 'node_3',
                        'type'     => 'action',
                        'name'     => 'delay',
                        'data'     => [
                            'actionType'     => 'add_delay',
                            'label'          => 'add_delay',
                            'subtitle'       => 'Wait for 1 days',
                            'operator'       => '=',
                            'value'          => '',
                            'delay'          => 1,
                            'delayUnit'      => 'days',
                            'delayCondition' => 'after_reservation_date',
                        ],
                        'position' => [
                            'x' => 307.5,
                            'y' => 280,
                        ],
                    ],
                    [
                        'id'       => 'end_1',
                        'type'     => 'end',
                        'name'     => 'end',
                        'data'     => [
                            'label'    => 'end_flow',
                            'subtitle' => 'Automation stops here',
                        ],
                        'position' => [
                            'x' => 315,
                            'y' => 640,
                        ],
                    ],
                    [
                        'id'       => 'node_4',
                        'type'     => 'action',
                        'name'     => 'email',
                        'data'     => [
                            'actionType'   => 'send_email',
                            'label'        => 'send_email',
                            'subtitle'     => 'From: ' . get_option('admin_email'),
                            'operator'     => '=',
                            'value'        => '',
                            'receiverType' => 'customer_email',
                            'from'         => get_option('admin_email'),
                            'subject'      => 'How was your experience with us?',
                            'body'         => $body,
                        ],
                        'position' => [
                            'x' => 311.25,
                            'y' => 460,
                        ],
                    ],
                ],
                'edges' => [
                    [
                        'id'        => 'edge_node_1-node_3',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_1',
                        'target'    => 'node_3',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_3-node_4',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_3',
                        'target'    => 'node_4',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                    [
                        'id'        => 'edge_node_4-end_1',
                        'type'      => 'smoothstep',
                        'markerEnd' => [
                            'type' => 'arrowclosed',
                        ],
                        'source'    => 'node_4',
                        'target'    => 'end_1',
                        'data'      => [
                            'animated' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

}
