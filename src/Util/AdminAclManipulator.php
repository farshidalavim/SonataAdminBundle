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

namespace Sonata\AdminBundle\Util;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\AclSecurityHandlerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;

/**
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AdminAclManipulator implements AdminAclManipulatorInterface
{
    /**
     * @var string
     */
    protected $maskBuilderClass;

    /**
     * @param string $maskBuilderClass
     */
    public function __construct($maskBuilderClass)
    {
        $this->maskBuilderClass = $maskBuilderClass;
    }

    public function configureAcls(OutputInterface $output, AdminInterface $admin)
    {
        $securityHandler = $admin->getSecurityHandler();
        if (!$securityHandler instanceof AclSecurityHandlerInterface) {
            $output->writeln(sprintf('Admin `%s` is not configured to use ACL : <info>ignoring</info>', $admin->getCode()));

            return;
        }

        $objectIdentity = ObjectIdentity::fromDomainObject($admin);
        $newAcl = false;
        if (null === ($acl = $securityHandler->getObjectAcl($objectIdentity))) {
            $acl = $securityHandler->createAcl($objectIdentity);
            $newAcl = true;
        }

        // create admin ACL
        $output->writeln(sprintf(' > install ACL for %s', $admin->getCode()));
        $configResult = $this->addAdminClassAces($output, $acl, $securityHandler, $securityHandler->buildSecurityInformation($admin));

        if ($configResult) {
            $securityHandler->updateAcl($acl);
        } else {
            $output->writeln(sprintf('   - %s , no roles and permissions found', ($newAcl ? 'skip' : 'removed')));
            $securityHandler->deleteAcl($objectIdentity);
        }
    }

    public function addAdminClassAces(
        OutputInterface $output,
        AclInterface $acl,
        AclSecurityHandlerInterface $securityHandler,
        array $roleInformation = []
    ) {
        if (\count($securityHandler->getAdminPermissions()) > 0) {
            $builder = new $this->maskBuilderClass();

            foreach ($roleInformation as $role => $permissions) {
                $aceIndex = $securityHandler->findClassAceIndexByRole($acl, $role);
                $roleAdminPermissions = [];

                foreach ($permissions as $permission) {
                    // add only the admin permissions
                    if (\in_array($permission, $securityHandler->getAdminPermissions(), true)) {
                        $builder->add($permission);
                        $roleAdminPermissions[] = $permission;
                    }
                }

                if (\count($roleAdminPermissions) > 0) {
                    if (false === $aceIndex) {
                        $acl->insertClassAce(new RoleSecurityIdentity($role), $builder->get());
                        $action = 'add';
                    } else {
                        $acl->updateClassAce($aceIndex, $builder->get());
                        $action = 'update';
                    }

                    if (null !== $output) {
                        $output->writeln(sprintf('   - %s role: %s, permissions: %s', $action, $role, json_encode($roleAdminPermissions)));
                    }

                    $builder->reset();
                } elseif (false !== $aceIndex) {
                    $acl->deleteClassAce($aceIndex);

                    if (null !== $output) {
                        $output->writeln(sprintf('   - remove role: %s', $role));
                    }
                }
            }

            return true;
        }

        return false;
    }
}
