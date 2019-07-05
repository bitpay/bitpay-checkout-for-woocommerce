<?php
class BPC_Token
{
    public function __construct($env, $token)
    {
        $this->api_env = $env;
        $this->api_token = $token;

    }
    public function BPC_checkToken()
    {
        $api_test = 'https://bitpay.com/invoices/1?token='.$this->api_token;
        if( $this->api_env == 'test'):
            $api_test = 'https://test.bitpay.com/invoices/1?token='.$this->api_token;
        endif;
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_test);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
      
    }
}
