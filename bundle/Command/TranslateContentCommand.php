<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use \Exception;
use EzSystems\EzPlatformAutomatedTranslationBundle\Entity\AutoTranslationActions;
use EzSystems\EzPlatformAutomatedTranslationBundle\Handler\AutoTranslationActionsHandler;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use EzSystems\EzPlatformAutomatedTranslation\ClientProvider;
use EzSystems\EzPlatformAutomatedTranslation\Translator;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Pagerfanta\Doctrine\DBAL\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ibexa\Migration\Log\LoggerAwareTrait;
use RuntimeException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Process\Process;
use Pagerfanta\Adapter\CallbackAdapter;

final class TranslateContentCommand extends Command
{
    use LockableTrait;
    use LoggerAwareTrait;

    private const ADMINISTRATOR_USER_ID = 14;

    protected EntityManagerInterface $em;
    protected Translator $translator;
    protected ClientProvider $clientProvider;
    protected ContentService $contentService;
    protected LocationService $locationService;
    protected PermissionResolver $permissionResolver;
    protected UserService $userService;
    protected Repository $repository;
    protected AutoTranslationActionsHandler $handler;

    public function __construct(
        EntityManagerInterface $em,
        Translator $translator,
        ClientProvider $clientProvider,
        ContentService $contentService,
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        Repository $repository,
        AutoTranslationActionsHandler $handler,
        ?LoggerInterface $logger = null
    ) {
        $this->em = $em;
        $this->clientProvider = $clientProvider;
        $this->translator = $translator;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->repository = $repository;
        $this->handler = $handler;
        $this->logger = $logger ?? new NullLogger();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ezplatform:automated:translate')
            ->setAliases(['eztranslate'])
            ->setDescription('Translate a Content in a new Language')
            ->addArgument(
                'service',
                InputArgument::REQUIRED,
                'Remote Service for Translation. <comment>[' .
                implode(' ', array_keys($this->clientProvider->getClients())) . ']</comment>'
            )
            ->addArgument('contentId', InputArgument::OPTIONAL, 'ContentId')
            ->addOption('from', '--from', InputOption::VALUE_OPTIONAL, 'Source Language Code')
            ->addOption('to', '--to', InputOption::VALUE_OPTIONAL, 'Target Language Code')
            ->addOption('user', '--user', InputOption::VALUE_OPTIONAL, 'The user id to publish new version with.')
            ->addOption('overwrite', '--overwrite', InputOption::VALUE_NONE, 'Overwrites existing translations')
            ->addOption('sudo', '--sudo', InputOption::VALUE_NONE, 'Force publication with admin user.')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmdStatus = Command::SUCCESS;
        $logMessage = '';
        if ($input->hasArgument('contentId') && (int)$input->getArgument('contentId')) {
            $this->subExecute($input, $output);
        } else {
            if (!$this->lock()) {
                $output->writeln('The command ezplatform:automated:translate is already running in another process.');
                return 0;
            }
            $action = $this->handler->getFirstPendingAction();

            $message = sprintf('Start the automated translate.');
            $this->getLogger()->info($message);
            $output->writeln($message);
            if(empty($action)){
                $message = sprintf('There is no pending action.');
                $this->getLogger()->info($message);
                $output->writeln($message);
            }
            if (!empty($action)) {
                unset(
                    $action['created_at'],
                    $action['finished_at'],
                    $action['content_name'],
                    $action['user_name'],
                    $action['user_name'],
                    $action['content_id']
                );
                [
                    $actionId,
                    $subtreeId,
                    $userId,
                    $targetLanguage,
                    $overwrite
                ] = array_values($action);

                $overwrite = (bool) $overwrite;
                /** @var  AutoTranslationActions $autoTranslationActions */
                $autoTranslationActions = $this->em->getRepository(AutoTranslationActions::class)->find($actionId);
                $autoTranslationActions->setStatus(AutoTranslationActions::STATUS_IN_PROGRESS);
                $this->em->flush($autoTranslationActions);
                try {
                    $subtreeLocation = $this->locationService->loadLocation($subtreeId);

                    $message = sprintf('Start Translation of subtree  %s.', $subtreeLocation->pathString);
                    $this->getLogger()->info($message);
                    $output->writeln($message);
                    $logMessage .= $message .'</br>';

                    $adapter = new CallbackAdapter(
                        function () use ($subtreeLocation): int {
                            return $this->handler->countContentWithRelationsInSubtree($subtreeLocation->pathString);
                        },
                        function (int $offset, int $limit) use ($subtreeLocation): iterable {
                            return $this->handler->getContentsWithRelationsInSubtree($subtreeLocation->pathString, $offset, $limit);
                        }
                    );
                    $currentPage = 1;
                    $maxPerPage = 1;
                    $pager = new Pagerfanta(
                        $adapter
                    );
                    $pager->setMaxPerPage($maxPerPage);
                    $pager->setCurrentPage($currentPage);
                    $i = 0;
                    $message = sprintf('Translate "%s" contents.', $pager->count());
                    $this->getLogger()->info($message);
                    $output->writeln($message);
                    $logMessage .= $message .'</br>';

                    $progressBar = new ProgressBar($output, $pager->count());

                    if ($pager->count() > 0) {
                        do {
                            $i++;
                            $adapter = new CallbackAdapter(
                                function () use ($subtreeLocation): int {
                                    return $this->handler->countContentWithRelationsInSubtree($subtreeLocation->pathString);
                                },
                                function (int $offset, int $limit) use ($subtreeLocation): iterable {
                                    return $this->handler->getContentsWithRelationsInSubtree($subtreeLocation->pathString, $offset, $limit);
                                }
                            );
                            $pager = new Pagerfanta(
                                $adapter
                            );
                            $pager->setMaxPerPage($maxPerPage);
                            $pager->setCurrentPage($currentPage);
                            $contentIds = [];
                            /** @var Content $content */
                            foreach ($pager->getCurrentPageResults() as $result) {
                                $contentIds[] = $result['contentId'];
                            }
                            $processes = $this->getPhpProcess(
                                $contentIds[0],
                                $targetLanguage,
                                $userId,
                                $overwrite,
                                $input
                            );
                            $processes->run();
                            $processes->getErrorOutput();
                            $logMessage .= $processes->getOutput() .'</br>';
                            if (!empty($processes->getErrorOutput())) {
                                $message = $processes->getErrorOutput();
                                $this->getLogger()->info($message);
                                $logMessage .= $message .'</br>';
                            } else {
                                $logMessage .= $processes->getOutput() .'</br>';
                            }
                            $currentPage++;
                            $progressBar->advance($maxPerPage);
                        } while ($pager->hasNextPage() && $i < 2000);
                        $progressBar->finish();
                    }
                    // clear leftover progress bar parts
                    $progressBar->clear();
                } catch (Exception $e) {
                    $logMessage = $e->getMessage();
                    $this->logException($e);
                    $cmdStatus = Command::FAILURE;
                }

                $autoTranslationActions->setStatus($cmdStatus === Command::FAILURE ?
                    AutoTranslationActions::STATUS_FAILED : AutoTranslationActions::STATUS_FINISHED
                );
                $autoTranslationActions->setLogMessage($logMessage);
                $autoTranslationActions->setFinishedAt(new \DateTime());
                $this->em->flush($autoTranslationActions);
            }
            $output->writeln('');
            $output->writeln('Finished');
            $this->getLogger()->info('Finished');
            $output->writeln('');
        }

        return $cmdStatus;
    }
    protected function subExecute(InputInterface $input, OutputInterface $output): int
    {
        $contentId = (int) $input->getArgument('contentId');
        $languageCodeFrom = $input->getOption('from');
        $languageCodeTo =  $input->getOption('to');
        $serviceApi = $input->getArgument('service');
        $overwrite = $input->getOption('overwrite');

        $status = Command::SUCCESS;
        if ($input->getOption('sudo')) {
            $status = $this->permissionResolver->sudo(function () use (
                $contentId,
                $languageCodeFrom,
                $languageCodeTo,
                $serviceApi,
                $overwrite,
                $input,
                $output
            ) {
                try {
                    $this->publishVersion($contentId, $languageCodeFrom, $languageCodeTo, $serviceApi, $overwrite);
                    $this->logDoneMessage($contentId, $languageCodeFrom, $languageCodeTo, $output);
                    return Command::SUCCESS;
                } catch (Exception $e) {
                    $this->logFailedMessage($contentId, $e);
                    return Command::FAILURE;
                }
            }, $this->repository);
        } else {
            try {
                $this->publishVersion($contentId, $languageCodeFrom, $languageCodeTo, $serviceApi, $overwrite);
                $this->logDoneMessage($contentId, $languageCodeFrom, $languageCodeTo, $output);
            } catch (\Throwable $e) {
                $this->logFailedMessage($contentId, $e);
                $status = Command::FAILURE;
            }
        }

        return $status;
    }

    /**
     * @throws BadStateException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     */
    protected function publishVersion(
        int $contentId,
        ?string &$languageCodeFrom,
        string $languageCodeTo,
        string $serviceApi,
        bool $overwrite = false
    ):  array {
        $content = $this->contentService->loadContent($contentId);
        $languageCodeFrom = $languageCodeFrom ?? $content->contentInfo->mainLanguageCode;

        if($languageCodeFrom == $languageCodeTo) {
            $message = sprintf('The target language from argument --to=%s must be different from the source language --from and the main language of the content %s .',
                $languageCodeTo,
                $languageCodeFrom,
            );
            throw new InvalidArgumentException('--from', $message);
        }

        if(!$overwrite && in_array($languageCodeTo,$content->getVersionInfo()->languageCodes)) {
            $message = sprintf('The content %d already translated into language --to=%s . use the --overwrite option. ',
                $contentId,
                $languageCodeTo
            );
            throw new InvalidArgumentException('--to', $message);
        }

        $draft = $this->translator->getTranslatedContent(
            $languageCodeFrom,
            $languageCodeTo,
            $serviceApi,
            $content
        );
        $newContentVersion = $this->contentService->publishVersion($draft->versionInfo);

        return [$content, $newContentVersion];
    }

    protected function logDoneMessage(
        int $contentId,
        string $languageCodeFrom,
        string $languageCodeTo,
        OutputInterface $output
    ): void
    {
        $message = sprintf(
            'Translation of content %d from %s to %s Done.',
            $contentId,
            $languageCodeFrom,
            $languageCodeTo
        );
        $this->getLogger()->info($message);
        $output->writeln($message);
    }
   protected function logFailedMessage(int $contentId, Exception $e): void
   {
       $message = sprintf(
           'Translation to %d Failed',
           $contentId
       );
       $this->logException($e, $message);
   }
    protected function logException(Exception $e, string $message = ''): void
    {
        $message = sprintf(
            '%s. %s',
            $message,
            $e->getMessage(),
        );
        $exception = new RuntimeException($message, $e->getCode(), $e);
        $this->getLogger()->error($message, [
            'exception' =>  $exception,
        ]);
    }
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $userId = (int) ($input->getOption('user') ?? self::ADMINISTRATOR_USER_ID);

        $this->permissionResolver->setCurrentUserReference(
            $this->userService->loadUser($userId)
        );
    }

    private function getPhpProcess(int $contentId, string $targetLanguage, int $userId, bool $overwrite, InputInterface $input): Process
    {
        $env = $input->getParameterOption(['--env', '-e'], getenv('APP_ENV') ?: 'dev', true);
        $serviceApi = $input->getArgument('service');
        $sudo = $input->getOption('sudo');

        $subProcessArgs = [
            'php',
            'bin/console',
            $this->getName(),
            $serviceApi,
            $contentId,
            '--user=' . $userId,
            '--to=' . $targetLanguage,
            '--env=' . $env
        ];

        if ($overwrite) {
            $subProcessArgs[] = '--overwrite';
        }

        if ($sudo) {
            $subProcessArgs[] = '--sudo';
        }

        $process = new Process($subProcessArgs);
        $process->setTimeout(null);

        return $process;
    }
}
