<?php
/*
* Plugin Name:       Opcean Framework
* Plugin URI:        https://github.com/zbandhan/opcean-framework
* Description:       Ocean Framework: Metabox and Options Page Made Easy and Smooth
* Version:           1.0.1
* Requires at least: 5.2
* Requires PHP:      7.4
* Author:            Giganteck
* Author URI:        https://giganteck.com/
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:       opcean-framework
* Domain Path:       /languages
*/
defined('ABSPATH') || exit;

require __DIR__ . '/vendor/autoload.php';

/** Import necessary classes */
use Giganteck\Opcean\Core\Setting;
use Giganteck\Opcean\Core\Metabox;
use Giganteck\Opcean\Core\TermMeta;
use Giganteck\Opcean\Contracts\SettingInterface;
use Giganteck\Opcean\Contracts\MetaboxInterface;
use Giganteck\Opcean\Contracts\TermMetaInterface;

/** Version constant for Opcean */
if( ! defined( 'OPCEAN_VERSION' ) ) {
    define( 'OPCEAN_VERSION', '2.1.1' );
}

/** Name constant for Opcean */
if( ! defined( 'OPCEAN_NAME' ) ) {
    define( 'OPCEAN_NAME', 'Opcean Framework' );
}

/** PATH constant for Opcean */
if( ! defined( 'OPCEAN_PATH' ) ) {
    define( 'OPCEAN_PATH', __FILE__ );
}

/** DIR constant for Opcean */
if( ! defined( 'OPCEAN_DIR' ) ) {
    define( 'OPCEAN_DIR', __DIR__ );
}

/** Base constant */
if( ! defined( 'OPCEAN_BASE' ) ) {
    define( 'OPCEAN_BASE', plugin_basename( OPCEAN_PATH ) );
}

/** URI constant for Opcean */
if( ! defined( 'OPCEAN_URL' ) ) {
    define( 'OPCEAN_URL', plugins_url( '/', __FILE__ ) );
}

/**
 * Opcean Framework - Type-Safe Factory
 *
 * Provides factory methods for creating framework components.
 * All methods return NEW instances (factory pattern) for maximum flexibility.
 *
 * @package Giganteck\Opcean
 * @version 1.0.0
 * @since 1.0.0
 */
final class Opcean {
	/**
	 * Custom factory functions for extensibility
	 *
	 * @var array<string, callable>
	 */
	private static $customFactories = [];

	/**
	 * Prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 *
	 * @throws \Exception
	 */
	public function __wakeup() {
		throw new \Exception("Cannot unserialize singleton");
	}

	/**
	 * Prevent serialization
	 *
	 * @return array
	 */
	public function __sleep() {
		return [];
	}

	/**
	 * Create a new Metabox instance
	 *
	 * @return MetaboxInterface
	 */
	public static function metabox(): MetaboxInterface {
		return self::resolve('metabox', Metabox::class);
	}

	/**
	 * Create a new Setting instance
	 *
	 * @return SettingInterface
	 */
	public static function setting(): SettingInterface {
		return self::resolve('setting', Setting::class);
	}

	/**
	 * Create a new TermMeta instance
	 *
	 * @return TermMetaInterface
	 */
	public static function termMeta(): TermMetaInterface {
		return self::resolve('term_meta', TermMeta::class);
	}

	/**
	 * Register a custom factory for a component type
	 *
	 * This allows plugins/themes to override component creation with custom implementations.
	 *
	 * @param string $key Component key (e.g., 'metabox', 'setting', 'term_meta')
	 * @param callable $factory Factory function that returns the component instance
	 * @return void
	 */
	public static function extend(string $key, callable $factory): void {
		self::$customFactories[$key] = $factory;
	}

	/**
	 * Resolve component instance
	 *
	 * Checks for custom factory first, then creates default instance.
	 *
	 * @param string $key Component key
	 * @param class-string<T> $defaultClass Default class to instantiate
	 * @return mixed
	 */
	private static function resolve(string $key, string $defaultClass) {
		if (isset(self::$customFactories[$key])) {
			return call_user_func(self::$customFactories[$key]);
		}

		return new $defaultClass();
	}

	/**
	 * Check if a custom factory is registered
	 *
	 * @param string $key Component key
	 * @return bool
	 */
	public static function hasCustomFactory(string $key): bool {
		return isset(self::$customFactories[$key]);
	}

	/**
	 * Remove a custom factory
	 *
	 * @param string $key Component key
	 * @return void
	 */
	public static function removeCustomFactory(string $key): void {
		unset(self::$customFactories[$key]);
	}

	/**
	 * Get all registered custom factory keys
	 *
	 * @return array<string>
	 */
	public static function getCustomFactories(): array {
		return array_keys(self::$customFactories);
	}

	/**
	 * Get framework version
	 *
	 * @return string
	 */
	public static function version(): string {
		return OPCEAN_VERSION;
	}

}

