<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс запроса баланса командой "Хочу баланс"
 */
class CommandBalance extends CommandCustom
{
    protected function validate(Message $message)
    {
        if (preg_match('/^хочу баланс$/ui', $message->text, $tmpMatched))
            return $tmpMatched;
        else
            return false;
    }

    protected function work(Message $message, $data)
    {
        $this->transport->writeChat($this->getAccountTag($message->user_id, $message->name)
            . ', Ваш баланс: 🌕' . $this->database->getInt($message->user_id, self::GOLD), $message, false);
        return true;
    }
}