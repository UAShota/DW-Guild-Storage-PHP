<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс учета передач предметов
 */
class CommandTransferGold extends CommandCustom
{
    protected function validate(Message $message)
    {
        if (preg_match('/^🌕\[id(\d+)\|(.+?)], получено (\d+) золота от игрока \[id(\d+)\|(.+?)]/s', $message->text, $tmpMatched))
            return $tmpMatched;
        else
            return false;
    }

    protected function work(Message $message, $data)
    {
        // Если не хранитель - скипаем запись
        if ($message->user_id != self::DW_ID)
            return true;
        // Обработаем регулярку
        $tmpData = new CommandTransferData();
        $tmpData->sourceId = $data[1];
        $tmpData->sourceName = $data[2];
        $tmpData->count = $data[3];
        $tmpData->targetId = $data[4];
        $tmpData->targetName = $data[5];
        // Если передача боту
        if ($tmpData->sourceId == $this->transport->getOwnerId()) {
            // Увеличим счет игрока
            $tmpHave = $this->database->getInt($tmpData->targetId, self::GOLD);
            $this->database->setParam($tmpData->targetId, self::GOLD, $tmpHave + $tmpData->count);
            // Уведомим
            $this->transport->writeChat($this->getAccountTag($tmpData->targetId, $tmpData->targetName)
                . ", Ваш баланс: 🌕" . $this->database->getInt($tmpData->targetId, self::GOLD), $message, false);
            return true;
        }
        // Если передача от бота
        if ($tmpData->targetId == $this->transport->getOwnerId()) {
            // Уменьшим счет игрока
            $tmpHave = $this->database->getInt($tmpData->sourceId, self::GOLD);
            $this->database->setParam($tmpData->sourceId, self::GOLD, $tmpHave - ($tmpData->count + $tmpData->count / 9));
            // Уведомим
            $this->transport->writeChat($this->getAccountTag($tmpData->sourceId, $tmpData->sourceName)
                . ", Ваш баланс: 🌕" . $this->database->getInt($tmpData->sourceId, self::GOLD), $message, false);
            return true;
        }
        // Иначе не обрабатываем
        return false;
    }
}