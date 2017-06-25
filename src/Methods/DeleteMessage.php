<?php
namespace Telegram\Bot\Methods;

use Telegram\Bot\Objects\Message;

/**
 * Class DeleteMessage
 *
 * Use this method to delete a message. A message can only be deleted if it was sent less than 48 hours ago.
 * Any such recently sent outgoing message may be deleted.
 * Additionally, if the bot is an administrator in a group chat, it can delete any message.
 * If the bot is an administrator in a supergroup,
 * it can delete messages from any other user and service messages about people joining or leaving the group
 * (other types of service messages may only be removed by the group creator). In channels, bots can only remove their own messages.
 * Returns True on success.
 *
 * <code>
 * $params = [
 *   'chat_id'                  => '',
 *   'message_id'               => '',
 * ];
 * </code>
 *
 * @link https://core.telegram.org/bots/api#deletemessage
 *
 * @method DeleteMessage chatId($chatId) int|string
 * @method DeleteMessage messageId($messageId) int
 *
 * @method Message|bool getResult($dumpAndDie = false)
 */
class DeleteMessage extends Method
{
    /** {@inheritdoc} */
    protected function returns()
    {
        return Message::class;
    }
}