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

namespace Sonata\AdminBundle\Manipulator;

use Symfony\Component\Yaml\Yaml;

/**
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Marek Stipek <mario.dweller@seznam.cz>
 * @author Simon Cosandey <simon.cosandey@simseo.ch>
 */
class ServicesManipulator
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $template = '    %s:
        class: %s
        arguments: [~, %s, %s]
        tags:
            - { name: sonata.admin, manager_type: %s, group: admin, label: %s }
        public: true
';

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = (string) $file;
    }

    /**
     * @param string $serviceId
     * @param string $modelClass
     * @param string $adminClass
     * @param string $controllerName
     * @param string $managerType
     *
     * @throws \RuntimeException
     */
    public function addResource($serviceId, $modelClass, $adminClass, $controllerName, $managerType)
    {
        $code = "services:\n";

        if (is_file($this->file)) {
            $code = rtrim(file_get_contents($this->file));
            $data = (array) Yaml::parse($code);

            if ('' !== $code) {
                $code .= "\n";
            }

            if (\array_key_exists('services', $data)) {
                if (\array_key_exists($serviceId, (array) $data['services'])) {
                    throw new \RuntimeException(sprintf(
                        'The service "%s" is already defined in the file "%s".',
                        $serviceId,
                        realpath($this->file)
                    ));
                }

                if (null !== $data['services']) {
                    $code .= "\n";
                }
            } else {
                $code .= '' === $code ? '' : "\n"."services:\n";
            }
        }

        $code .= sprintf(
            $this->template,
            $serviceId,
            $adminClass,
            $modelClass,
            $controllerName,
            $managerType,
            current(\array_slice(explode('\\', $modelClass), -1))
        );
        @mkdir(\dirname($this->file), 0777, true);

        if (false === @file_put_contents($this->file, $code)) {
            throw new \RuntimeException(sprintf(
                'Unable to append service "%s" to the file "%s". You will have to do it manually.',
                $serviceId,
                $this->file
            ));
        }
    }
}
