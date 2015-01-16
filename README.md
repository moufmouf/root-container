About RootContainer
===================

This project is a test project developed as a proof of concept while working on the [ContainerInterop](https://github.com/container-interop/container-interop/) project.

###This package was the precursor of [Container-Installer](https://github.com/thecodingmachine/container-installer) and [Root-Container](https://github.com/thecodingmachine/root-container) and can now be considered as abandonned and replaced by those 2 packages.

The goal of this project is to create a "root container", that can automatically detect and add containers contained
in other packages into a global composite container that can be used by the application.

Compared to the classical way of thinking about a web application, this is a paradigm shift.

**In a "classical" application**, packages added to the application may add new instances to the main and only DI container.
This is what SF2 bundles, ZF2 modules or Mouf2 packages are doing.

**Using RootController**, each package provides its own DI container that contains instances. DI containers are added
to a global container that is queried.

Benefits
--------
Each package provides its container. The package is not dependent on the DI container used in the application.
This way, we can provide packages that are framework agnostic.

Downsides
---------
The classical implementation of the composite controller might imply a performance hit. We will need to think of a way to 
improve the performance of the composite container (maybe by doing entries maps, mapping entries to their associated container...) 

About other projects
--------------------
This is not the only project working on the "one container per package" paradigm. The [FrameworkInterop project](https://github.com/mnapoli/framework-interop)
by @mnapoli is also taking the same route (although its scope is larger).

How to create a package with an integrated DI container
=======================================================

This is the nice part: it is easy!

In your *composer.json* file, add an "extra" session like this one:

```json
{
	"extra": {
		"container-interop": {
			"container-factory": "My\\ContainerFactory::getContainer"
		}
	}
}
```

The "container-factory" parameter must point to a function or a static method that returns the container.

Here is a sample implementation:

```php
class ContainerFactory {
	private static $container;

	/**
	 * This method is returning a configured container
	 *
	 * @param ContainerInterface $rootContainer
	 * @return ContainerInterface
	 */
	public static function getContainer(ContainerInterface $rootContainer) {
		if (!$this->container) {
			// Delegate dependencies fetching to the root container.
			$this->container = new PimpleInterop($rootContainer);
			$this->container['hello'] = $this->container->share(function(ContainerInterface $container) {
				return array('hello' => $container->get('world'));
			}); 
		}
		return $this->container;
	}
}
```

A quick note about this code: we are providing a [PimpleInterop container](https://github.com/moufmouf/pimple-interop).
PimpleInterop is a modified version of Pimple 1 that adds compatibility with the [ContainerInterop](https://github.com/container-interop/container-interop/) project.

**Important**: the factory takes one compulsory parameter: the `$rootContainer`. If some entries in your container are containing
*external dependencies* (dependencies that are not part of the container), then your container needs to be able
to delegate dependencies fetching to the $rootContainer. For instance, `PimpleInterop` can delegate dependencies fetching if
you pass another container as the first argument of the constructor.

Note: your package does not have to require the `mouf/root-container` package. This is sweet because if 
other container aggregators follow the same convention (referencing factory code in `composer.json` extra section),
there can easily be many different implementations of a root-container (maybe one per framework). 

How to use the root container in your project?
==============================================

First of all, you have to use packages that have integrated DI containers (see previous chapter).

All you have to do is to include the root-container Composer package in your project:

**composer.json**
```json
{
	"require" : {
		"mouf/root-container" : "dev-master"
	},
	"repositories" : [{
		"type" : "git",
		"url" : "git@github.com:moufmouf/root-container.git"
	}]
}
```

Getting an instance of the root container is easy:

```php
use Mouf\RootContainer;

$container = RootContainerFactory::get();
$myEntry = $container->get('myEntry');
```

"Hey, but you are using a static method to get the RootContainer instance! Static methods are evil!"

Mmmm... yeah, of course. But containers are constructed using static methods anyway! So it is not that bad.
The real problem is that the RootContainer is accessible from anywhere in the code (since it is exposed statically).
If this is an issue, do not use RootContainer. Instead, write your own RootContainer implementation
that fits the framework you are using.

Testing
=======

The [root-container-test-project on GitHub](https://github.com/moufmouf/root-container-test-project) provides
a nice playground to see how the root-container behaves.

```
# Download the project
git clone https://github.com/moufmouf/root-container-test-project.git
cd root-container-test-project
# Install dependencies (this will also compile the root-container)
php composer.phar install
# Run the tests
vendor/bin/phpunit
```

This test project requires 2 subprojects ([A](https://github.com/moufmouf/root-container-test-subprojectA) and 
[B](https://github.com/moufmouf/root-container-test-subprojectB)).
Both subprojects are providing custom DI containers [through their `composer.json` file](https://github.com/moufmouf/root-container-test-subprojectA/blob/master/composer.json).
The DI containers are provided by [PimpleInterop](https://github.com/moufmouf/root-container-test-subprojectB/blob/master/src/Acme/ProjectB/Factory.php),
an extended version of Pimple that supports the ContainerInterop standard.

About performance
=================

The current implementation of RootContainer is relying on the Acclimate's CompositeContainer. It is 
a proof-of-concept and no effort has been done performance-wise.
The more container you have in your application, the lower the performance should be (linearly).

It does not mean however that performance cannot be improved. There are many possible strategies to improve performance,
like building a map of all entries associated to their respective container. This is going further than
the current scope of this project.
