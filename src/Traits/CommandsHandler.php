<?php
namespace Telegram\Bot\Traits;

use Telegram\Bot\Commands\CommandBus;
use Telegram\Bot\Objects\Update;

/**
 * CommandsHandler
 */
trait CommandsHandler
{
    /**
     * Return Command Bus.
     *
     * @return CommandBus
     */
    protected function getCommandBus()
    {
        return CommandBus::Instance()->setTelegram($this);
    }

    /**
     * Get all registered commands.
     *
     * @return mixed
     */
    public function getCommands()
    {
        return $this->getCommandBus()->getCommands();
    }

    /**
     * Processes Inbound Commands.
     *
     * @param bool $webhook
     *
     * @return Update|Update[]
     */
    public function commandsHandler($webhook = false)
    {
        if ($webhook) {
            $update = $this->getWebhookUpdate();
            $this->processCommand($update);

            return $update;
        }

        $updates = $this->getUpdates()->getResult();
        $highestId = -1;

        foreach ($updates as $update) {
            $highestId = $update->updateId;
            $this->processCommand($update);
        }

        //An update is considered confirmed as soon as getUpdates is called with an offset higher than its update_id.
        if ($highestId != -1) {
            $this->markUpdateAsRead($highestId);
        }

        return $updates;
    }

    /**
     * An alias for getUpdates that helps readability.
     *
     * @param $highestId
     *
     * @return \Telegram\Bot\Objects\Update[]
     */
    protected function markUpdateAsRead($highestId)
    {
        return $this->getUpdates()
            ->shouldEmitEvents(false)
            ->offset($highestId + 1)
            ->limit(1)
            ->getResult();
    }

    /**
     * Check update object for a command and process.
     *
     * @param Update $update
     */
    public function processCommand(Update $update)
    {
        $message = $update->getMessage();

        if($callBackQuery = $update->callbackQuery){
            $this->getCommandBus()->handler($callBackQuery->data, $update);
            return;
        }
        if($message == null)
            return;

        if($message->has('entities')) {
            if($message->entities[0]['type'] == 'bot_command') {
                $this->getCommandBus()->handler($this->getMessageText($update), $update);
            }
        }elseif($update->getMessage() and $replyToMessage = $update->getMessage()->replyToMessage){
            $this->handleReplyMessages($replyToMessage, $update);
        }else{
            $this->handleUserMessages($message, $update);
        }
    }

    /**
     * If user message is a replied message then this method handle it.
     * @param $message \Telegram\Bot\Objects\Message
     * @param $update Update
     */
    public function handleReplyMessages($message, $update){
        $commands = $this->getCommandBus()->getCommands();
        foreach ($commands as $command){
            if(trim($message->text) == $command->getReplyTextTrigger()){
                $arguments = [];
                $arguments[] = $update->getMessage()->text;
                $command->make($this, $arguments, $update);
            }
        }
    }

    /**
     * Check user message and take a good action to reply
     * @param $message \Telegram\Bot\Objects\Message
     * @param $update Update
     */
    public function handleUserMessages($message, $update){
        $commands = $this->getCommandBus()->getCommands();
        foreach ($commands as $command){
            if(in_array(trim($message->text), $command->getAliases())){
                $arguments = [];
                $arguments[] = $update->getMessage()->text;
                $command->make($this, $arguments, $update);
            }
        }
    }

    /**
     * Helper to Trigger Commands.
     *
     * @param string $name   Command Name
     * @param Update $update Update Object
     *
     * @return mixed
     */
    public function triggerCommand($name, Update $update)
    {
        return $this->getCommandBus()->execute($name, [$this->getMessageText($update)], $update);
    }

    /**
     * Get Message Text from Update.
     *
     * @param Update $update
     *
     * @return mixed|null
     */
    protected function getMessageText(Update $update)
    {
        $message = $update->getMessage();

        if ($message !== null && $message->has('text')) {
            return $message->text;
        }

        return null;
    }
}
