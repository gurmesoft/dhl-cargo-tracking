<?php
/**
 * Bu bir deneme
 */



/**
 * DHL Tracking classı olayların işlendiği class burasıdır.
 * Burada dhl bilgileri alınıp statusu dhl statuse göre güncelleyip son durumu bilgilendirme işlemidir.
 *
 * @package dhl-tracking/includes
 * @version 1.0.0
 */

class DHL_Track
{
    
    /**
     * Bu sınıfın kurucusudur.
     */
    public function __construct()
    {
        add_action('woocommerce_order_status_completed', array( $this, 'dhl_takip_kodunu_olustur' ), 10, 1);
        add_action('woocommerce_order_status_changed', array( $this, 'durum_kontrol' ), 10, 3);
        add_action('dhl_status_control', array( $this, 'dhl_durum_degisikligi' ), 10, 1);
        add_action('dhl_takip_kodunu_olustur', array( $this, 'dhl_takip_kodunu_olustur' ), 10, 1);
    }
    /**
     *
     * Bu fonksiyon DHL takip kodu ile çekilen statusu update fonksiyonu
     *
     * @param string $order_id  müşteri numarası verir.
     */
    
    public function dhl_takip_kodunu_olustur($order_id)
    {
        
        $order           = new WC_Order($order_id);
      
        $dhl_tracking_id = $this->get_tracking_id($order_id);
        $status      = $this->get_status_from_dhl($dhl_tracking_id);
        if ('transit' !== $status->status->statusCode) {
            wp_schedule_single_event(time() + 60, 'dhl_takip_kodunu_olustur', array( $order_id ));
        }
 
        if ('transit' === $status->status->statusCode) {
            update_post_meta($order_id, '_dhl_tracking_code', $dhl_tracking_id);
            $order->update_status('transit', 'DHL tarafından kargo alındı, durum değiştirildi');
            error_log(print_r('DHL takip kodu oluştur fonksiyonuna içndeki if e girdi ----satır 46', true));
          
            wp_schedule_single_event(time() + 60, 'dhl_status_control', array( $order_id ));
        } else {
            $order->add_order_note('2 saat kontrol ardından kargo yola çıkmadı 2 saat sonra yazıdırılacak', 1);
        }
        error_log(print_r('DHL takip kodu oluştur fonksiyonuna girdi ----satır 52', true));
    }

    /**
     * DHL tarafından algılanan değişikliği göster ve bu duruma istinden order statusu güncelleyen fonksiyon
     *
     * @param string $order_id  müşteri numarası alır.
     */
    public function dhl_durum_degisikligi($order_id)
    {
        $order       = wc_get_order($order_id);
        $tracking_id = get_post_meta($order_id, '_dhl_tracking_code', true);
    
        $status      = $this->get_status_from_dhl($tracking_id);
        $order->add_order_note($order->get_status(), 'Status dhl durum içindeki ');
        error_log(print_r("dhl_durum_degisikligi fonksiyonun içnde {$status->status->statusCode} -- satır 67", true));

        if ('delivered' === $status->status->statusCode && 'transit' === $order->get_status()) {
            $order->update_status('delivered', 'Sipariş teslim edildi');
            error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki delivered durumu {$status->status->statusCode} -- satır 70 {$order_id}", true));
        } else {
			if ('transit' === $status->status->statusCode   && ('completed' === $order->get_status()  || 'on-hold'=== $order->get_status())) { // phpcs:ignore
                error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki transit durumu DHL Statusu = {$status->status->statusCode} --- Order woCommercedeki  Statusu = {$order->get_status()} ---- satır 73", true));

                $order->update_status('transit', 'DHL tarafından durum değişikliği oldu sipariş yolda');
            }
           
            wp_schedule_single_event(time() + 60, 'dhl_status_control', array( $order_id ));
        }
    }
    /**
     * Sipariş durumununa göre mesaj gönderen fonksiyon
     *
     *  @since 1.0.0
     *  @param string $order_id  müşteri numarası verir.
     *  @param string $old_status order status bi önceki durumu verir.
     *  @param string $new_status tıkladıgı andaki statüsü.
     */
    public function durum_kontrol($order_id, $old_status, $new_status)//phpcs:ignore
    {
        

        $dhl_send_sms           = new DHL_Twilio();
        $order                  = wc_get_order($order_id);
      
        $get_order_phone_number = get_post_meta($order_id, '_billing_phone', true);
        $price                  = get_post_meta($order_id, '_order_total', true);
        $tracking_id            = $this->get_tracking_id($order_id);
        $trackin_url            = $this->get_cargo_urli($order_id);
       
        $ad                     = $order->get_billing_first_name();// isim
        $soyad                  = $order->get_billing_last_name();//soyisim
        $status                 = $this->get_status_from_dhl($tracking_id);
        $kTarihi                = $status->estimatedTimeOfDelivery;
        error_log(print_r("durum_kontrol fonksiyonun içinde satır 105 Order ID = {$order_id}", true));
        $sendsms = array(
            
           
            "processing" =>"Dear {$ad} {$soyad}\n\nMrGuild London would like to Thankyou for placing your order.\n\n Your order No: {$order_id}\nYour order total: {$price}\n\n Please keep an eye out for additional text messages which will inform you of your order status and when your order has been dispatched.\n\n Don’t miss out on our Sales and Promotions, check out our website for the latest arrivals at https://mrguild.com/ \n\n Best Wishes \n \n              
                MrGuild London",
            "transit" => "Dear{$ad} {$soyad}\n\nGREAT NEWS! Your order has been dispatched and is making it’s way to you.\n\nYour estimated delivery is. {$kTarihi} \n\nIf you would like to make any changes to your delivery please click the link below: {$trackin_url}\n\nBest Wishes\n\n MrGuild London\n\n https://mrguild.com/",

            //"out-for-delivery" => "Dear {$ad} {$soyad}\n\nYour parcel is with a driver out for delivery and is expected to arrive by the end of the day!\n\n If you would like to make any changes to your delivery please click the link below:\n\n{$get_url}\n\nBest Wishes\n\nMrGuild London https://mrguild.com/ ",
            "delivered" => "Dear {$ad} {$soyad}\n\nYour parcel has been delivered!\n\nThankyou for shopping with us, we sincerely hope you are happy with your order and we hope you visit again soon!\n\nBest Wishes \n\nMrGuild London\n\n https://mrguild.com/ "
        );


        switch ($order->get_status()) {
            case 'processing':
                $order->status = $new_status;
                error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki processing durumu ORder ID = {$order_id} -----satır 122", true));

                $isget= $dhl_send_sms->smsGonder($sendsms["processing"], $get_order_phone_number, $order_id);
                if (!$isget['type']) {
                    $text = sprintf("{$get_order_phone_number} Numaralı telefona {$sendsms["processing"]} MESAJI GÖNDERİLEMEDİ !!!");
                    $order->add_order_note($text);
                    $order->add_order_note($isget['mesaj']);
                } else {
                    $order->add_order_note("{$get_order_phone_number} nolu numaraya mesaj başarılı bir şekilde gönderildi.");
                    error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki processing durumu else kontorlü  Order woCommercedeki bir önceki Statusu ={$old_status} Order ID= {$order_id}----satır 131 ", true));

                    $order->update_status('completed', 'Sipariş DHL Tarafından yola Çıktı ');
                    update_post_meta($order_id, '_send_message_status', $order->status);
                }
                break;
            case 'transit':
                error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki transit durumu  Order woCommercedeki  bir önceki Statusu ={$old_status} --- Order ID = {$order_id} ----satır 138", true));

                if ('completed' === $old_status || 'on-hold'===$old_status) {
                    $isget= $dhl_send_sms->smsGonder($sendsms["transit"], $get_order_phone_number, $order_id);
                    error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki transit durumu  Order woCommercedeki  bir önceki Statusu ={$old_status} --- Order ID = {$order_id} ----satır 142", true));

                    if (!$isget['type']) {
                        $text = sprintf("{$get_order_phone_number} Numaralı telefona {$sendsms["transit"]} MESAJI GÖNDERİLEMEDİ !!!");
                        $order->add_order_note($text);
                    } else {
                        $order->add_order_note("{$get_order_phone_number} nolu numaraya mesaj başarılı bir şekilde gönderildi.");
                    }
                }
                break;
            case 'delivered':
                if ('transit'=== $old_status) {
                    error_log(print_r("dhl_durum_degisikligi fonksiyonun içndeki delivered durumu   Order woCommercedeki  Statusu = {$old_status} --- Order = ID{$order_id} ----satır 154 ", true));

                    $isget= $dhl_send_sms->smsGonder($sendsms["delivered"], $get_order_phone_number, $order_id);
                    if (!$isget['type']) {
                        $text = sprintf("{$get_order_phone_number} Numaralı telefona {$sendsms["delivered"]} MESAJI GÖNDERİLEMEDİ !!!");
                        $order->add_order_note($text);
                    } else {
                        $order->add_order_note("{$get_order_phone_number} nolu numaraya mesaj başarılı bir şekilde gönderildi.");
                    }
                }
                break;
        }
    }
    /**
     * Bu fonksiyon DHL'den api bağlantısı saglanarak link çekiliyor.
     *
     * @param string $order_id takip numarasını alır.
     * @return string  veri döner.
     */
    function get_cargo_urli($order_id)
    {
        global $wpdb;
        $get_url = false;
        $urling  = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT comment_content FROM {$wpdb->prefix}comments WHERE comment_post_ID = %s AND comment_content LIKE %s",
                array(
                    $order_id,
                    '%tracking%',
                )
            )
        );
        if ($urling) {
            $urling = explode('is: ', $urling);
            if (! empty($urling)) {
                $get_url = $urling[2];
            }
        }
       
        return $get_url;
    }
    /**
     * Bu fonksiyon DHL'den api bağlantısı saglanarak tracking numbera göre durumu çekiliyor.
     *
     * @param string $tracking_id takip numarasını alır.
     * @return JSON $response bize json olarak veri döner.
     */
    public function get_status_from_dhl($tracking_id)
    {
        
        $args     = array(
            'headers' => array(
                'DHL-API-Key' => 'p4MCNbE3GtcGiXgoDNhl4mQi8tFAxn5n',
            ),
        );
        $response = wp_remote_get("https://api-eu.dhl.com/track/shipments?trackingNumber={$tracking_id}", $args);
        
        $response = json_decode($response['body']);
        error_log(print_r("get_status_from_dhl fonksiyonun içnde  --- 212 ", true));

     
        return $response->shipments[0];//
    }
    /**
     * Get tracking id Bu fonksiyon DHL tarafından barkok basıldıgından commnet_content'den statusu çekiyor.
     *
     *  @param string $order_id  müşteri numarası alır.
     * @return string $tracking_id
     */
    public function get_tracking_id($order_id)
    {
        global $wpdb;
        $tracking_id = false;
        $tracking    = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT comment_content FROM {$wpdb->prefix}comments WHERE comment_post_ID = %s AND comment_content LIKE %s",
                array(
                    $order_id,
                    '%tracking%',
                )
            )
        );
        if ($tracking) {
            $trackling = explode('id=', $tracking);
            if (! empty($trackling)) {
                $tracking_id = $trackling[1];
            }
        }
        error_log(print_r("get_tracking_id fonksiyonun içnde Takip no = {$tracking_id} --- ----satır 242 ", true));

        return $tracking_id;
    }
}
