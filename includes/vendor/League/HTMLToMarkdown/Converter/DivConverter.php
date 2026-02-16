<?php

declare(strict_types=1);

namespace AgentMark\Vendor\League\HTMLToMarkdown\Converter;

use AgentMark\Vendor\League\HTMLToMarkdown\Configuration;
use AgentMark\Vendor\League\HTMLToMarkdown\ConfigurationAwareInterface;
use AgentMark\Vendor\League\HTMLToMarkdown\ElementInterface;

class DivConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    public function convert(ElementInterface $element): string
    {
        if ($this->config->getOption('strip_tags', false)) {
            return $element->getValue() . "\n\n";
        }

        return \html_entity_decode($element->getChildrenAsString());
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['div'];
    }
}
