<?php

/**
 * Copyright (c) 2019 BitPay
 **/
declare (strict_types=1);
namespace BitPayVendor\BitPaySDK;

use BitPayVendor\BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPayVendor\BitpaySDK\Exceptions\BitPayException;
use BitPayVendor\BitPaySDK\Exceptions\CurrencyQueryException;
use BitPayVendor\BitPaySDK\Model\Currency;
use BitPayVendor\BitPaySDK\Util\RESTcli\RESTcli;
use Exception;
use BitPayVendor\JsonMapper;
/**
 * Client for handling POS facade specific calls.
 *
 * @package BitPaySDK
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class PosClient extends Client
{
    protected string $env;
    protected Tokens $token;
    protected RESTcli $RESTcli;
    /**
     * Constructor for the BitPay SDK to use with the POS facade.
     *
     * @param $token       string The token generated on the BitPay account.
     * @param string|null $environment string The target environment [Default: Production].
     *
     * @throws BitPayException BitPayException class
     */
    public function __construct(string $token, string $environment = null)
    {
        try {
            $this->token = new Tokens(null, null, $token);
            $this->env = \strtolower($environment) === "test" ? Env::TEST : Env::PROD;
            $this->init();
            parent::__construct($this->RESTcli, new Tokens(null, null, $token));
        } catch (Exception $e) {
            throw new BitPayException("failed to initialize BitPay Light Client (Config) : " . $e->getMessage());
        }
    }
    /**
     * Initialize this object with the selected environment.
     *
     * @throws BitPayException BitPayException class
     */
    private function init() : void
    {
        try {
            $this->RESTcli = new RESTcli($this->env, new PrivateKey());
        } catch (Exception $e) {
            throw new BitPayException("failed to build configuration : " . $e->getMessage());
        }
    }
    /**
     * Fetch the supported currencies.
     *
     * @return array     A list of BitPay Invoice objects.
     * @throws CurrencyQueryException CurrencyQueryException class
     * @throws \BitPaySDK\Exceptions\BitPayException BitPayException class
     */
    public function getCurrencies() : array
    {
        try {
            $responseJson = $this->RESTcli->get("currencies", null, \false);
        } catch (BitPayException $e) {
            throw new CurrencyQueryException("failed to serialize Currency object : " . $e->getMessage(), null, null, $e->getApiCode());
        } catch (Exception $e) {
            throw new CurrencyQueryException("failed to serialize Currency object : " . $e->getMessage());
        }
        try {
            $mapper = new JsonMapper();
            $mapper->bEnforceMapType = \false;
            $currencies = $mapper->mapArray(\json_decode($responseJson, \true, 512, \JSON_THROW_ON_ERROR), [], Currency::class);
        } catch (Exception $e) {
            throw new CurrencyQueryException("failed to deserialize BitPay server response (Currency) : " . $e->getMessage());
        }
        return $currencies;
    }
}
