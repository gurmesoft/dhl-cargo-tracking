<?php

class GDHL_Schedule {

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_new_time_space' ), 100 );
		add_filter( 'gdhl_check_shipment_status_event', array( $this, 'process_dhl_query' ), 100 );
	}

	public function add_new_time_space( $times ) {
		$times['dhl_every_four_hours'] = array(
			'interval' => 60,
			'display'  => 'DHL 4 Saatte Bir Durum Sorgulama',
		);
		return $times;
	}

	public function process_dhl_query() {
		$args   = array(
			'status' => array( 'wc-transit', 'wc-completed' ),
		);
		$orders = wc_get_orders( $args );

		foreach ( $orders as $order ) {
			$order_id    = $order->get_id();
			$tracking_id = get_post_meta( $order_id, GDHL_TRACKING_ID, true );

			if ( ! $tracking_id ) {
				$tracking_id = gdhl_get_tracking_id_form_comments( $order_id );
			}

			if ( $tracking_id ) {

				$response = gdhl_dhl()->get_status_by_tracking_id( $tracking_id );

				if ( false === $response['status'] ) {
					$log_message = print_r( $response['message'], true );
					gdhl_logger( $order_id, "Durum sorgulama başarısız : {$log_message}" );
				} else {
					$log_message = print_r( $response['message']->status, true );
					gdhl_logger( $order_id, "Durum sorgulama başarılı : {$log_message}" );
					gdhl_update_order_status_by_dhl_response( $order_id, $response['message'] );
				}
			} else {
				gdhl_logger( $order_id, 'DHL takip no bulunamadı.' );
			}
			sleep( 2 );
		}
	}

}
