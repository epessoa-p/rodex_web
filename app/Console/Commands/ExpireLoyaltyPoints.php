<?php

namespace App\Console\Commands;

use App\Services\Loyalty\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expira los puntos de fidelización vencidos (lotes con fecha de expiración cumplida).';

    public function handle(LoyaltyService $loyalty): int
    {
        $expired = $loyalty->expirePoints();
        $this->info("Puntos expirados: {$expired}");

        return self::SUCCESS;
    }
}
