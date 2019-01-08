<?php

declare(strict_types=1);

namespace herbie\plugin\markdown;

use Herbie\Config;
use Herbie\PluginInterface;
use Herbie\StringValue;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;

class MarkdownPlugin implements PluginInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * MarkdownPlugin constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        // add twig function / filter
        if ((bool)$this->config->get('plugins.config.markdown.twig', false)) {
            $events->attach('onTwigInitialized', [$this, 'onTwigInitialized'], $priority);
        }

        // add shortcode
        if ((bool)$this->config->get('plugins.config.markdown.shortcode', true)) {
            $events->attach('onShortcodeInitialized', [$this, 'onShortcodeInitialized'], $priority);
        }

        $events->attach('onRenderContent', [$this, 'onRenderContent'], $priority);
    }

    /**
     * @param EventInterface $event
     */
    public function onTwigInitialized(EventInterface $event)
    {
        /** @var Twig_Environment $twig */
        $twig = $event->getTarget();
        $options = ['is_safe' => ['html']];
        $twig->addFunction(
            new \Twig_SimpleFunction('markdown', [$this, 'parseMarkdown'], $options)
        );
        $twig->addFilter(
            new \Twig_SimpleFilter('markdown', [$this, 'parseMarkdown'], $options)
        );
    }

    /**
     * @param EventInterface $event
     * @throws \Exception
     */
    public function onRenderContent(EventInterface $event)
    {
        #echo __METHOD__ . "<br>";
        if (!in_array($event->getParam('format'), ['markdown', 'md'])) {
            return;
        }
        /** @var StringValue $stringValue */
        $stringValue = $event->getTarget();
        $parsed = $this->parseMarkdown($stringValue->get());
        $stringValue->set($parsed);
    }

    /**
     * @param EventInterface $event
     */
    public function onShortcodeInitialized(EventInterface $event)
    {
        /** @var \herbie\plugin\shortcode\classes\Shortcode $shortcode */
        $shortcode = $event->getTarget();
        $shortcode->add('markdown', [$this, 'markdownShortcode']);
    }

    /**
     * @param string $string
     * @return string
     * @throws \Exception
     */
    public function parseMarkdown(string $string): string
    {
        $parser = new \ParsedownExtra();
        $parser->setUrlsLinked(false);
        $html = $parser->text($string);
        return $html;
    }

    /**
     * @param mixed $attribs
     * @param string $content
     * @return string
     * @throws \Exception
     */
    public function markdownShortcode($attribs, string $content): string
    {
        return $this->parseMarkdown($content);
    }
}
