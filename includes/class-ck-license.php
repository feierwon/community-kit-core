<?php
/**
 * License manager.
 *
 * Placeholder for license validation and management.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_License
 *
 * Stub implementation for license key management.
 */
class CK_License {

	/**
	 * Validate a license key.
	 *
	 * @param string $key The license key to validate.
	 * @return bool True if the key is valid.
	 */
	public function validate( string $key ): bool {
		// Stub: always returns false until a licensing API is integrated.
		return false;
	}

	/**
	 * Get the current license status.
	 *
	 * @return string One of: valid, invalid, expired, inactive.
	 */
	public function get_status(): string {
		return 'inactive';
	}
}
