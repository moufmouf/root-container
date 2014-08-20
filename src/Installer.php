<?php
namespace Mouf\RootContainer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\Util\Filesystem;
use Composer\Package\Dumper\ArrayDumper;

/**
 * RootContainer Installer for Composer.
 * (based on RobLoach's code for ComponentInstaller
 */
class Installer extends LibraryInstaller
{

    /**
     * The location where Components are to be installed.
     */
    protected $componentDir;

    /**
     * {@inheritDoc}
     *
     * Containers are supported by all packages. This checks wheteher or not the
     * entire package is a "container", as well as injects the script to act
     * on containers embedded in packages that are not just "container" types.
     */
    public function supports($packageType)
    {
        // Containers are supported by all package types. We will just act on
        // the root package's scripts if available.
        $rootPackage = isset($this->composer) ? $this->composer->getPackage() : null;
        if (isset($rootPackage)) {
            // Ensure we get the root package rather than its alias.
            while ($rootPackage instanceof AliasPackage) {
                $rootPackage = $rootPackage->getAliasOf();
            }

            // Make sure the root package can override the available scripts.
            if (method_exists($rootPackage, 'setScripts')) {
                $scripts = $rootPackage->getScripts();
                // Act on the "post-autoload-dump" command so that we can act on all
                // the installed packages.
                $scripts['post-autoload-dump']['rootcontainer-installer'] = 'Mouf\\RootContainer\\Installer::postAutoloadDump';
                $rootPackage->setScripts($scripts);
            }
        }

        // Explicitly state support of "container" packages.
        return $packageType === 'container';
    }

    /**
     * Script callback; Acted on after the autoloader is dumped.
     */
    public static function postAutoloadDump(Event $event)
    {
        // Retrieve basic information about the environment and present a
        // message to the user.
        $composer = $event->getComposer();
        $io = $event->getIO();
        $io->write('<info>Compiling containers list</info>');

        $packages = self::getPackagesList($composer);
        
        $factoryList = array();
        
        foreach ($packages as $package) {
        	if (isset($package['extra']['container-interop']['container-factory'])) {
        		$factories = $package['extra']['container-interop']['container-factory'];
        		if (!is_array($factories)) {
        			$factories = array($factories);
        		}
        		$factoryList = array_merge($factoryList, $factories);
        	}
        }
        
        if ($factoryList) {
        	// TODO: security checks
	        $fp = fopen("vendor/containers.php", "w");
	        fwrite($fp, "<?php\n");
	        foreach ($factoryList as $factory) {
	        	fwrite($fp, "\$rootContainer->addContainer(".$factory."(\$rootContainer));\n");
	        }
        }
    }
    
    /**
     * Returns the list of packages that contain containers.
     * 
     * @param Composer $composer
     * @return PackageInterface[]
     */
    protected static function getPackagesList(Composer $composer)
    {
    	// Get the available packages.
    	$allPackages = array();
    	$locker = $composer->getLocker();
    	if (isset($locker)) {
    		$lockData = $locker->getLockData();
    		$allPackages = isset($lockData['packages']) ? $lockData['packages'] : array();
    
    		// Also merge in any of the development packages.
    		$dev = isset($lockData['packages-dev']) ? $lockData['packages-dev'] : array();
    		foreach ($dev as $package) {
    			$allPackages[] = $package;
    		}
    	}
    	
    	$packages = array();
    
    	// Only add those packages that we can reasonably
    	// assume are components into our packages list
    	foreach ($allPackages as $package) {
    		$extra = isset($package['extra']) ? $package['extra'] : array();
    		if (isset($extra['container-interop']) && is_array($extra['container-interop'])) {
    			$packages[] = $package;
    		}
    	}
    
    	// Add the root package to the packages list.
    	$root = $composer->getPackage();
    	if ($root) {
    		$dumper = new ArrayDumper();
    		$package = $dumper->dump($root);
    		$package['is-root'] = true;
    		$packages[] = $package;
    	}
    
    	// TODO: order the packages in reverse order of dependencies
    	
    	return $packages;
    }
}
