<?php
/**
 * This file is part of the Zemit Framework.
 *
 * (c) Zemit Team <contact@zemit.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Zemit\Modules\Cli\Tasks;

use DateTime;
use DOMElement;
use DOMDocument;
use DateTimeZone;
use Phalcon\Mvc\Model\Row;
use Phalcon\Cli\Console\Exception;
use Zemit\Console\AbstractTask;
use Zemit\Model\Services\Service\Posts;

/**
 * Zemit\Modules\Cli\Tasks\Sitemap
 *
 * @package Zemit\Modules\Cli\Tasks
 */
class SitemapTask extends AbstractTask
{
    /**
     * @Doc("Generate the sitemap.xml")
     */
    public function generate()
    {
        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = container('filesystem', [$this->basePath . DIRECTORY_SEPARATOR . 'public']);

        /** @var \Phalcon\Config $config */
        $config  = container('config');
        $baseUrl = $config->get('site')->url;

        $sitemap = $this->createSitemapDocument();
        $urlset  = $this->createUrlSet($sitemap);
        $urlset->appendChild($this->createBaseUrl($sitemap, $baseUrl));

        /** @var Posts $postsService */
        $postsService = container(Posts::class);

        $posts = $postsService->getPostsOrderedByKarma();
        $maxKarma = $postsService->getMostHighestPostsKarma();

        $modifiedAt = new DateTime('now', new DateTimeZone('UTC'));

        foreach ($posts as $post) {
            /** @var \Phalcon\Mvc\Model\Row $post */
            $postKarma = $post->offsetGet('karma') / ($maxKarma + 100);
            $priority  = $postKarma > 0.7 ? sprintf("%0.1f", $postKarma) : sprintf("%0.1f", $postKarma + 0.25);
            $postUrl   = $postsService->getPostUrl($post);

            $urlset->appendChild($this->createPostUrl($sitemap, $post, $modifiedAt, $priority, $postUrl));
        }

        $sitemap->appendChild($urlset);

        $xml = $sitemap->saveXML();

        if ($filesystem->has('sitemap.xml')) {
            $result = $filesystem->update('sitemap.xml', $xml . PHP_EOL);
        } else {
            $result = $filesystem->write('sitemap.xml', $xml . PHP_EOL);
        }

        if ($result) {
            $this->output('The sitemap.xml was successfully updated');
        } else {
            throw new Exception('Failed to update the sitemap.xml file');
        }
    }

    /**
     * Creates post url element.
     *
     * @param DOMDocument $sitemap
     * @param Row         $post
     * @param DateTime    $date
     * @param string      $priority
     * @param string      $postUrl
     *
     * @return DOMElement
     */
    protected function createPostUrl(DOMDocument $sitemap, Row $post, DateTime $date, $priority, $postUrl)
    {
        $date->setTimestamp($post->offsetGet('modified'));

        $url = $sitemap->createElement('url');

        $url->appendChild($sitemap->createElement('loc', $postUrl));
        $url->appendChild($sitemap->createElement('lastmod', $date->format('Y-m-d\TH:i:s\Z')));
        $url->appendChild($sitemap->createElement('priority', $priority));

        return $url;
    }

    /**
     * Creates 'url' element.
     *
     * @param DOMDocument $sitemap
     * @param string      $baseUrl
     *
     * @return DOMElement
     */
    protected function createBaseUrl(DOMDocument $sitemap, $baseUrl)
    {
        $url = $sitemap->createElement('url');

        $url->appendChild($sitemap->createElement('loc', $baseUrl));
        $url->appendChild($sitemap->createElement('changefreq', 'daily'));
        $url->appendChild($sitemap->createElement('priority', '1.0'));

        return $url;
    }

    /**
     * Creates 'urlset' element.
     *
     * @param  DOMDocument $sitemap
     * @return DOMElement
     */
    protected function createUrlSet(DOMDocument $sitemap)
    {
        $urlset = $sitemap->createElement('urlset');

        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        return $urlset;
    }

    /**
     * Creates sitemap document.
     *
     * @return DOMDocument
     */
    protected function createSitemapDocument()
    {
        $sitemap = new DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;

        return $sitemap;
    }
}
