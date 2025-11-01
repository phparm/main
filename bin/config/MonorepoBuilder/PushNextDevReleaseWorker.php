<?php

declare(strict_types=1);

namespace Bin\Config\MonorepoBuilder;

use PharIo\Version\Version;
use Symplify\MonorepoBuilder\Release\Contract\ReleaseWorker\ReleaseWorkerInterface;
use Symplify\MonorepoBuilder\Release\Process\ProcessRunner;
use Symplify\MonorepoBuilder\Utils\VersionUtils;
use Symplify\MonorepoBuilder\ValueObject\Option;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

class PushNextDevReleaseWorker implements ReleaseWorkerInterface
{
    private string $branchName = 'main';

    public function __construct(
        private ProcessRunner $processRunner,
        private VersionUtils $versionUtils,
        ParameterProvider $parameterProvider
    ) {
    }

    public function work(Version $version): void
    {
        $versionInString = $this->getVersionDev($version);

        $gitAddCommitCommand = sprintf(
            'git add . && git commit --allow-empty -m "open %s" && git push origin "%s"',
            $versionInString,
            $this->branchName
        );

        $this->processRunner->run($gitAddCommitCommand);
    }

    public function getDescription(Version $version): string
    {
        $versionInString = $this->getVersionDev($version);

        return sprintf('Push "%s" open to remote repository', $versionInString);
    }

    private function getVersionDev(Version $version): string
    {
        return $this->versionUtils->getNextAliasFormat($version);
    }
}
