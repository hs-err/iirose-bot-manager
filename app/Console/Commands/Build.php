<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Phar;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build {plugin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $plugin=$this->argument('plugin');
        $phar = new Phar(App::basePath().'/Plugin/'.$plugin.'.phar');
        $phar->buildFromDirectory(App::basePath().'/Plugin/src/'.$plugin);
        $phar->compressFiles(Phar::GZ);
        $phar->stopBuffering();
    }
}
