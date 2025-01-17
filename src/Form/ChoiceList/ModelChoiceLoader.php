<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Form\ChoiceList;

use Doctrine\Common\Util\ClassUtils;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Sonata\Doctrine\Adapter\AdapterInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ModelChoiceLoader implements ChoiceLoaderInterface
{
    public $identifier;

    /**
     * @var \Sonata\AdminBundle\Model\ModelManagerInterface
     */
    private $modelManager;

    /**
     * @var string
     */
    private $class;

    private $property;

    private $query;

    private $choices;

    /**
     * @var PropertyPath
     */
    private $propertyPath;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    private $choiceList;

    /**
     * @param string      $class
     * @param string|null $property
     * @param mixed|null  $query
     * @param array       $choices
     */
    public function __construct(
        ModelManagerInterface $modelManager,
        $class,
        $property = null,
        $query = null,
        $choices = [],
        PropertyAccessorInterface $propertyAccessor = null
    ) {
        $this->modelManager = $modelManager;
        $this->class = $class;
        $this->property = $property;
        $this->query = $query;
        $this->choices = $choices;

        $this->identifier = $this->modelManager->getIdentifierFieldNames($this->class);

        // The property option defines, which property (path) is used for
        // displaying entities as strings
        if ($property) {
            $this->propertyPath = new PropertyPath($property);
            $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        }
    }

    public function loadChoiceList($value = null)
    {
        if (!$this->choiceList) {
            if ($this->query) {
                $entities = $this->modelManager->executeQuery($this->query);
            } elseif (\is_array($this->choices) && \count($this->choices) > 0) {
                $entities = $this->choices;
            } else {
                $entities = $this->modelManager->findBy($this->class);
            }

            $choices = [];
            foreach ($entities as $key => $entity) {
                if ($this->propertyPath) {
                    // If the property option was given, use it
                    $valueObject = $this->propertyAccessor->getValue($entity, $this->propertyPath);
                } else {
                    // Otherwise expect a __toString() method in the entity
                    try {
                        $valueObject = (string) $entity;
                    } catch (\Exception $e) {
                        throw new RuntimeException(sprintf('Unable to convert the entity "%s" to string, provide "property" option or implement "__toString()" method in your entity.', ClassUtils::getClass($entity)), 0, $e);
                    }
                }

                $id = implode(AdapterInterface::ID_SEPARATOR, $this->getIdentifierValues($entity));

                if (!\array_key_exists($valueObject, $choices)) {
                    $choices[$valueObject] = [];
                }

                $choices[$valueObject][] = $id;
            }

            $finalChoices = [];
            foreach ($choices as $valueObject => $idx) {
                if (\count($idx) > 1) { // avoid issue with identical values ...
                    foreach ($idx as $id) {
                        $finalChoices[sprintf('%s (id: %s)', $valueObject, $id)] = $id;
                    }
                } else {
                    $finalChoices[$valueObject] = current($idx);
                }
            }

            $this->choiceList = new ArrayChoiceList($finalChoices, $value);
        }

        return $this->choiceList;
    }

    public function loadChoicesForValues(array $values, $value = null)
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * @param object $entity
     */
    private function getIdentifierValues($entity): array
    {
        try {
            return $this->modelManager->getIdentifierValues($entity);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Unable to retrieve the identifier values for entity %s', ClassUtils::getClass($entity)), 0, $e);
        }
    }
}
