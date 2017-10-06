### [1.0.4](../../compare/1.0.3...1.0.4) - 2017-10-06

- Revert fix Doctrine issue [#6751](https://github.com/doctrine/doctrine2/issues/6751), PARTIAL with Collection have too much bugs to work

### [1.0.3](../../compare/1.0.2...1.0.3) - 2017-10-04

- Fix Doctrine issue [#6751](https://github.com/doctrine/doctrine2/issues/6751), by adding defaut query hints to query hints

### [1.0.2](../../compare/1.0.1...1.0.2) - 2017-10-04

- Fix _spl_object_hash()_ collision when _ObjectManager::clear()_ is called

### [1.0.1](../../compare/1.0.0...1.0.1) - 2017-09-21

- Call _$eventArgs->addDefinedValue()_ only when needed

### 1.0.0 - 2016-12-14

- Add steevanb\DoctrineEntityMerger\QueryHint::MERGE_ENTITY hint to merge PARTIAL queries
