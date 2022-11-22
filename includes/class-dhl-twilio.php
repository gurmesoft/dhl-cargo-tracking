<?php


use Twilio\Rest\Client;

/**
 * Mesaj gönderme Classı twlio
 *
 */


class DHL_Twilio
{

    public function smsGonder($mesaj, $telefon)
    {
        $client = new Client('ACd7a856e84694aa10035c930c21959a28', '08f4651f9e32eaa55b789c56dd9578ef');//AC92baa5d4305b5eb8307f284f69ab2681----29f3dbef6511d27a1d0494ddce41c464

        try {
            $client->messages->create(
                $telefon,
                [
                    'messagingServiceSid' => 'MGa79e26e226a2071881d16ffc2ef13aef',
                    'body' => $mesaj,
                ]
            );
            return array(
                'type' => true,
                'mesaj' => "SMS sending successfully."
            );
        } catch (\Exception $e) {
            return array(
                'type' => false,
                'mesaj' => $e->getMessage()
            );
        }
    }
}
new DHL_Twilio;
