<?php


use League\CommonMark\ElementRendererInterface;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\HtmlInline;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

final class HtmlDumpRenderer implements InlineRendererInterface
{
    /**
     * @param HtmlInline $inline
     * @param ElementRendererInterface $htmlRenderer
     *
     * @return string
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer)
    {
        if (!($inline instanceof HtmlDump)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . \get_class($inline));
        }

        return $inline->getContent();
    }
}
