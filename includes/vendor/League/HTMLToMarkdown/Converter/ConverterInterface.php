<?php

declare(strict_types=1);

namespace AgentMark\Vendor\League\HTMLToMarkdown\Converter;

use AgentMark\Vendor\League\HTMLToMarkdown\ElementInterface;

interface ConverterInterface
{
    public function convert(ElementInterface $element): string;

    /**
     * @return string[]
     */
    public function getSupportedTags(): array;
}
