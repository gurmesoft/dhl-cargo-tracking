<?php
/**
 * 1- DHL Statusleri Bul ('delivered','','','')
 * 2- WooCommerce Status Bul ('wc-completed','','')
 * 3- DHL - WC Status eşleştirilcek.
 * 4- Eşleştirilen statuse istinaden wooKargo ayarlarından sms şablonu getirilecek.
 * 5- Getirilen Şablon Twillio sms ile gönderilecek.
 */

class DHL_Tracking {
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_status_complete' ) );
		add_action( 'dhl_status_control', array( $this, 'order_status_complete' ) );
	}

	public function order_status_complete( $order_id ) {
		$order       = wc_get_order( $order_id );
		$tracking_id = $this->get_tracking_id( $order_id );

		if ( $tracking_id ) {

			$status = $this->get_status_from_dhl( $tracking_id );

			if ( $status ) {

				$old_status = get_post_meta( $order_id, 'dhl_status', true );

				if ( $status !== $old_status ) {

					update_post_meta( $order_id, 'dhl_status', $status->status );
					update_post_meta( $order_id, 'dhl_status_code', $status->statusCode );
					update_post_meta( $order_id, 'dhl_description', $status->description );
					$order->add_order_note( "DHL Message: {$status}" );

				}

				if ( $status->status !== 'delivered' ) {
					wp_schedule_single_event( time() + 60, 'dhl_status_control', array( $order_id ) );
				}
			}
		}
	}

	public function get_tracking_id( $order_id ) {
		global $wpdb;
		$tracking_id = false;
		$trackling   = $wpdb->get_var( "SELECT comment_content FROM {$wpdb->comments} WHERE comment_post_ID = {$order_id} AND comment_content LIKE '%tracking%'" );
		if ( $trackling ) {
			$trackling = explode( 'id=', $trackling );
			if ( ! empty( $trackling ) ) {
				$tracking_id = $trackling[1];
			}
		}

		return $tracking_id;

	}

	public function get_status_from_dhl( $tracking_id ) {
		$args     = array(
			'headers' => array(
				'DHL-API-Key' => 'AoYnm5SXlWMGnKG4JSFe69Zo3Y5P3WSP',
			),
		);
		$response = wp_remote_get( "https://api-eu.dhl.com/track/shipments?trackingNumber={$tracking_id}", $args );
		$response = json_decode( $response['body'] );

		return $response->shipments[0]->status;
	}

}
