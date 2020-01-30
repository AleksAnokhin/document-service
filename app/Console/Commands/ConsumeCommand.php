<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bschmitt\Amqp\Facades\Amqp;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPChannelException;


class ConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forever consume messages  from the RabbitMq';

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }



    /**
     * Lounching forever consume
     * @return mixed
     */
    public function handle()
    {
        while(true) {
            Amqp::consume('documentor_pipeline', function ($message, $resolver) {
                var_dump($message->body);
                $resolver->acknowledge($message);
            }, [
                'persistent' => true
            ]);
        }

    }
}
