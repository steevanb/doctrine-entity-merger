[![version](https://img.shields.io/badge/version-1.0.0-green.svg)](https://github.com/steevanb/doctrine-events/tree/1.0.0)
[![doctrine](https://img.shields.io/badge/doctrine/orm-^2.5.0-blue.svg)](http://www.doctrine-project.org)
[![php](https://img.shields.io/badge/php-^5.4.6 || ^7.0-blue.svg)](http://www.php.net)
![Lines](https://img.shields.io/badge/code lines-XXXX-green.svg)
![Total Downloads](https://poser.pugx.org/steevanb/doctrine-events/downloads)
[![SensionLabsInsight](https://img.shields.io/badge/SensionLabsInsight-platinum-brightgreen.svg)](https://insight.sensiolabs.com/projects/cf51b54f-77fa-459d-8a55-503732fef052/analyses/1)
[![Scrutinizer](https://scrutinizer-ci.com/g/steevanb/doctrine-entity-merger/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/steevanb/doctrine-entity-merger/)

doctrine-entity-merger
---------------------

When you use PARTIAL in DQL, you retrive only fields you need, instead of all Entity fields.

But, if you execute 2 PARTIAL on same entity, but not same fields, you will have this problem :
```php
class FooEntity
{
    protected $id;
    protected $name;
    protected $description;
}

$foo1 = $repository
    ->createQueryBuilder('foo')
    ->select('PARTIAL foo.{id, name}')
    ->where('site.id = 1')
    ->getQuery()
    ->getSingleResult();

var_dump($foo1->getDescription()); // null, that's fine, description is not in PARTIAL

$foo2 = $repository
    ->createQueryBuilder('foo')
    ->select('PARTIAL foo.{id, name, description}')
    ->where('site.id = 1')
    ->getQuery()
    ->getSingleResult();

// $foo1 is same object as $foo2, cause Doctrine know first query hydrated $foo1

// so, when you ask same entity (same id in query) with 2nd query, Doctrine will execute SQL,

// but will not hydrate a new entity

// UnitOfWork will return instance of Foo it had already hydrated, with first query

var_dump(spl_object_has($foo1) === $spl_object_has($foo2)); // true

// but, as Doctrine return $foo1 in 2nd query, your new field description will not be defined in $foo1

var_dump($foo1->getDescription()); // null, but we want it, cause it's defined in PARTIAL 2nd query

```

You can use steevanb\DoctrineEntityMerger\QueryHint::MERGE_ENTITY to define description in $foo1 :
```php
use steevanb\DoctrineEntityMerger\QueryHint;

$foo1 = $repository
    ->createQueryBuilder('foo')
    ->select('PARTIAL foo.{id, name}')
    ->where('site.id = 1')
    ->getQuery()
    ->getSingleResult();

var_dump($foo1->getName()); // 'My name' for example

var_dump($foo1->getDescription()); // null, that's fine, description is not in PARTIAL

$foo1->setName('New name');

var_dump($foo1->getName()); // 'New name'

$foo2 = $repository
    ->createQueryBuilder('foo')
    ->select('PARTIAL foo.{id, description}')
    ->where('site.id = 1')
    ->getQuery()
    ->setHint(QueryHint::MERGE_ENTITY, true)
    ->getSingleResult();

var_dump($foo1->getName()); // 'New name', MERGE_ENTITY will not change Foo::$name value if it was already defined in another query before

var_dump($foo1->getDescription()); // 'My description'
```

Installation
------------

As doctrine-entity-merger use steevanb/doctrine-events, see how to install it
(composer dependecy is added here, you don't need to add it for steevanb/doctrine-events) :

[steevanb/doctrine-events](https://github.com/steevanb/doctrine-events)

Add it to your composer.json :
```yml
{
    "require": {
        "steevanb/doctrine-entity-merger": "^1.0",
    }
}
```

Add EntityMergerSubscriber :
```php
$entityManager->getEventManager()->addEventSubscriber(
    new steevanb\DoctrineEntityMerger\EventSubscriber\EntityMergerSubscriber()
);
```

If you want to add MERGE_ENTITY hint to all of your queries, you can do this :

```php
use steevanb\DoctrineEntityMerger\QueryHint;
$entityManager->getConfiguration()->setDefaultQueryHint(QueryHint::MERGE_ENTITY, true);
```

For example, if you are on a Symfony project, you can add it in AppKernel :
```php
# app/AppKernel.php

class AppKernel
{
    public function boot()
    {
        parent::boot();

        // add hint MERGE_ENTITY to all your queries
        foreach ($this->getContainer()->get('doctrine')->getEntityManagers() as $entityManager) {
            $entityManager->getConfiguration()->setDefaultQueryHint(QueryHint::MERGE_ENTITY, true);
        }

        // add listener, who use steevanb/doctrine-events to change UnitOfWork::createEntity()
        // to take into account MERGE_ENTITY hint
        $this->getContainer()->get('doctrine')->getEntityManager()->getEventManager()->addEventSubscriber(
            new \steevanb\DoctrineEntityMerger\EventSubscriber\EntityMergerSubscriber()
        );
    }
}
