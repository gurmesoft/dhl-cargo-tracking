<?php
class GDHL_WooCommerce {
	public function __construct() {
		add_filter( 'wc_order_statuses', array( $this, 'order_statuses' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_status_processing' ) );
		add_action( 'woocommerce_order_status_transit', array( $this, 'order_status_transit' ) );
		add_action( 'woocommerce_order_status_delivered', array( $this, 'order_status_delivered' ) );
	}

	public function order_statuses( $statuses ) {

		$new_statuses = array();
		foreach ( $statuses as $key => $value ) {
			$new_statuses[ $key ] = $value;
			if ( 'wc-processing' === $key ) {
				$new_statuses['wc-transit']   = 'In Transit';
				$new_statuses['wc-delivered'] = 'Delivered';
			}
		}
		return $new_statuses;

	}

	public function order_status_processing( $order_id ) {

		$order   = wc_get_order( $order_id );
		$message = "Dear {$order->get_formatted_billing_full_name()}
		\n\nMrGuild London would like to Thankyou for placing your order.
		\n\nYour order No: {$order_id}
		\nYour order total: {$order->get_total()}
		\n\nPlease keep an eye out for additional text messages which will inform you of your order status and when your order has been dispatched.
		\n\nDon’t miss out on our Sales and Promotions, check out our website for the latest arrivals at https://mrguild.com/ 
		\n\nBest Wishes 
		\n\nMrGuild London";

		$this->send_sms( $message, $order );
	}

	public function order_status_transit( $order_id ) {
		$order              = wc_get_order( $order_id );
		$estimated_delivery = (string) get_post_meta( $order_id, 'gdhl_estimated_delivery', true );
		$tracking_id        = get_post_meta( $order_id, GDHL_TRACKING_ID, true );
		$tracking_url       = "https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id={$tracking_id}";
		$message            = "Dear{$order->get_formatted_billing_full_name()}
		\n\nGREAT NEWS! Your order has been dispatched and is making it’s way to you.
		\n\nYour estimated delivery is. {$estimated_delivery} 
		\n\nIf you would like to make any changes to your delivery please click the link below: {$tracking_url}
		\n\nBest Wishes
		\n\nMrGuild London
		\n\nhttps://mrguild.com/";

		$this->send_sms( $message, $order );

		gdhl_logger( $order_id, 'Sipariş transit durumuna güncellendi.' );

	}

	public function order_status_delivered( $order_id ) {

		$order   = wc_get_order( $order_id );
		$message = "Dear {$order->get_formatted_billing_full_name()}
		\n\nYour parcel has been delivered!
		\n\nThankyou for shopping with us, we sincerely hope you are happy with your order and we hope you visit again soon!
		\n\nBest Wishes 
		\n\nMrGuild London
		\n\nhttps://mrguild.com/ ";

		gdhl_logger( $order_id, 'Sipariş delivered durumuna güncellendi.' );
		$this->send_sms( $message, $order );
	}

	protected function send_sms( $message, $order ) {
		$response = gdhl_twilio()->send_sms( $message, $order->get_billing_phone() );
		$order->add_order_note( $response['message'] );
	}


}
