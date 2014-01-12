<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ReleaseListCommand extends BaseRepoCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('release:list')
            ->setDescription('List of the releases')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = $this->getGithubClient();
        list($org, $repo) = $this->getOrgAndRepo($input);

        $releases = $client->api('repo')->releases()->all($org, $repo);

        $table = $this->getHelper('table');
        $table->setHeaders(array('ID', 'Name', 'Tag', 'Commitish', 'Draft', 'Prerelease', 'Created', 'Published'));
        $table->formatRows($releases, $this->getRowBuilderCallback());
        $table->setFooter(sprintf('%s release(s)', count($releases)));
        $table->render($output, $table);
    }

    private function getRowBuilderCallback()
    {
        return function ($release) {
            return [
                $release['id'],
                $release['name'] ? : 'not set',
                $release['tag_name'],
                $release['target_commitish'],
                $release['draft'] ? 'yes': 'no',
                $release['prerelease'] ? 'yes' : 'no',
                $release['created_at'],
                $release['published_at'],
            ];
        };
    }
}
