<?php

namespace Dock\Installer\DNS\Mac;

use Dock\Dinghy\DinghyCli;
use Dock\Installer\InstallerTask;
use Dock\IO\PharFileExtractor;
use Dock\IO\ProcessRunner;
use Dock\IO\UserInteraction;
use SRIO\ChainOfResponsibility\DependentChainProcessInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerRouting extends InstallerTask implements DependentChainProcessInterface
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;
    /**
     * @var UserInteraction
     */
    private $userInteraction;
    /**
     * @var DinghyCli
     */
    private $dinghy;
    /**
     * @var PharFileExtractor
     */
    private $fileExtractor;

    /**
     * @param DinghyCli $dinghy
     * @param UserInteraction $userInteraction
     * @param ProcessRunner $processRunner
     * @param PharFileExtractor $fileExtractor
     */
    public function __construct(DinghyCli $dinghy, UserInteraction $userInteraction, ProcessRunner $processRunner, PharFileExtractor $fileExtractor)
    {
        $this->dinghy = $dinghy;
        $this->userInteraction = $userInteraction;
        $this->processRunner = $processRunner;
        $this->fileExtractor = $fileExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->userInteraction->writeTitle('Configure routing for direct Docker containers access');

        $dinghyIp = $this->dinghy->getIp();

        $this->configureRouting($dinghyIp);
        $this->addPermanentRouting($dinghyIp);
    }

    /**
     * {@inheritdoc}
     */
    public function dependsOn()
    {
        return ['dinghy'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'routing';
    }

    /**
     * @param string $dinghyIp
     * @throws ProcessFailedException
     */
    private function configureRouting($dinghyIp)
    {
        try {
            $this->processRunner->run(sprintf('sudo route -n add 172.17.0.0/16 %s', $dinghyIp));
        } catch (ProcessFailedException $e) {
            if (strpos($e->getProcess()->getErrorOutput(), 'File exists') !== false) {
                $this->userInteraction->writeTitle('Routing already configured');

                return;
            }

            throw $e;
        }
    }

    /**
     * @param string $dinghyIp
     */
    private function addPermanentRouting($dinghyIp)
    {
        if (file_exists('/Library/LaunchDaemons/com.docker.route.plist')) {
            return;
        }

        $filePath = $this->fileExtractor->extract(__DIR__.'/fixtures/com.docker.route.plist');

        // Replace the Dinghy IP
        file_put_contents($filePath, str_replace('__DINGHY_IP__', $dinghyIp, file_get_contents($filePath)));

        $this->processRunner->run(sprintf('sudo cp %s /Library/LaunchDaemons/com.docker.route.plist', $filePath));
        $this->processRunner->run('sudo launchctl load /Library/LaunchDaemons/com.docker.route.plist');
    }
}