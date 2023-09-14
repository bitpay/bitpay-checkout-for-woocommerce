<?php

/**
 * Copyright (c) 2019 BitPay
 **/
namespace BitPayVendor\BitPaySDK\Model\Invoice;

/**
 * The type Invoice status.
 *
 * @package BitPaySDK\Model\Invoice
 * @author BitPay Integrations <integrations@bitpay.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @see https://bitpay.readme.io/reference/invoices REST API Invoices
 */
interface InvoiceStatus
{
    /**
     * An invoice starts in this state. When in this state and only in this state, payments broadcasted by purchasers
     * be applied to the invoice (there is a 15 minute window for the purchaser to send a payment from
     * their crypto wallet). If an invoice has received a partial payment,
     * it will still reflect a status of new to the merchant. From a merchant system perspective,
     * an invoice is either paid or not paid, partial payments are automatically refunded by BitPay to the consumer.
     */
    public const NEW = "new";
    /**
     * As soon as payment is received it is evaluated against the invoice requested amount.
     * If the amount paid is equal to or greater than the amount expected then the invoice is marked as being paid.
     * To detect whether the invoice has been overpaid consult the invoice exception status (exceptionStatus parameter).
     * The overpaid amount on an invoice is automatically refunded by BitPay to the consumer.
     */
    public const PAID = "paid";
    /**
     * This status can be used by merchants in order to fulfill orders placed by the consumer.
     * Merchants can configure the timing at which BitPay sets this specific invoice status,
     * depending on the number of confirmation achieved by the consumer's transaction in the selected cryptocurrency.
     * This can be configured during invoice creation using the "transactionSpeed" parameter
     * (section Create an invoice), or at account level via a dashboard setting.
     */
    public const CONFIRMED = "confirmed";
    /**
     * When an invoice has the status complete, it means that BitPay has credited the merchant account,
     * in the currency indicated in the settlement settings. For instance, with invoices paid in Bitcoin (BTC),
     * 6 confirmation blocks on the bitcoin network are required for an invoice to be complete,
     * this takes on average 1 hour.
     */
    public const COMPLETE = "complete";
    /**
     * An invoice reaches the expired status if no payment was received and the 15 minute payment window has elapsed.
     */
    public const EXPIRED = "expired";
    /**
     * An invoice is considered invalid when it was paid,
     * but the corresponding cryptocurrency transaction was not confirmed within 1 hour on the corresponding blockchain.
     * It is possible that some transactions can take longer than 1 hour to be included in a block.
     * If the transaction confirms after 1 hour, BitPay will update the invoice state from "invalid" to "complete"
     * (6 confirmations for transactions on the bitcoin network for instance).
     */
    public const INVALID = "invalid";
}
