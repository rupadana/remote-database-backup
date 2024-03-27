<?php

namespace App\Jobs;

use App\Models\Database;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $databaseId)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = Database::find($this->databaseId);

        if ($record && $record->data !== null) {
            $data = $record->data[0];

            if ($data['type'] === 'mysql') {
                $data = $this->backupMysql($data['data']);
                if($data) {
                    $record->backup_histories()->create([
                        'path' => $data['path'],
                        'filename' => $data['filename'],
                    ]);
                }
            } else {
                throw new \Exception('Backup driver is not found');
            }
        }
    }


    protected function backupMysql(array $data): array
    {
        $filename = $data['database'] . "-backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".gz";
        $path = storage_path() . "/databases";

        $command = "mysqldump --user=" . $data['username'] . " --password=" . $data['password']
            . " --host=" . $data['password'] . " " . $data['database']
            . "  | gzip > " . $path . '/' . $filename;

        $returnVar = NULL;
        $output = NULL;

        exec($command, $output, $returnVar);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }
}


