<?php

/**
 * Copyright (c) 2019 BitPay
 **/
declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Exceptions;

use Exception;
/**
 * Ledger query exception.
 *
 * @package BitPaySDK\Exceptions
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class LedgerQueryException extends LedgerException
{
    private string $bitPayMessage = "Failed to retrieve ledger";
    private string $bitPayCode = "BITPAY-LEDGER-GET";
    /**
     * Construct the LedgerQueryException.
     *
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code to throw.
     * @param string|null $apiCode [optional] The API Exception code to throw.
     */
    public function __construct($message = "", $code = 132, Exception $previous = null, ?string $apiCode = "000000")
    {
        $message = $this->bitPayCode . ": " . $this->bitPayMessage . "-> " . $message;
        $this->apiCode = $apiCode;
        parent::__construct($message, $code, $previous, $apiCode);
    }
}
