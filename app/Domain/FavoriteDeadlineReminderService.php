<?php

namespace JobMarket\Domain;

use JobMarket\Infrastructure\FavoriteDeadlineReminderRepository;

class FavoriteDeadlineReminderService
{
    public function __construct(private ?FavoriteDeadlineReminderRepository $repository = null)
    {
        $this->repository ??= new FavoriteDeadlineReminderRepository();
    }

    public function dispatch(bool $dryRun = false): array
    {
        $candidates = $this->repository->candidates(2);
        $sent = 0;
        if (!$dryRun) {
            foreach ($candidates as $candidate) {
                if ($this->repository->createReminder($candidate)) $sent++;
            }
        }
        return ["candidates" => count($candidates), "sent" => $sent, "dry_run" => $dryRun];
    }
}
