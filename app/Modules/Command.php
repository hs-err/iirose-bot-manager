<?php


namespace App\Modules;


use App\Utils\InputUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    protected static $defaultName = 'ob:a';
    private $config;
    /** @var Plugin $plugin */
    private $plugin;

    public function __construct($plugin,$config)
    {
        $this->config=$config;
        $this->plugin=$plugin;
        parent::__construct($this->config->sign);
    }

    protected function configure()
    {
        $this->setDescription($this->config->description)
            ->setAliases($this->config->aliases);
        foreach ($this->config->arguments as $argument){
            $mode=$argument->require ? InputArgument::REQUIRED : InputArgument::OPTIONAL;
            $mode=$argument->array ? $mode|InputArgument::IS_ARRAY : $mode;
            $this->addArgument($argument->name,$mode,$argument->describle,$argument->default);
        }
        foreach ($this->config->options as $option){
            $mode=$option->require ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
            $mode=$option->array ? $mode|InputOption::VALUE_IS_ARRAY : $mode;
            $mode=$option->none ? $mode|InputOption::VALUE_NONE : $mode;
            $this->addOption($option->name,$option->shortcuts,$mode,$option->describle,$option->default);
        }
        foreach ($this->config->usages as $usage) {
            $this->addUsage($usage);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$input instanceof InputUtils){
            return 0;
        }
        $this->plugin->onCommand($this->config->sign,$input->getSender(),$input,$output);
        return 0;
    }
}
