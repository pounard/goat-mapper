# Goat Mapper

Yes, because one of the best practice is "do not reinvent the wheel" I decided
to re-invent it.

This is not an ORM, not really, even thought it seriously looks like it. Note
that one main differences between what exists and this ORM is that it's read
only. Yes, you read it right, it is READ-ONLY, and it is by design.

It is meant to be used in Domain Driven Development code, where writes should
be implemented as dedicated methods with semantic meaning. This tool does not
wrap your SQL, it just help you reading your data more efficiently, and make
lots and lots of code more natural to write.

But writing data is YOUR PROBLEM, not mine. It will depend on your domain code,
on your application specification, on your software design. I won't implement
it for you.

Basically yes, it maps your SQL tables into objects, and write complex SQL
queries to load them, and implement some solutions for the N+1 problem:

 - eager loading for any to one relationships, using JOIN, able to JOIN
   indefinitely (meaning you can fetch A -> B -> C ...) in a single SQL
   query,

 - lazy loading of collections (not so N+1 solving) but yet nice to use
   for end users,

 - not yet implemented, but it will bulk lazy load collections that cannot
   be naturally JOIN'ed in a second SQL query for a single result set,

 - hydrates everything nicely using ocramius/generated-hydrator with
   makinacorpus/generated-hydrator-bundle on top to support nested
   object tree.

Now it has some problems, let's be honest, if you need an ORM, use Doctrine,
don't come and report be any bugs, it will NEVER be as powerful as other
complete and mature solutions are such as the Doctrine ORM.

# Installation

Add the dependency using composer:

```sh
composer require makinacorpus/goat-query
composer require makinacorpus/goat-mapper
```

# Standalone setup

## Setup a database connection

You need to create a database connection to use this library. It explicitely
uses `makinacorpus/goat-query` for writing SQL.

A very easy setup would be something such as:

```php
use Goat\Driver\Configuration;
use Goat\Driver\ExtPgSQLDriver;

$driver = new ExtPgSQLDriver();
$driver->setConfiguration(
    Configuration::fromString(
        "pgsql://user:password@hostname:port/database"
    )
);
$runner = $driver->getRunner();
```

Of course, it is strongly advised that you read its documentation for using
it correctly.

## Setup the definition registry

Definition registry is the component that does lookup for entity metadata
and build the entity graph that will be traversed in order to build SQL
queries.

Multiple implementations are provided, we will document them in order of
ease of use.

We will consider that in all cases, you want this process to be cached,
at least in memory, so start by creating the cache decorator and definition
chain implementations:

```php
use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;

$chainDefinitionRegistry = new $definitionRegistry();
$definitionRegistry = new CacheDefinitionRegistry($chainDefinitionRegistry);
```

Starting from there, you are ready to choose your definition registry concrete
implementation.


After this step, you can proceed with defining entities, or continue this section
to see other entity definition options.

When this section will be complete, you will be able to fetch entity definitions
using:

```php
$entity = $definitionRegistry->getDefinition(\Vendor\App\Entity\Foo::class);
```

### Static entity definition registry

Static entity definition registry is the easiest to use, but it requires
you to implement a the `Goat\Mapper\Definition\Registry\StaticEntityDefinition`
interface on all your entity classes.

This interface provides a single static method that will take a single parameter
which a builder instance, implementing the builder pattern with naturally named
methods, easy to use:

```php
interface StaticEntityDefinition
{
    /**
     * Define entity using the given builder.
     */
    public static function defineEntity(DefinitionBuilder $builder): void;
}
```

In order to setup the definition registry, let's proceed continuing the
code we started above:

```php
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;

// Code from above is here...

$staticDefinitionRegistry = new StaticEntityDefinitionRegistry();

// Static definition registry will extensively use the proxy pattern in order
// to lazy load entity definitions while browsing the entity graph, so it needs
// a reference to the facade definition registry (i.e. the one doing caching):
$staticDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);

// Add it to our chain.
$chainDefinitionRegistry->add($staticDefinitionRegistry);
```

### Using PHP cache definition registry

PHP cache is an extra caching layer for your entity definitions that generates
the definitions into PHP functions, dumped into PHP files in cache, which are
way faster than other way of defining entities.

In order to use it, you must adapt the initial code:

```php
use Goat\Mapper\Cache\Definition\Registry\PhpDefinitionRegistry;
use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;

$chainDefinitionRegistry = new ChainDefinitionRegistry();
$phpDefinitionRegistry = new PhpDefinitionRegistry($chainDefinitionRegistry);
$definitionRegistry = new CacheDefinitionRegistry($phpDefinitionRegistry);

// Static definition registry will extensively use the proxy pattern in order
// to lazy load entity definitions while browsing the entity graph, so it needs
// a reference to the facade definition registry (i.e. the one doing caching):
$phpDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);
```

Per default, PHP code will be generated in `\sys_get_temp_dir()` which may be
forbidden using `open_basedir()`. You can set this folder pretty much anywhere:

```php
$phpDefinitionRegistry->setGeneratedFileDirectory('/some/path/');
```

@todo autoload files and composer for even faster loading.

## Setup the entity hydrator

Of course, everything is about loading entities, so we also need an hydrator
for those.

An easy way to setup an hydrator is by using `makinacorpus/generated-hydrator-bundle`
(even if documented as such, you don't need Symfony to make it work).

Pre-requisites:

 - For this, we consider that you have setup a definition registry as
   described above, we will reference it as `$definitionRegistry`.

First install it:

```sh
composer require makinacorpus/generated-hydrator-bundle
```

Then set it up:

```php
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
```

Please note that later, you will be able to use `ocramius/generated-hydrator`
directly instead.

@todo more documentation

## Setup the manager

Manager is the only dependency you code will need once it is setup. It knows
the entity definition registry, and is able to build complex SQL queries for
you.

Pre-requisites:

 - For this, we consider that you have setup a definition registry as
   described above, we will reference it as `$definitionRegistry`.

 - You need the entity hydrator as well, as saw above, we will reference
   it as `$entityHydrator`.

 - You also need a working database connection as described on top of
   this documentation, we will reference it as `$runner`.

Seting it up is as easy as:

```php
use Goat\Mapper\Repository\DefaultRepositoryManager;

$manager = new DefaultRepositoryManager(
    $runner,
    $definitionRegistry,
    $entityHydrator
);
```

And that's it, you are ready to go!

## Defining an entity

## Wrapping it up

Here is a complete sample of full initialization:

```php
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
```

Of course, adapt to your needs or your framework.

### Using the static entity definition interface

@todo

### Using an array

@todo

### Using YAML

@todo

## Usage

@todo

# Symfony setup

@todo I need to write this.

# Entity graph

Entities and relations are represented using a graph, which can be browsed
in all directions. Every relation may have its inverse counter-part within
the graph depending the graph builder that was chosen.

Query builder uses that graph to build SQL queries.

In order to browse this graph, you must start with a specific entity class
as a starting point. While browsing it, each time you reach a new entity node
it is being lazy loaded, the graph is never entirely loaded in memory.

# Roadmap

Here is a nice todolist of what's missing:

Priority 0, we need it ASAP:

 - [x] work around handle null references when lazy loading any to one
   relationship: done by forcing join based eager load for to one,

 - [x] make lazy collections rewindable, seriously,

 - [x] rewrite SELECT query using a graph for eager relations, allowing to
   recurse in repository dependency graph for eager loading,

 - [x] make relation fetcher use the graph based query builder,

 - [x] write the finder API, with a dynamic registry, able to introspect for
   classes and call the builder if necessary (using the entity self-defining
   interface),

 - [x] fix join statements for relations: when source or target key is not the
   primary key it must join with the target/source table when there are given
   identifiers, because we can't use the intermediate key to match,

 - [x] remove the repository as a first class citizen and make it transcient,
   this means that all queries can be created without materializing the
   repositories,

 - [x] specialize the relation definition object to make it more obvious on
   how to use it,

 - [ ] empty test cache folder on setUp() but keep data on teardown, it might
   be usseful for debug.

Priority 1, we need it before being able to use it:

 - [x] implement to one conditions using property path e.g.
   'entity.property.property' = foo,

 - [x] handle mapping tables in SQL builder,

 - [x] with mapping tables builder, ensure mapping keys are compatible with
   their counterpart in source and target tables,

 - [ ] untangle the relation fetcher interface and implementation mess,

 - [ ] find a proper and more direct way to collect entities identifiers,

 - [ ] implement the N+1 bulk lazy load solution for large result sets with
   any to many collection - note that this is implemented, at least in
   interfaces, but concrete implementation does not bulk load,

 - [x] implement functionnal tests with a real SQL backend behind (right now
   only SQL generation is tested),

 - [x] write a code generator as a cache decorator for the finder API that would
   generate very fast and efficient PHP code to create repository definitions,
   based upon the intermediate reprensentation,

 - [x] make that PHP cache not so stupid and lazy load repository definitions
   on demand,

 - [ ] industrialise the PHP cache writer, decouple function name inflector,
   file name locator, and file loader,

 - [ ] make the rewindable collections much smarter (create a new iterator impl
   that populate an internal array over first iteration maybe, to keep them
   fully lazy) - now that result iterators from goat are rewindable, not sure
   we need to keep a rewindable collection,

 - [ ] identify relations using a predicitable name, and a direction, so that
   all relations and their inverse relations can be identified,

 - [ ] create various compilation passes after builder for fixing data and
   materializing reverse relations.

 - [ ] allow custom repositories to be used instead of the default one,

 - [ ] write a basic symfony bundle,

 - [ ] add custom repository logic to symfony bundle,

 - [ ] write poor's man documentation for basic use cases.

Priority 2, industrialisation:

 - [ ] write much more functionnal tests,

 - [ ] in builder, ensure that key column names can be entity property names
   and find a way to resolve them in a smart way,

 - [ ] refactore SQL-comparison tests to ignore SELECT clauses when checking
   for JOINs statements,

 - [ ] write specific SQL-comparison tests for entity columns SELECTion,

 - [ ] implement SQL schema introspector in goat-query,

 - [ ] implement SQL builder introspector in goat-query,

 - [ ] SQL schema introspector that reads your schema and reconcile with exising
   entity classes, to auto-generated definitions,

 - [ ] SQL schema introspector that reads your schema and generate PHP entities,

 - [ ] implement to many conditions same as upper, but using EXISTS query,

 - [ ] instrumentation!

 - [x] implement chain definition registry

 - [x] implement PHP driven configuration using a pattern builder,

 - [ ] implement SQL schema parsing auto configuration (from SQL to PHP class),

 - [ ] re-implement array-based configuration,

 - [ ] implement yaml reader,

 - [x] never implement annotation (yes, I hate those),

 - [ ] stabilise the symfony bundle and make it highly customizable,

 - [ ] documentation.

Backlog, for later or when I'm bored:

 - [x] proof of concept of implementing definitions as a graph that lazy loads
   and a query builder working by traversing this graph,

 - [x] implement the rewindabe re-usable result iterator in goat-query,

 - [ ] implement a PHP dumper that creates classes that implement graph and
   directly return values instead of hydrating default implementations, I'm not
   really sure it worthes it, so keep this for much much later,

 - [ ] implement xml reader ? why exactly ?

 - [ ] handle null references when lazy loading any to one relationship,
   as of today, a virtual proxy is used, but it'll crash if loaded reference
   is null,

 - [ ] EXISTS/CTE optimisations for lazy loading,

 - [ ] implement update, delete and insert helpers.
