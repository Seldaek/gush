<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwitchBaseCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:switch')
            ->setDescription('Switch the base of the PR to another one')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to be switched')
            ->addArgument('base_branch', InputArgument::REQUIRED, 'Name of the new base branch to switch the PR to', 'master')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getArgument('org');
        $repo = $input->getArgument('repo');
        $prNumber = $input->getArgument('pr_number');
        $baseBranch = $input->getArgument('base_branch');

        // squash to only cherry-pick once
        $command = $this->getApplication()->find('pull-request:squash');
        $input = new ArrayInput(['pr_number' => $prNumber]);
        $command->run($input, $output);

        // get old base and sha1 from old PR
        $client = $this->getGithubClient();
        $pr = $client->api('issue')->show($org, $repo, $prNumber);
        $oldBase = $pr['base'];
        $commitSha1 = $pr['head']['sha'];

        // close PR
        $command = $this->getApplication()->find('issue:close');
        $input = new ArrayInput(['issue_number' => $prNumber]);
        $command->run($input, $output);

        // save current branchName and append switch string
        // git checkout a clean `newbase` based branch
        // git remote update
        // git checkout origin/newBase
        // git checkout -b useSavedBranchNameAppendedWithSwitchStringHere
        // git cherry-pick $commitSha1
        // git push -u origin branchName

        // run gsh pull-request:table (opening new PR on new branch)

        $commands = [
            [
                'line' => sprintf('git checkout %s/%s', 'origin', $baseBranch),
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git cherry-pick')
            ]

        ];

        $this->runCommands($commands);
    }
}
