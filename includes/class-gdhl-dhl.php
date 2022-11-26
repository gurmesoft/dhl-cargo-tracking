<?php

class GDHL_Dhl {

	public function get_status_by_tracking_id( $tracking_id ) {

		$result = array(
			'status'  => false,
			'message' => '',
		);

		$args = array(
			'headers' => array(
				'DHL-API-Key' => 'p4MCNbE3GtcGiXgoDNhl4mQi8tFAxn5n',
			),
		);

		$response = wp_remote_get( "https://api-eu.dhl.com/track/shipments?trackingNumber={$tracking_id}", $args );

		if ( is_wp_error( $response ) ) {
			$result['message'] = $response->get_error_message();
		} else {

			$response   = json_decode( $response['body'] );
			$json_error = json_last_error();

			if ( $json_error ) {

				$result['message'] = $json_error;
			} else {

				if ( property_exists( $response, 'shipments' ) && count( $response->shipments ) > 0 ) {
					$result = array(
						'status'  => true,
						'message' => $response->shipments[0],
					);
				} else {
					$result = array(
						'status'  => false,
						'message' => $response,
					);
				}
			}
		}

		return $result;
	}
}
