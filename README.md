About RootContainer
===================

This project is a test project developed as a proof of concept while working on the [ContainerInterop](https://github.com/container-interop/container-interop/) project.

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

