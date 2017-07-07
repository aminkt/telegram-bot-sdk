<?php

namespace Telegram\Bot\Commands;

use ReflectionMethod;
use Telegram\Bot\Answers\Answerable;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Class Command.
 *
 *
 * @method mixed replyWithMessage($use_sendMessage_parameters)       Reply Chat with a message. You can use all the sendMessage() parameters except chat_id.
 * @method mixed replyWithPhoto($use_sendPhoto_parameters)           Reply Chat with a Photo. You can use all the sendPhoto() parameters except chat_id.
 * @method mixed replyWithAudio($use_sendAudio_parameters)           Reply Chat with an Audio message. You can use all the sendAudio() parameters except chat_id.
 * @method mixed replyWithVideo($use_sendVideo_parameters)           Reply Chat with a Video. You can use all the sendVideo() parameters except chat_id.
 * @method mixed replyWithVoice($use_sendVoice_parameters)           Reply Chat with a Voice message. You can use all the sendVoice() parameters except chat_id.
 * @method mixed replyWithDocument($use_sendDocument_parameters)     Reply Chat with a Document. You can use all the sendDocument() parameters except chat_id.
 * @method mixed replyWithSticker($use_sendSticker_parameters)       Reply Chat with a Sticker. You can use all the sendSticker() parameters except chat_id.
 * @method mixed replyWithLocation($use_sendLocation_parameters)     Reply Chat with a Location. You can use all the sendLocation() parameters except chat_id.
 * @method mixed replyWithChatAction($use_sendChatAction_parameters) Reply Chat with a Chat Action. You can use all the sendChatAction() parameters except chat_id.
 */
abstract class Command implements CommandInterface
{
    use Answerable;

    /**
     * The name of the Telegram command.
     * Ex: help - Whenever the user sends /help, this would be resolved.
     *
     * @var string
     */
    protected $name;

    /**
     * This attribute help system to get user input to replay this message.
     * @var $replyTextCommandTrigger
     */
    protected $replyTextTrigger=null;

    /**
     * Command Aliases
     * Helpful when you want to trigger command with more than one name.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * @var string The Telegram command description.
     */
    protected $description;

    /**
     * @var string Arguments passed to the command.
     */
    protected $arguments;


    /**
     * Run when an object created
     */
    public function init(){

    }

    /**
     * Get Command Name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get Command Aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set Command Name.
     *
     * @param $name
     *
     * @return Command
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get Command Description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set Command Description.
     *
     * @param $description
     *
     * @return Command
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get Command replyTextTrigger.
     *
     * @return string
     */
    public function getReplyTextTrigger()
    {
        return trim($this->replyTextTrigger);
    }

    /**
     * Set replyTextTrigger
     *
     * @param $replyTextTrigger
     *
     * @return Command
     */
    public function setReplyTextTrigger($replyTextTrigger)
    {
        $this->replyTextTrigger = trim($replyTextTrigger);

        return $this;
    }

    /**
     * Handle errors
     * @param $message
     * @param bool $hint
     */
    public function error($message, $hint=true){
        $message = '❌ '.$message.PHP_EOL;
        if($hint)
            $message.="```این مشکل ممکن است یک خطای سیستمی باشد. در صورتی که چنین فکر میکنید موضوع را به اطلاع ما برسانید.```";
        $this->replyWithMessage([
            'text'=>$message,
            'parse_mode'=>'Markdown',
        ]);
    }

    /**
     * Handle info messages.
     * @param $message
     */
    public function info($message){
        $message = '❕ '.$message.PHP_EOL;

        $this->replyWithMessage([
            'text'=>$message,
            'parse_mode'=>'Markdown',
        ]);
    }

    /**
     * Handle success messages.
     * @param $message
     */
    public function success($message){
        $message = '✅ '.$message.PHP_EOL;

        $this->replyWithMessage([
            'text'=>$message,
            'parse_mode'=>'Markdown',
        ]);
    }

    /**
     * Get Arguments passed to the command.
     *
     * @return string
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns an instance of Command Bus.
     *
     * @return CommandBus
     */
    public function getCommandBus()
    {
        return $this->telegram->getCommandBus();
    }

    /**
     * @inheritDoc
     */
    public function make(Api $telegram, $arguments, Update $update)
    {
        $this->telegram = $telegram;
        $this->arguments = $arguments;
        $this->update = $update;

        $this->init();

        $args = $this->bindMethodParams('handle', $arguments);

        return call_user_func_array(array($this, 'handle'), $args);
    }

    /**
     * Bind params to method.
     * @param $method
     * @param array $args
     * @return array
     */
    public function bindMethodParams($method, $args) {
        if(method_exists($this, $method)) {
            $arguments = array();
            $reflectionMethod = new ReflectionMethod($this,$method);
            foreach($reflectionMethod->getParameters() as $parameter){
                if(isset($args[$parameter->name])){
                    $arguments[$parameter->name] = $args[$parameter->name];
                } elseif(isset($args[$parameter->getPosition()])){
                    $arguments[$parameter->getName()] = $args[$parameter->getPosition()];
                } elseif($parameter->isDefaultValueAvailable()){
                    $arguments[$parameter->name] = $parameter->getDefaultValue();
                } else {
                    $arguments[$parameter->name] = null;
                }
            }
            return $arguments;
        }
        return $args?$args:[];
    }

    /**
     * Helper to Trigger other Commands.
     *
     * @param      $command
     * @param null $arguments
     *
     * @return mixed
     */
    protected function triggerCommand($command, $arguments = null)
    {
        return $this->getCommandBus()->execute($command, $arguments ? $arguments : $this->arguments, $this->update);
    }
}
