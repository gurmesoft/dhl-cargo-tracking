<?php
function gdhl_dhl() {
	require_once 'class-gdhl-dhl.php';
	return new GDHL_Dhl();
}
function gdhl_twilio() {
	require_once 'class-gdhl-twilio.php';
	return new GDHL_Twilio();
}

function gdhl_get_tracking_id_form_comments( $order_id, $retry_count = 4 ) {
	global $wpdb;
	$tracking_id = false;
	$tracking    = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT comment_content FROM {$wpdb->prefix}comments WHERE comment_post_ID = %s AND comment_content LIKE %s",
			array(
				$order_id,
				'This order has been shipped with: DHL%',
			)
		)
	);

	if ( $tracking ) {
		$trackling = explode( 'id=', $tracking );
		if ( ! empty( $trackling ) ) {
			$tracking_id = $trackling[1];
		}
		gdhl_logger( $order_id, "Tracking id tespit edildi. Id : {$tracking_id}. Takip başlatıldı" );
		update_post_meta( $order_id, GDHL_TRACKING_ID, $tracking_id );
		wp_schedule_single_event( GDHL_SCHEDULE_DELAY_TIME, 'gdhl_check_shipment_status_event', array( $order_id ) );

	}

	if ( 0 === $retry_count && false === $tracking_id ) {
		gdhl_logger( $order_id, 'Son kez denendi tracking id bulunamadı.' );
	}

	if ( false === $tracking_id && $retry_count > 0 ) {
		gdhl_logger( $order_id, "Tracking id bulunamadı. {$retry_count} kez daha denenecek." );
		$retry_count--;
		wp_schedule_single_event(
			GDHL_SCHEDULE_DELAY_TIME,
			'gdhl_get_tracking_id_form_comments_event',
			array(
				$order_id,
				$retry_count,
			)
		);
	}
}

function gdhl_update_order_status_by_dhl_response( $order_id, $dhl_response ) {
	$dhl_status = false;
	$order      = wc_get_order( $order_id );

	try {
		if ( property_exists( $dhl_response, 'status' ) ) {

			$dhl_status = $dhl_response->status;

			if ( property_exists( $dhl_status, 'statusCode' ) ) {
				$last_status = $dhl_status->statusCode; // phpcs:ignore
				gdhl_logger( $order_id, "DHL status tespit edildi : {$last_status}" );

				switch ( $last_status ) {
					case 'transit':
						update_post_meta( $order_id, 'gdhl_estimated_delivery', $dhl_status->estimatedTimeOfDelivery ); // phpcs:ignore
						$order->update_status( 'transit' );
						break;
					case 'delivered':
						$order->update_status( 'delivered' );
						break;
				}
			} else {
				gdhl_logger( $order_id, 'DHL status bulunamadı sorgulama devam ediyor.' );
			}
		} else {
			$message = print_r( $dhl_response, true );
			gdhl_logger( $order_id, "DHL Status tespit edilemedi. Obje : {$message}" );
		}
	} catch ( Exception  $e ) {
		gdhl_logger( $order_id, $e->getMessage() );
	}

	if ( $order->get_status() !== 'delivered' ) {
		gdhl_logger( $order_id, 'Tekrar sıraya eklendi.' );
		wp_schedule_single_event( GDHL_SCHEDULE_DELAY_TIME, 'gdhl_check_shipment_status_event', array( $order_id ) );
	}
}

function gdhl_logger( $order_id, $message ) {
	if ( class_exists( 'WC_Logger' ) ) {
		$logger = wc_get_logger();
		$logger->add( "{$order_id}-gurmehub-dhl-tracking", $message );
	}
}
