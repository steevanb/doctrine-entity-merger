# doctrine-entity-merger
Add hint MERGE_ENTITY to merge fields retrieved by many queries

Since Doctrine 2.5, you can call it :

```php
use steevanb\DoctrineEntityMerger\QueryHint;
$entityManager->getConfiguration()->setDefaultQueryHint(QueryHint::MERGE_ENTITY, true);
