<?php

class GDHL_Schedule {

	public function __construct() {
		add_action( 'gdhl_check_shipment_status_event', array( $this, 'check_shipment_status' ), 10, 2 );
		add_action( 'gdhl_get_tracking_id_form_comments_event', 'gdhl_get_tracking_id_form_comments', 10, 2 );
	}

	public function check_shipment_status( $order_id, $retry_count = 4 ) {
		gdhl_logger( $order_id, '################################################### ACTION START ###################################################' );
		$tracking_id = get_post_meta( $order_id, GDHL_TRACKING_ID, true );
		$response    = gdhl_dhl()->get_status_by_tracking_id( $tracking_id );

		if ( false === $response['status'] ) {
			$log_message = print_r( $response['message'], true );
			gdhl_logger( $order_id, "Durum sorgulama başarısız : {$log_message}" );

			if ( 0 === $retry_count ) {
				gdhl_logger( $order_id, 'Son kez denendi DHL son duruma erişilemedi.' );
			} else {
				gdhl_logger( $order_id, "DHL son duruma erişilemedi. {$retry_count} kez daha denenecek." );
				$retry_count--;
				wp_schedule_single_event(
					GDHL_SCHEDULE_DELAY_TIME,
					'gdhl_check_shipment_status_event',
					array(
						$order_id,
						$retry_count,
					)
				);
			}
		} else {
			$log_message = print_r( $response['message']->status, true );
			gdhl_logger( $order_id, "Durum sorgulama başarılı : {$log_message}" );
			gdhl_update_order_status_by_dhl_response( $order_id, $response['message'] );
		}

	}
}
