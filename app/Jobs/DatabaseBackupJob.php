<?php

namespace App\Jobs;

use App\Filament\Resources\DatabaseResource\Services\Backup\BackupRunner;
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

    public $timeout = 300;

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
        //        dd($this->databaseId);
        $record = Database::find($this->databaseId);

        if ($record && $record->data !== null) {
            $data = $record->data[0];
            $runner = BackupRunner::of($data['type'], $data['data'])->run();

            if ($runner) {
                $record->backup_histories()->create([
                    'path' => $runner['path'],
                    'filename' => $runner['filename'],
                ]);
            }
        }
    }

    //    protected function backupMysql(array $data): array
    //    {
    //        $filename = $data['database'] . "-backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".gz";
    //        $path = storage_path() . "/databases";
    //
    //        $command = "mysqldump --user=" . $data['username'] . " --password=" . $data['password']
    //            . " --host=" . $data['host'] . " " . $data['database']
    //            . "  | gzip > " . $path . '/' . $filename;
    //        $returnVar = NULL;
    //        $output = NULL;
    //
    //        exec($command, $output, $returnVar);
    //
    //        return [
    //            'path' => $path,
    //            'filename' => $filename,
    //        ];
    //    }

    protected function backupPostgresql(array $data): array
    {
        $filename = $data['database'].'-backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.gz';
        $path = storage_path().'/databases';
        putenv('PGPASSWORD='.$data['password']);
        $command = 'PGPASSWORD='.$data['password'].' pg_dump --username='.$data['username'].' --port='.$data['port']
            .' --host='.$data['host'].' -d '.$data['database']
            .'  | gzip > '.$path.'/'.$filename;
        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);
        putenv('PGPASSWORD=');

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }
}
