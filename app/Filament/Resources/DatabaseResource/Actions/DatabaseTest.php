<?php
namespace App\Filament\Resources\DatabaseResource\Actions;

use App\Models\Database;
use Filament\Actions\Action;
use Exception;
use mysqli;

class DatabaseTest extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('test-database-connection');

        $this->action(function (Database $record) {


            $host = $record->data[0]['data']['host'];
            $username = $record->data[0]['data']['username'];
            $password = $record->data[0]['data']['password'];
            $database = $record->data[0]['data']['database'];
            $port = $record->data[0]['data']['port'];

            $mysqli = new \mysqli($host, $username, $password, $database);

            // Check if the query was successful
            if ($mysqli->connect_error) {
                dd('Error executing query: ' . $mysqli->error);
            }else{
                $this->sendSuccessNotification();
                $mysqli->close();
            }


        });

        $this->label('Connection Test');
        $this->color('info');

        // Here we use the record's ID
        $this->successNotificationTitle('Connection OK ');
    }
}
