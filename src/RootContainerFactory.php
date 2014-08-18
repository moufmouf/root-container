<?php
namespace Mouf\RootContainer;

use Acclimate\Container\CompositeContainer;
/**
 * Factory in charge of creating an instance of the root container.
 * 
 * @author David Négrier <david@mouf-php.com>
 */
class RootContainerFactory {
	
	public static function get() {
		$rootContainer = new CompositeContainer();
		
		require '../../../containers.php';
	}
}