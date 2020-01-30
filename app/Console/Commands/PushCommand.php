<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bschmitt\Amqp\Facades\Amqp;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push messages to queue';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Push message to queue
     *
     * @return mixed
     */
    public function handle()
    {
        Amqp::publish('test', 'hello from laravel' , ['queue' => 'test']);

    }
}
