<?php

declare(strict_types=1);

namespace Unit\BitPayLib;

use BitPayLib\BitPayWebhookVerifier;
use PHPUnit\Framework\TestCase;

class TestBitPayWebhookVerifier extends TestCase {
    private function get_test_webhook_body(): string
    {
        $file = __DIR__ . '/json/bitpay_paid_ipn_webhook.json';
        if (!file_exists($file)) {
            $this->fail("Test webhook body file does not exist.");
        }
        return file_get_contents($file);
    }

    /**
     * @test
     */
    public function it_should_return_true_if_the_signature_matches()
    {
        $verifier = $this->getTestedClass();
        $signingKey = 'my_secret_key';

        // bitpay_paid_ipn_webhook request body signed with the signing key: my_secret_key
        $expected_signature_header = 'G3AN1yIRVFPahcmKXK0qg9UksH9WlK3Llvvs7APZOzc=';

        $this->assertTrue(
            $verifier->verify($signingKey, $expected_signature_header, $this->get_test_webhook_body())
        );
    }

    /**
     * @test
     */
    public function it_should_return_false_if_the_signature_doesnt_match()
    {
        $verifier = $this->getTestedClass();

        // key different from the one used to test sign
        $signingKey = 'different_key';

        // bitpay_paid_ipn_webhook request body signed with the signing key: my_secret_key
        $expected_signature_header = 'G3AN1yIRVFPahcmKXK0qg9UksH9WlK3Llvvs7APZOzc=';

        $this->assertFalse(
            $verifier->verify($signingKey, $expected_signature_header, $this->get_test_webhook_body())
        );
    }

    /**
     * @test
     */
    public function it_should_return_false_for_empty_signature()
    {
        $verifier = $this->getTestedClass();
        $signingKey = 'my_secret_key';

        $this->assertFalse(
            $verifier->verify($signingKey, '', $this->get_test_webhook_body())
        );
    }

    /**
     * @test
     */
    public function it_should_return_false_if_the_request_body_is_tampered_with()
    {
        $verifier = $this->getTestedClass();
        $signingKey = 'my_secret_key';

        $originalWebhookBody = $this->get_test_webhook_body();
//        $expected_signature_header = base64_encode(
//            hash_hmac('sha256', $originalWebhookBody, $signingKey, true)
//        );

        // bitpay_paid_ipn_webhook request body signed with the signing key: my_secret_key
        $expected_signature_header = 'G3AN1yIRVFPahcmKXK0qg9UksH9WlK3Llvvs7APZOzc=';

        // Tamper with the request body (e.g., change a character)
        $tamperedWebhookBody = str_replace('payment', 'tampered', $originalWebhookBody);

        $this->assertFalse(
            $verifier->verify($signingKey, $expected_signature_header, $tamperedWebhookBody),
            "Tampered webhook body should result in a failed verification"
        );
    }

    private function getTestedClass(): BitPayWebhookVerifier {
        return new BitPayWebhookVerifier();
    }
}
