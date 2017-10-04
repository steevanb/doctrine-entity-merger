<?php

namespace steevanb\DoctrineEntityMerger\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use steevanb\DoctrineEntityMerger\QueryHint;
use steevanb\DoctrineEvents\Doctrine\ORM\Event\OnCreateEntityDefineFieldValuesEventArgs;
use steevanb\DoctrineEvents\Doctrine\ORM\Event\OnCreateEntityOverrideLocalValuesEventArgs;

class EntityMergerSubscriber implements EventSubscriber
{
    /** @var array */
    protected $definedFieldValues = [];

    /** @return array */
    public function getSubscribedEvents()
    {
        return [
            OnCreateEntityOverrideLocalValuesEventArgs::EVENT_NAME,
            OnCreateEntityDefineFieldValuesEventArgs::EVENT_NAME,
            Events::onClear
        ];
    }

    /** @param OnCreateEntityOverrideLocalValuesEventArgs $eventArgs */
    public function onCreateEntityOverrideLocalValues(OnCreateEntityOverrideLocalValuesEventArgs $eventArgs)
    {
        if ($this->haveMergeEntityHint($eventArgs->getHints())) {
            $eventArgs->setOverrideLocalValues(true);
        }
    }

    /** @param OnCreateEntityDefineFieldValuesEventArgs $eventArgs */
    public function onCreateEntityDefineFieldValues(OnCreateEntityDefineFieldValuesEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getEntityManager()->getClassMetadata($eventArgs->getClassName());
        $entityHash = spl_object_hash($eventArgs->getEntity());

        if ($this->haveMergeEntityHint($eventArgs->getHints())) {
            foreach ($eventArgs->getData() as $field => $value) {
                if (
                    isset($classMetadata->fieldMappings[$field])
                    && isset($this->definedFieldValues[$entityHash][$field]) === false
                ) {
                    $classMetadata->reflFields[$field]->setValue($eventArgs->getEntity(), $value);
                    $this->definedFieldValues[$entityHash][$field] = true;
                }
            }
        }

        if (array_key_exists($entityHash, $this->definedFieldValues)) {
            foreach (array_keys($this->definedFieldValues[$entityHash]) as $field) {
                $eventArgs->addDefinedFieldValue($field);
            }
        }
    }

    public function onClear()
    {
        $this->definedFieldValues = [];
    }

    /**
     * @param array $hints
     * @return bool
     */
    protected function haveMergeEntityHint(array $hints)
    {
        return isset($hints[QueryHint::MERGE_ENTITY]);
    }
}
