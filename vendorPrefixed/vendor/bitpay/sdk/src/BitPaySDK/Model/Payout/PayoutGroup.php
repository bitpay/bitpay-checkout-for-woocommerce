<?php

declare (strict_types=1);
namespace BitPayVendor\BitPaySDK\Model\Payout;

/**
 * @package BitPaySDK\Model\Payout
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class PayoutGroup
{
    /**
     * @var Payout[]
     */
    private array $payouts = [];
    /**
     * @var PayoutGroupFailed[]
     */
    private array $failed = [];
    /**
     * @return Payout[]
     */
    public function getPayouts() : array
    {
        return $this->payouts;
    }
    /**
     * @param Payout[] $payouts
     */
    public function setPayouts(array $payouts) : void
    {
        $this->payouts = $payouts;
    }
    /**
     * @return PayoutGroupFailed[]
     */
    public function getFailed() : array
    {
        return $this->failed;
    }
    /**
     * @param PayoutGroupFailed[] $failed
     */
    public function setFailed(array $failed) : void
    {
        $this->failed = $failed;
    }
}
