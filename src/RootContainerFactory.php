<?php
namespace Mouf\RootContainer;

use Acclimate\Container\CompositeContainer;
use Interop\Container\ContainerInterface;

/**
 * Factory in charge of creating an instance of the root container.
 * 
 * @author David Négrier <david@mouf-php.com>
 */
class RootContainerFactory {
	
	private static $rootContainer;
	
	/**
	 * Returns a container aggregating all the containers of the application.
	 * 
	 * @return ContainerInterface
	 */
	public static function get() {
		if (!self::$rootContainer) {
			self::$rootContainer = new CompositeContainer();
			
			require '../../../containers.php';
		}
		return self::$rootContainer;
	}
}