<?php

use Twilio\Rest\Client;

class GDHL_Twilio {
	public function send_sms( $message, $phone ) {
		$response = array();
		//AC92baa5d4305b5eb8307f284f69ab2681----29f3dbef6511d27a1d0494ddce41c464
		$client = new Client( 'ACd7a856e84694aa10035c930c21959a28', '08f4651f9e32eaa55b789c56dd9578ef' );
		try {
			$client->messages->create(
				$phone,
				array(
					'messagingServiceSid' => 'MGa79e26e226a2071881d16ffc2ef13aef',
					'body'                => $message,
				)
			);
			$response = array(
				'status'  => true,
				'message' => 'Sms sending successfully. (DHL Tracking)',
			);
		} catch ( Exception $e ) {
			$response = array(
				'status'  => false,
				'message' => "Error while sendig sms : {$e->getMessage()} (DHL Tracking)",
			);
		}

		return $response;
	}
}
