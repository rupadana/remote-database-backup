<?php

namespace App\Jobs;

use App\Models\Database;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use function Pest\Laravel\put;

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
            $backedUp = null;
            if ($data['type'] === 'mysql') {
                $backedUp = $this->backupMysql($data['data']);

            } else if ($data['type'] === 'postgresql') {
                $backedUp = $this->backupPostgresql($data['data']);
            }
            else {
                throw new \Exception('Backup driver is not found');
            }


            if ($backedUp) {
                $record->backup_histories()->create([
                    'path' => $backedUp['path'],
                    'filename' => $backedUp['filename'],
                ]);
            }
        }
    }


    protected function backupMysql(array $data): array
    {
        $filename = $data['database'] . "-backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".gz";
        $path = storage_path() . "/databases";

        $command = "mysqldump --user=" . $data['username'] . " --password=" . $data['password']
            . " --host=" . $data['host'] . " " . $data['database']
            . "  | gzip > " . $path . '/' . $filename;
        $returnVar = NULL;
        $output = NULL;

        exec($command, $output, $returnVar);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    protected function backupPostgresql(array $data): array
    {
        $filename = $data['database'] . "-backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".gz";
        $path = storage_path() . "/databases";
        putenv('PGPASSWORD=' . $data['password']);
        $command = 'PGPASSWORD=' . $data['password'] . " pg_dump --username=" . $data['username'] . " --port=" . $data['port']
            . " --host=" . $data['host'] . " -d " . $data['database']
            . "  | gzip > " . $path . '/' . $filename;
        $returnVar = NULL;
        $output = NULL;
        putenv('PGPASSWORD=');

        exec($command, $output, $returnVar);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }
}
