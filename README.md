[![version](https://img.shields.io/badge/version-1.0.5-green.svg)](https://github.com/steevanb/doctrine-entity-merger/tree/1.0.5)
[![doctrine](https://img.shields.io/badge/doctrine/orm-^2.5.0-blue.svg)](http://www.doctrine-project.org)
[![php](https://img.shields.io/badge/php-^5.4.6%20||%20^7.0-blue.svg)](http://www.php.net)
![Lines](https://img.shields.io/badge/code%20lines-264-green.svg)
![Total Downloads](https://poser.pugx.org/steevanb/doctrine-events/downloads)
[![SensionLabsInsight](https://img.shields.io/badge/SensionLabsInsight-platinum-brightgreen.svg)](https://insight.sensiolabs.com/projects/cf51b54f-77fa-459d-8a55-503732fef052/analyses/18)
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
// UnitOfWork will return instance of Foo who is already hydrated, with first query
var_dump(spl_object_hash($foo1) === spl_object_hash($foo2)); // true

// but, as Doctrine return $foo1 in 2nd query, your new field description will not be defined in $foo1
var_dump($foo1->getDescription()); // null, but we want it, cause it's defined in PARTIAL 2nd query
```

You can use _steevanb\DoctrineEntityMerger\QueryHint::MERGE_ENTITY_ to define description in _$foo1_ :
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

[Changelog](changelog.md)

Installation
------------

As _doctrine-entity-merger_ use _steevanb/doctrine-events_, see how to install it
(composer dependecy is added here, you don't need to add it for steevanb/doctrine-events) :

[steevanb/doctrine-events](https://github.com/steevanb/doctrine-events)

Add it to your composer.json :
```yml
{
    "require": {
        "steevanb/doctrine-entity-merger": "^1.0.5",
    }
}
```

Add _EntityMergerSubscriber_ :
```php
$entityManager->getEventManager()->addEventSubscriber(
    new steevanb\DoctrineEntityMerger\EventSubscriber\EntityMergerSubscriber()
);
```

If you want to add MERGE_ENTITY hint to all of your queries, you can do this :

```php
$entityManager->getConfiguration()->setDefaultQueryHint(
    steevanb\DoctrineEntityMerger\QueryHint\QueryHint::MERGE_ENTITY,
    true
);
```

For example, if you are on a Symfony project, you can add it in _AppKernel_ :
```php
# app/AppKernel.php

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineEntityMerger\QueryHint;
use steevanb\DoctrineEntityMerger\EventSubscriber\EntityMergerSubscriber;

class AppKernel
{
    public function boot()
    {
        parent::boot();
        
        foreach ($this->getContainer()->get('doctrine')->getManagers() as $manager) {
            if ($manager instanceof EntityManagerInterface) {
                // add hint MERGE_ENTITY to all your queries
                $manager->getConfiguration()->setDefaultQueryHint(QueryHint::MERGE_ENTITY, true);

                // add listener, who use steevanb/doctrine-events to change UnitOfWork::createEntity()
                // to take into account MERGE_ENTITY hint
                $manager->getEventManager()->addEventSubscriber(new EntityMergerSubscriber());
            }
        }
    }
}
