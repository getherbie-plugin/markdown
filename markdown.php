<?php

namespace herbie\plugin\markdown;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;

class MarkdownPlugin extends \Herbie\Plugin
{

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $config = $this->herbie->getConfig();

        // add twig function / filter
        if ((bool)$config->get('plugins.config.markdown.twig', false)) {
            $events->attach('twigInitialized', [$this, 'onTwigInitialized'], $priority);
        }

        // add shortcode
        if ((bool)$config->get('plugins.config.markdown.shortcode', true)) {
            $events->attach('shortcodeInitialized', [$this, 'addShortcode'], $priority);
        }

        $events->attach('renderContent', [$this, 'onRenderContent'], $priority);
    }

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

    public function onRenderContent(EventInterface $event)
    {
        if (!in_array($event->getParam('format'), ['markdown', 'md'])) {
            return;
        }
        $content = $event->getTarget();
        $parsed = $this->parseMarkdown($content);
        $content->set($parsed);
    }

    public function addShortcode(EventInterface $event)
    {
        /** @var \herbie\plugin\shortcode\classes\Shortcode $shortcode */
        $shortcode = $event->getTarget();
        $shortcode->add('markdown', [$this, 'markdownShortcode']);
    }

    public function parseMarkdown($value)
    {
        $parser = new \ParsedownExtra();
        $parser->setUrlsLinked(false);
        $html = $parser->text($value);
        return $html;
    }

    public function markdownShortcode($attribs, $content)
    {
        return $this->parseMarkdown($content);
    }
}
