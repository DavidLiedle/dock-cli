<?php

namespace Dock\Installer\System\Linux\RedHat;

use Dock\Installer\InstallContext;
use Dock\Installer\InstallerTask;
use SRIO\ChainOfResponsibility\DependentChainProcessInterface;
use Symfony\Component\Process\Process;

class NoSudo extends InstallerTask implements DependentChainProcessInterface
{
    /**
     * {@inheritdoc}
     */
    public function dependsOn()
    {
        return ['docker'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'noSudo';
    }

    public function run(InstallContext $context)
    {
        $processRunner = $context->getProcessRunner();

        if (! $processRunner->run('groups | grep dockerroot', false)->isSuccessful()) {
            $userInteraction = $context->getUserInteraction();
            $userInteraction->writeTitle('Making docker work without sudo');

            $processRunner->run('sudo usermod -a -G dockerroot $USER');
        }
    }
}
