Goat Mapper
===========

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   install/index
   usage/index
   hydrator/index

Introduction
^^^^^^^^^^^^

Goat mapper is an SQL to PHP entity mapper, supporting complex object relations.

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

Use case and desing approach
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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

Concepts and software design
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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

Getting started
^^^^^^^^^^^^^^^

Now you that know everything, you may start using it if you wishes to.
