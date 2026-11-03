<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Markdown;

use League\CommonMark\CommonMarkConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Twig\Extra\Markdown\LeagueMarkdown;
use Webmozart\Assert\Assert;

/**
 * Ensures the CommonMark configuration used by the "markdown_to_html" Twig filter
 * does not render untrusted input (e.g. changelogs from pull request bodies) as HTML.
 */
final class MarkdownToHtmlTest extends TestCase
{
    #[DataProvider('provideUnsafeMarkdownIsNotRenderedAsHtmlCases')]
    public function testUnsafeMarkdownIsNotRenderedAsHtml(string $markdown, string $forbidden): void
    {
        static::assertStringNotContainsString($forbidden, $this->render($markdown));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideUnsafeMarkdownIsNotRenderedAsHtmlCases(): iterable
    {
        yield 'raw script tag' => ['- Fixed <script>alert(1)</script>', '<script>'];
        yield 'raw html attribute' => ['- Fixed <img src="x" onerror="alert(1)">', '<img'];
        yield 'javascript link' => ['- [click](javascript:alert(1))', 'javascript:'];
        yield 'data uri image' => ['- ![x](data:text/html;base64,PHNjcmlwdD4=)', 'data:text/html'];
    }

    public function testRegularMarkdownIsRendered(): void
    {
        static::assertSame(
            "<h3>Fixed</h3>\n<ul>\n<li>foo <code>bar</code> <a href=\"https://example.com\">link</a></li>\n</ul>\n",
            $this->render("### Fixed\n- foo `bar` [link](https://example.com)")
        );
    }

    private function render(string $markdown): string
    {
        $config = Yaml::parseFile(__DIR__.'/../../../config/packages/twig_extra.yaml');
        Assert::isMap($config);
        Assert::isMap($config['twig_extra'] ?? null);
        Assert::isMap($config['twig_extra']['commonmark'] ?? null);

        return new LeagueMarkdown(new CommonMarkConverter($config['twig_extra']['commonmark']))->convert($markdown);
    }
}
