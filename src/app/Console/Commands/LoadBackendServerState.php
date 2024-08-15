<?php

namespace App\Console\Commands;

use App\Lib\Haproxy\AdminInterface;
use Illuminate\Console\Command;

class LoadBackendServerState extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:load-backend-server-state {backend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load state of backend servers into haproxy';

    /**
     * Execute the console command.
     */
    public function handle(AdminInterface $haproxyadmin): void {
        $haproxyadmin->loadServerState([$this->argument('backend')]);
    }
}
