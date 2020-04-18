# Goat Mapper

Goat mapper is an SQL to PHP entity mapper, supporting complex object relations.

## Introduction

This is not a full-fledged ORM, even though it does look like it. Note that the main
difference between existing ORMs and this tool is that this tool is read-only.
Yes, you read it right, it is READ-ONLY, and it is by design.

It's build on top of:

 - ``makinacorpus/goat-query`` − https://github.com/pounard/goat-query
 - ``ocramius/generated-hydrator`` − https://github.com/Ocramius/GeneratedHydrator
 - ``ocramius/proxy-manager`` − https://github.com/Ocramius/ProxyManager

Let's be honest, **this is an experimental project**, if you need an ORM, use
a mature and community-driven one such as Doctrine. This component as of now is
not meant be as powerful or complete than the many already existing mature
solutions.

## Use case and desing approach

It is meant to be used in a Domain Driven Development approach, it delegates
writes to you. Writes shoud be implemented as dedicated methods with semantic
meaning. This tool does not hide your SQL, it just help you read and hydrate
your data more efficiently according to your SQL schema.

We consider that writing data will remain related to your domain, and cannot
be written in a generic manner without creating new problems mostly related
to data access concurency and transactions. Where most ORM fail is when you
need to fine tune your transactions, and we don't have the pretention to
solve that problem without knowing your schema and domain business in
advance.

## Concepts and software design

Basically, it maps your SQL tables into objects using an internal and
intermediate entity-relationship graph-based represendation, and write
complex SQL queries traversing this graph to load your objects.

It implement a few solutions for the famous N+1 problem:

 - eager loading for any to one relationships, using ``JOIN``, able to
   ``JOIN`` indefinitely (meaning you can fetch A -> B -> C ...) in a
   single SQL query,

 - lazy loading of collections (not so N+1 solving) but yet nice to use
   for end users,

 - bulk lazy load collections over an entity result set that cannot be
   naturally ``JOIN``'ed using an additional SQL query for each relation,

 - hydrates everything efficiently using ``ocramius/generated-hydrator``.

# Installation

Please refer to the complete documentation in the ``./docs/`` folder.

# Implementations notes

## Entity graph

Entities and relations are represented using a graph, which can be browsed
in all directions. Every relation may have its inverse counter-part within
the graph depending the graph builder that was chosen.

Query builder uses that graph to build SQL queries.

In order to browse this graph, you must start with a specific entity class
as a starting point. While browsing it, each time you reach a new entity node
it is being lazy loaded, the graph is never entirely loaded in memory.

## Query graph

@todo

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

 - [x] implement functionnal tests with a real SQL backend behind (right now
   only SQL generation is tested),

 - [x] write a code generator as a cache decorator for the finder API that would
   generate very fast and efficient PHP code to create repository definitions,
   based upon the intermediate reprensentation,

 - [x] make that PHP cache not so stupid and lazy load repository definitions
   on demand,

 - [x] industrialise the PHP cache writer, decouple function name inflector,
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

 - [ ] untangle the relation fetcher interface and implementation mess,

 - [ ] find a proper and more direct way to collect entities identifiers,

 - [ ] implement the N+1 bulk lazy load solution for large result sets with
   any to many collection - note that this is implemented, at least in
   interfaces, but concrete implementation does not bulk load,

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

 - [ ] handle null references when lazy loading any to one relationship,
   as of today, a virtual proxy is used, but it'll crash if loaded reference
   is null,

 - [ ] EXISTS/CTE optimisations for lazy loading,

 - [ ] implement update, delete and insert helpers.
