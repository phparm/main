<?php

declare(strict_types=1);

namespace Bin\Config\MonorepoBuilder;

use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use PharIo\Version\Version;
use Symplify\MonorepoBuilder\Release\Contract\ReleaseWorker\ReleaseWorkerInterface;
use Symplify\SmartFileSystem\SmartFileSystem;

class AddTagToChangelogReleaseWorker implements ReleaseWorkerInterface
{
    public function __construct(
        private SmartFileSystem $smartFileSystem
    ) {
    }

    public function work(Version $version): void
    {
        $changelogFilePath = getcwd() . '/CHANGELOG.md';
        if (! file_exists($changelogFilePath)) {
            $changelogFilePath = getcwd() . '/changelog.md';
        }
        if (! file_exists($changelogFilePath)) {
            return;
        }

        $theDate = date('Y-m-d');
        $dateHeader = "## {$theDate}";
        $theVersion = $this->generateVersion($version);

        $changelogFileContent = $this->smartFileSystem->readFile($changelogFilePath);

        $content = str_replace('### {{version}}', "### {$theVersion}", $changelogFileContent);

        // 检查是否已存在今天的日期
        if (strpos($content, $dateHeader) !== false) {
            // 匹配 ## {{date}} 到下一个 ## 或文件结尾
            if (preg_match('/## \{\{date\}\}\n(.*?)(?=^## |\z)/ms', $content, $matches)) {
                $block = $matches[0];
                $blockContent = $matches[1];

                // 删除原有的 ## {{date}} 区块
                $content = str_replace($block, '', $content);

                // 找到今天日期的位置
                $pos = strpos($content, $dateHeader);
                if ($pos !== false) {
                    // 找到日期标题后的位置
                    $insertPos = $pos + strlen($dateHeader);
                    // 插入 blockContent 到日期标题下方
                    $content = substr_replace($content, "\n" . trim($blockContent) . "\n", $insertPos, 0);
                }
            }
        } else {
            // 替换占位符
            $content = str_replace('## {{date}}', $dateHeader, $content);
        }

        $this->smartFileSystem->dumpFile($changelogFilePath, $content);
    }

    public function getDescription(Version $version): string
    {
        $newHeadline = $this->generateVersion($version);

        return sprintf('Change "Unreleased" in `CHANGELOG.md` to "%s"', $newHeadline);
    }

    private function generateVersion(Version $version): string
    {
        return 'v'.$version->getVersionString();
    }
}
