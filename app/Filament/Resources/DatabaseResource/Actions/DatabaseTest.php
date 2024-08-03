<?php
namespace App\Filament\Resources\DatabaseResource\Actions;

use App\Models\Database;
use Filament\Actions\Action;
use Exception;
use Filament\Notifications\Notification;
use mysqli;

class DatabaseTest extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('test-database-connection');

        $this->action(function (Database $record) {

            $record = $record->data[0]['data'];


            $host = $record['host'];
            $username = $record['username'];
            $password = $record['password'];
            $database = $record['database'];
            $port = $record['port'];

            $mysqli = new \mysqli($host, $username, $password, $database);

            // Check if the query was successful
            if ($mysqli->connect_error) {
                $this->failureNotification(
                    Notification::make()
                        ->title("Connection Failed")
                        ->body($mysqli->error)
                );
                $this->sendFailureNotification();
            }else{
                $this->sendSuccessNotification();
                $mysqli->close();
            }
        });

        $this->label('Test Connection');
        $this->color('info');

        // Here we use the record's ID
        $this->successNotificationTitle('Connection OK ');

        
    }
}
