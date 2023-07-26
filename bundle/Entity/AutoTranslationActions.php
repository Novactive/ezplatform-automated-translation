<?php

/**
 * EzPlatformAutomatedTranslationBundle.
 *
 * @package   EzPlatformAutomatedTranslationBundle
 *
 * @author    Novactive
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/ezplatform-automated-translation/blob/master/LICENSE
 */

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="auto_translation_actions")
 * @ORM\Entity(repositoryClass="EzSystems\EzPlatformAutomatedTranslationBundle\Repository\AutoTranslationActionsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class AutoTranslationActions
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_FAILED = 'failed';
    public const STATUS_FINISHED = 'finished';
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var int
     *
     * @ORM\Column(name="subtree_id", type="integer", length=11, nullable=false)
     */
    private int $subtreeId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", length=11, nullable=false)
     */
    private int $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="target_language", type="text", length=20, nullable=false)
     */
    private string $targetLanguage;

    /**
     * @var boolean
     *
     * @ORM\Column(name="overwrite", type="boolean", nullable=false, options={"default": false})
     */
    private bool $overwrite = false;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=11, nullable=false, options={"default": "pending"})
     */
    private string $status = self::STATUS_PENDING;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime",nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @var String
     *
     * @ORM\Column(name="log_message", type="text", nullable=true)
     */
    private $logMessage;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubtreeId(): int
    {
        return $this->subtreeId;
    }

    public function setSubtreeId(int $subtreeId): self
    {
        $this->subtreeId = $subtreeId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): self
    {
        $this->targetLanguage = $targetLanguage;
        return $this;
    }

    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    /**
     * @param DateTime|null $finishedAt
     */
    public function setFinishedAt(?DateTime $finishedAt = null): void
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return string|null
     */
    public function getLogMessage(): ?string
    {
        return $this->logMessage;
    }

    /**
     * @param string|null $logMessage
     */
    public function setLogMessage(?string $logMessage): void
    {
        $this->logMessage = $logMessage;
    }

    /**
     * @ORM\PrePersist
     */
    public function updatedTimestamps(): void
    {
        $dateTimeNow = new DateTime('now');
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($dateTimeNow);
        }
    }
}
