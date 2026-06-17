<?php

declare(strict_types=1);

namespace BitPayLib;

class BitPayWebhookVerifier {
	public function verify( string $signing_key, string $sig_header, string $webhook_body ): bool {
		// phpcs:ignore
        $hmac = base64_encode(
			hash_hmac(
				'sha256',
				$webhook_body,
				$signing_key,
				true
			)
		);

		return $sig_header === $hmac;
	}
}
