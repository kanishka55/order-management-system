<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:import {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import orders from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('file');

        if(!Storage::exists($filename)) {
            $this->error("File not found: $filename");
            return 1;
        }

        $this->info("Starting import of {$filename}...");

        // Read CSV and chunk it for processing
        $handle = fopen(Storage::path($filename), 'r');
        $header = fgetcsv($handle); // Assuming first row is header

        $chunk = [];
        $chunkSize = 100;
        $rowCount = 0;

        while(($row = fgetcsv($handle)) !== false) {
            $chunk[] = array_combine($header, $row);
            $rowCount++;

            if(count($chunk) >= $chunkSize) {
                ProcessOrderImport::dispatch($chunk);
                $chunk = [];
            }
        }

        // Process any remaining chunck
        if(!empty($chunk)) {
            ProcessOrderImport::dispatch($chunk);
        }

        fclose($handle);

        $this->info("Dispatched {$rowCount} orders for processing in chunks of {$chunkSize}");
        return 0;
    }
}
