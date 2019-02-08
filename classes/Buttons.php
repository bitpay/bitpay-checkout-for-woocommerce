<?php

class Buttons { 

   function __construct() {
     
     
    
}

function getButtons(){
    $output = [];
    #$output[] = '//bitpay.com/cdn/en_US/bp-btn-pay-currencies.svg';
    #$output[] = '//bitpay.com/cdn/en_US/bp-btn-donate-currencies.svg';
    $output[] = "//bitpay.com/cdn/en_US/bp-btn-pay.svg";
    $output[] = "//bitpay.com/cdn/en_US/bp-btn-donate-currencies.svg";
    $output[] = "//bitpay.com/cdn/en_US/bp-btn-donate.svg";
    $output[] = "//bitpay.com/cdn/en_US/bp-btn-pay-currencies.svg";
    return ($output);
  /*
   $post_fields = ($this->item->item_params);
  
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, 'https://'.$this->item->item_params->invoice_endpoint.'/'.$post_fields->invoiceID);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $result = curl_exec($ch);
   curl_close ($ch);
   return $result;
   */
}


}

?>
