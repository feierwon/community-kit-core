<?php
/**
 * Plugin updater.
 *
 * Placeholder for self-hosted update checks.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Updater
 *
 * Stub implementation for plugin update management.
 */
class CK_Updater {

	/**
	 * Check for available updates.
	 *
	 * @return array{version: string, url: string}|null Update info or null if up to date.
	 */
	public function check_for_updates(): ?array {
		// Stub: no update server configured yet.
		return null;
	}
}
