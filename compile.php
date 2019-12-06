#!/usr/bin/php
<?php
require __DIR__ . '/vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use Goutte\Client;

class Compile extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Download and compile book into one big HTML.');
        $options->registerArgument('url', 'URL of the book.', true);
    }

    protected function main(Options $options)
    {
        list($url) = $options->getArgs();

        $client = new Client();
        $crawler = $client->request('GET', $url);

        $html = implode(
            PHP_EOL,
            array_merge(
                [
                    '<h1>' . $crawler->filter('.entry-title')->first()->text() . '</h1>',
                    '<p>' . $crawler->filter('.entry-content > div')->first()->text() . '</p>',
                    $crawler->filter('.entry-content > div')->eq(1)->html()
                ],
                $crawler->filter('.entry-content li')->each(function ($node) {
                    return implode(
                        PHP_EOL,
                        $node->filter('li > strong > a, li > a')->each(function ($node) {
                            return implode(PHP_EOL, [
                                $node->parents()->first()->nodeName() == 'strong' ?
                                    '<h2>' . $node->text() . '</h2>' :
                                    '<h3>' . $node->text() . '</h3>',
                                (new Client())
                                    ->click($node->link())
                                    ->filter('.entry-content > div')
                                    ->first()
                                    ->html()
                            ]);
                        })
                    );
                })
            )
        );

        echo $html;
    }
}

$cli = new Compile();
$cli->run();
