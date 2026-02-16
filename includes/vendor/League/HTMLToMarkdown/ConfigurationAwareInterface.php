<?php

declare(strict_types=1);

namespace AgentMark\Vendor\League\HTMLToMarkdown;

interface ConfigurationAwareInterface
{
    public function setConfig(Configuration $config): void;
}
