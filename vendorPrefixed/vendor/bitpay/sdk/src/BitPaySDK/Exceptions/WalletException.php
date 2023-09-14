<?php

/**
 * Copyright (c) 2019 BitPay
 **/
declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Exceptions;

/**
 * Generic wallet exception.
 *
 * @package BitPaySDK\Exceptions
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class WalletException extends BitPayException
{
    private string $bitPayMessage = "An unexpected error occurred while trying to manage the wallet";
    private string $bitPayCode = "BITPAY-WALLET-GENERIC";
    /**
     * Construct the WalletException.
     *
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code to throw.
     * @param string|null $apiCode [optional] The API Exception code to throw.
     */
    public function __construct($message = "", $code = 181, Exception $previous = null, ?string $apiCode = "000000")
    {
        if (!$message) {
            $message = $this->bitPayCode . ": " . $this->bitPayMessage . "-> " . $message;
        }
        parent::__construct($message, $code, $previous, $apiCode);
    }
}
