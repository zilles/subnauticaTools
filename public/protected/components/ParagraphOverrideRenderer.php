<?php


use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Block\Renderer\BlockRendererInterface;

final class ParagraphOverrideRenderer implements BlockRendererInterface
{
    /**
     * @param Paragraph $block
     * @param ElementRendererInterface $htmlRenderer
     * @param bool $inTightList
     *
     * @return HtmlElement|string
     */
    public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
    {
        if (!($block instanceof Paragraph)) {
            throw new \InvalidArgumentException('Incompatible block type: ' . \get_class($block));
        }

        if ($inTightList) {
            return $htmlRenderer->renderInlines($block->children());
        }

        $attrs = $block->getData('attributes', []);
        $attrs['class'] = "paragraph";

        return new HtmlElement('div', $attrs, $htmlRenderer->renderInlines($block->children()));
    }
}
