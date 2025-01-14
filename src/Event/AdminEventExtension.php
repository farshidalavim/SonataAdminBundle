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

namespace Sonata\AdminBundle\Event;

use Sonata\AdminBundle\Admin\AbstractAdminExtension;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AdminEventExtension extends AbstractAdminExtension
{
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function configureFormFields(FormMapper $form)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.configure.form',
            new ConfigureEvent($form->getAdmin(), $form, ConfigureEvent::TYPE_FORM)
        );
    }

    public function configureListFields(ListMapper $list)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.configure.list',
            new ConfigureEvent($list->getAdmin(), $list, ConfigureEvent::TYPE_LIST)
        );
    }

    public function configureDatagridFilters(DatagridMapper $filter)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.configure.datagrid',
            new ConfigureEvent($filter->getAdmin(), $filter, ConfigureEvent::TYPE_DATAGRID)
        );
    }

    public function configureShowFields(ShowMapper $show)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.configure.show',
            new ConfigureEvent($show->getAdmin(), $show, ConfigureEvent::TYPE_SHOW)
        );
    }

    public function configureQuery(AdminInterface $admin, ProxyQueryInterface $query, $context = 'list')
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.configure.query',
            new ConfigureQueryEvent($admin, $query, $context)
        );
    }

    public function preUpdate(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.pre_update',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_UPDATE)
        );
    }

    public function postUpdate(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.post_update',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_UPDATE)
        );
    }

    public function prePersist(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.pre_persist',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_PERSIST)
        );
    }

    public function postPersist(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.post_persist',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_PERSIST)
        );
    }

    public function preRemove(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.pre_remove',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_REMOVE)
        );
    }

    public function postRemove(AdminInterface $admin, $object)
    {
        $this->eventDispatcher->dispatch(
            'sonata.admin.event.persistence.post_remove',
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_REMOVE)
        );
    }
}
