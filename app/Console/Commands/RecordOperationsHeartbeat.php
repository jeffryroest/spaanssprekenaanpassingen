<?php

namespace App\Console\Commands;

use App\Beta\SchedulerHeartbeat;
use Illuminate\Console\Command;

final class RecordOperationsHeartbeat extends Command
{
    protected $signature = 'operations:heartbeat';

    protected $description = 'Leg zonder persoonsgegevens vast dat de Laravel-scheduler actief is';

    public function handle(SchedulerHeartbeat $heartbeat): int
    {
        $heartbeat->record();

        return self::SUCCESS;
    }
}
