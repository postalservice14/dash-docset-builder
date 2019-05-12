<?php

namespace App\Docsets;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Jigsaw extends BaseDocset
{
    const CODE = 'jigsaw';
    const NAME = 'Jigsaw';
    const URL = 'jigsaw.tighten.co';
    const INDEX = 'installation.html';
    const PLAYGROUND = '';
    const ICON_16 = 'favicon-16x16.png';
    const ICON_32 = 'favicon-32x32.png';
    const EXTERNAL_DOMAINS = [];


    public function entries(string $file): Collection
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $entries = collect();
        $entries = $entries->merge($this->guideEntries($crawler, $file));
        $entries = $entries->merge($this->sectionEntries($crawler, $file));

        return $entries;
    }

    protected function guideEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        $crawler->filter('h2')->each(function (HtmlPageCrawler $node) use ($entries, $file) {
            $fileBasename = basename($file);

            if ($fileBasename !== 'index.html') {
                $entries->push([
                    'name' => trim($node->text()),
                    'type' => 'Guide',
                    'path' => $fileBasename
                ]);
            }
        });

        return $entries;
    }

    protected function sectionEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        $parent = $crawler->filter('h4')->first()->text() ?: $crawler->filter('h2')->first()->text();

        $crawler->filter('h3')->each(function (HtmlPageCrawler $node) use ($entries, $file, $parent) {
            $fileBasename = basename($file);

            if ($fileBasename !== 'index.html') {
                $entries->push([
                    'name' => trim($node->text() . ' - ' . $parent),
                    'type' => 'Section',
                    'path' => basename($file) . '#' . Str::slug($node->text())
                ]);
            }
        });

        return $entries;
    }

    public function format(string $html): string
    {
        $crawler = HtmlPageCrawler::create($html);

        $this->removeHeader($crawler);
        $this->removeFooter($crawler);
        $this->updateTopPadding($crawler);
        $this->updateContainer($crawler);
        $this->updateTextSize($crawler);
        $this->updateH4Padding($crawler);
        $this->removeJavaScript($crawler);
        $this->insertDashTableOfContents($crawler);

        return $crawler->saveHTML();
    }

    protected function removeHeader(HtmlPageCrawler $crawler)
    {
        $crawler->filter('#vue-app > div:first-child')->remove();
        $crawler->filter('#vue-app > header')->remove();
    }

    protected function removeFooter(HtmlPageCrawler $crawler)
    {
        $crawler->filter('footer')->remove();
    }

    protected function updateTopPadding(HtmlPageCrawler $crawler)
    {
        $crawler->filter('#vue-app > div > div > div')
            ->removeClass('pt-4')
            ->css('margin-top', '-1.5rem')
        ;
    }

    protected function updateContainer(HtmlPageCrawler $crawler)
    {
        $crawler->filter('#vue-app > div')
            ->removeClass('pt-16')
            ->removeClass('md:pt-24')
            ->removeClass('lg:pt-32')
            ->removeClass('md:px-6')
        ;

        $crawler->filter('#vue-app > div > div')
            ->removeClass('max-w-3xl')
        ;

        $crawler->filter('div.markdown')
            ->removeClass('lg:max-w-md')
            ->removeClass('xl:max-w-lg')
            ->removeClass('md:mb-6')
            ->removeClass('lg:mb-10')
            ->removeClass('xl:px-10')
            ->removeClass('sm:shadow')
            ->removeClass('md:rounded-lg')
        ;
    }

    protected function updateTextSize(HtmlPageCrawler $crawler)
    {
        $crawler->filter('h2')
            ->addClass('text-3xl')
        ;

        $crawler->filter('h3')
            ->css('font-size', '1.5rem')
        ;
    }

    protected function updateH4Padding(HtmlPageCrawler $crawler)
    {
        $crawler->filter('h4')
            ->css('margin-top', '2.5rem')
        ;
    }

    protected function updateBottomPadding(HtmlPageCrawler $crawler)
    {
        $crawler->filter('section > div > div')
            ->removeClass('pb-16')
        ;
    }

    protected function removeJavaScript(HtmlPageCrawler $crawler)
    {
        $crawler->filter('script')->remove();
    }

    protected function insertDashTableOfContents(HtmlPageCrawler $crawler)
    {
        $crawler->filter('h2')
            ->before('<a name="//apple_ref/cpp/Section/Top" class="dashAnchor"></a>');

        $crawler->filter('h3')->each(function (HtmlPageCrawler $node) {
            $node->before(
                '<a id="' . Str::slug($node->text()) . '" name="//apple_ref/cpp/Section/' . rawurlencode($node->text()) . '" class="dashAnchor"></a>'
            );
        });
    }
}
