.. _install:

Getting started
===============

Installation
^^^^^^^^^^^^

.. code-block:: sh

   composer require makinacorpus/goat-query ^2
   composer require makinacorpus/goat-mapper

Symfony setup
^^^^^^^^^^^^^

Bundle setup and configuration
##############################

@todo

Services usage
##############

@todo

Register a custom repository
############################

@todo

Standalone setup
^^^^^^^^^^^^^^^^

Setup a database connection
###########################

You need to create a database connection to use this library. It explicitely
uses ``makinacorpus/goat-query`` for writing SQL.

A very easy setup would be something such as:

.. code-block:: php

   <?php

   use Goat\Driver\Configuration;
   use Goat\Driver\ExtPgSQLDriver;

   $driver = new ExtPgSQLDriver();
   $driver->setConfiguration(
       Configuration::fromString(
           "pgsql://user:password@hostname:port/database"
       )
   );
   $runner = $driver->getRunner();

Additionnaly, you may re-use an existing PDO connection as such:

.. code-block:: php

   <?php

   use Goat\Driver\DriverFactory;

   $runner = DriverFactory::fromPDOConnection($pdo)->getRunner();

Or using an existing Doctrine DBAL connection:

.. code-block:: php

   <?php

   use Goat\Driver\DriverFactory;

   $runner = DriverFactory::fromDoctrineConnection($connection)->getRunner();

Of course, it is strongly advised that you read its documentation for using
it correctly.

Setup the definition registry
#############################

Definition registry is the component that does lookup for entity metadata
and build the entity graph that will be traversed in order to build SQL
queries.

Multiple implementations are provided, we will document them in order of
ease of use.

We will consider that in all cases, you want this process to be cached,
at least in memory, so start by creating the cache decorator and definition
chain implementations:

.. code-block:: php

   <?php

   use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;

   $chainDefinitionRegistry = new $definitionRegistry();
   $definitionRegistry = new CacheDefinitionRegistry($chainDefinitionRegistry);

Starting from there, you are ready to choose your definition registry concrete
implementation.


After this step, you can proceed with defining entities, or continue this section
to see other entity definition options.

When you will have setup it fully, you will be able to fetch entity definitions
using:

.. code-block:: php

   <?php

   $entity = $definitionRegistry->getDefinition(\Vendor\App\Entity\Foo::class);

Now proceed to next chapters in order to setup a concrete implementation.

Static entity definition registry
#################################

Static entity definition registry is the easiest to use, but it requires
you to implement the ``Goat\Mapper\Definition\Registry\StaticEntityDefinition``
interface on all your entity classes.

This interface provides a single static method that will take a single parameter
which a builder instance, implementing the builder pattern with naturally named
methods, easy to use:

.. code-block:: php

   <?php

   interface StaticEntityDefinition
   {
       /**
        * Define entity using the given builder.
        */
       public static function defineEntity(DefinitionBuilder $builder): void;
   }

@todo link to definition builder documentation

In order to setup the definition registry, let's proceed continuing the
code we started above:

.. code-block:: php

   <?php

   use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;

   // Code from above is here...

   $staticDefinitionRegistry = new StaticEntityDefinitionRegistry();

   // Static definition registry will extensively use the proxy pattern in order
   // to lazy load entity definitions while browsing the entity graph, so it needs
   // a reference to the facade definition registry (i.e. the one doing caching):
   $staticDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);

   // Add it to our chain.
   $chainDefinitionRegistry->add($staticDefinitionRegistry);


Using PHP cache definition registry
###################################

PHP cache is an extra caching layer for your entity definitions that generates
the definitions into PHP functions, dumped into PHP files in cache, which are
way faster than other way of defining entities.

In order to use it, you must adapt the initial code:

.. code-block:: php

   <?php

   use Goat\Mapper\Cache\Definition\Registry\PhpDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;

   $chainDefinitionRegistry = new ChainDefinitionRegistry();
   $phpDefinitionRegistry = new PhpDefinitionRegistry($chainDefinitionRegistry);
   $definitionRegistry = new CacheDefinitionRegistry($phpDefinitionRegistry);

   // PHP cache definition registry will extensively use the proxy pattern in order
   // to lazy load entity definitions while browsing the entity graph, so it needs
   // a reference to the facade definition registry (i.e. the one doing caching):
   $phpDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);

Per default, PHP code will be generated in ``\sys_get_temp_dir()`` which may be
forbidden using ``open_basedir()``. You can set this folder pretty much anywhere:

.. code-block:: php

   <?php

   $phpDefinitionRegistry->setGeneratedFileDirectory('/some/path/');

@todo autoload files and composer for even faster loading.

Setup the entity hydrator
#########################

Of course, everything is about loading entities, so we also need an hydrator
for those.

Using `makinacorpus/generated-hydrator-bundle`
----------------------------------------------

An easy way to setup an hydrator is by using ``makinacorpus/generated-hydrator-bundle``
(even if documented as such, you don't need Symfony to make it work).

Pre-requisites:

 - For this, we consider that you have setup a definition registry as
   described above, we will reference it as ``$definitionRegistry``.

First install it:

.. code-block:: sh

   composer require makinacorpus/generated-hydrator-bundle

Then set it up:

.. code-block:: php

   <?php

   use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
   use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
   use Goat\Mapper\Hydration\HydratorRegistry\GeneratedHydratorBundleHydratorRegistry;

   $entityHydrator = new EntityHydratorFactory(
       $definitionRegistry,
       new GeneratedHydratorBundleHydratorRegistry(
           new DefaultHydrator(
               \sys_get_temp_dir()
           )
       )
   );


Please note that later, you will be able to use ``ocramius/generated-hydrator``
directly instead.

@todo

Custom implementation
---------------------

@todo

Setup the manager
#################

Manager is the only dependency you code will need once it is setup. It knows
the entity definition registry, and is able to build complex SQL queries for
you.

Pre-requisites:

 - For this, we consider that you have setup a definition registry as
   described above, we will reference it as ``$definitionRegistry``.

 - You need the entity hydrator as well, as saw above, we will reference
   it as ``$entityHydrator``.

 - You also need a working database connection as described on top of
   this documentation, we will reference it as ``$runner``.

Seting it up is as easy as:

.. code-block:: php

   <?php

   use Goat\Mapper\Repository\DefaultRepositoryManager;

   $manager = new DefaultRepositoryManager(
       $runner,
       $definitionRegistry,
       $entityHydrator
   );

Wrapping it up
##############

Here is a complete sample of full initialization:

.. code-block:: php

   <?php

   declare(strict_types=1);

   use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
   use Goat\Driver\Configuration;
   use Goat\Driver\ExtPgSQLDriver;
   use Goat\Mapper\Cache\Definition\Registry\PhpDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
   use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
   use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
   use Goat\Mapper\Hydration\HydratorRegistry\GeneratedHydratorBundleHydratorRegistry;
   use Goat\Mapper\Repository\DefaultRepositoryManager;

   // Definition registry

   $chainDefinitionRegistry = new ChainDefinitionRegistry();
   $phpDefinitionRegistry = new PhpDefinitionRegistry($chainDefinitionRegistry);
   $definitionRegistry = new CacheDefinitionRegistry($phpDefinitionRegistry);

   $phpDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);
   $phpDefinitionRegistry->setGeneratedFileDirectory('/some/path/');

   $staticDefinitionRegistry = new StaticEntityDefinitionRegistry();
   $staticDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);
   $chainDefinitionRegistry->add($staticDefinitionRegistry);

   // Entity hydrator

   $entityHydrator = new EntityHydratorFactory(
       $definitionRegistry,
       new GeneratedHydratorBundleHydratorRegistry(
           new DefaultHydrator(
               \sys_get_temp_dir()
           )
       )
   );

   // Database connection

   $driver = new ExtPgSQLDriver();
   $driver->setConfiguration(
       Configuration::fromString(
           "pgsql://user:password@hostname:port/database"
       )
   );
   $runner = $driver->getRunner();

   // Entity manager

   $manager = new DefaultRepositoryManager(
       $runner,
       $definitionRegistry,
       $entityHydrator
   );

Of course, adapt to your needs or your framework and tooling.

Now, you are ready to setup your entity definitions.

@todo link to entity definition documentation
