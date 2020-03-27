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

Here is a nice todolist of what's missing:

Priority 0, we need it ASAP:

 - [ ] handle null references when lazy loading any to one relationship,
   as of today, a virtual proxy is used, but it'll crash if loaded reference
   is null,

 - [ ] make lazy collections rewindable, seriously,

 - [ ] implement the N+1 bulk lazy load solution for large result sets with
   any to many collection,

 - [ ] write the finder API, with a dynamic registry, able to introspect for
   classes and call the builder if necessary (using the entity self-defining
   interface),

 - [ ] write a cache implementation for the finder API,

 - [ ] implement functionnal tests with a real SQL backend behind (right now
   only SQL generation is tested).

Priority 1, we need it before being able to use it:

 - [ ] handle mapping tables,

 - [ ] write a code generator as a cache decorator for the finder API that would
   generate very fast and efficient PHP code to create repository definitions,

 - [ ] make that PHP cache not so stupid and lazy load repository definitions
   on demand,

 - [ ] add finder in builder to ensure relation target repositories are created
   or exist when adding new relations, make it lazy with a link resolution phase
   if necessary (handle potential circular references),

 - [ ] allow custom repositories to be used instead of the default one. 

Priority 2, industrialisation:

 - [x] implement chain definition registry

 - [x] implement PHP driven configuration using a pattern builder,

 - [ ] implement SQL schema parsing auto configuration (from SQL to PHP class),

 - [ ] implement yaml/annotation driven configuration (from PHP class to SQL);

 - [ ] implement a Symfony bundle,

 - [ ] documentation.

Priority 3, for later:

 - [ ] implement update, delete and insert helpers.
