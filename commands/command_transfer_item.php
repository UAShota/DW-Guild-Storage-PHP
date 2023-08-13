<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс учета передач предметов
 */
class CommandTransferItem extends CommandCustom
{
    protected function validate(Message $message)
    {
        if (preg_match('/^👝\[id(\d+)\|(.+?)], получено: (.+?)(, )?(\d+)?( штук)? от игрока \[id(\d+)\|(.+?)]/s', $message->text, $tmpMatched))
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
        $tmpData->message = $message;
        $tmpData->sourceId = $data[1];
        $tmpData->sourceName = $data[2];
        $tmpData->type = mb_strtolower($data[3]);
        $tmpData->count = $data[5] ? $data[5] : 1;
        $tmpData->targetId = $data[7];
        $tmpData->targetName = $data[8];
        $tmpData->item = $this->findItem($tmpData->type);
        // Если передача боту
        if ($tmpData->sourceId == $this->transport->getOwnerId()) {
            if (!$tmpData->item)
                return $this->incomingFree($tmpData);
            else
                return $this->incomingPaid($tmpData);
        }
        // Если передача от бота
        if ($tmpData->targetId == $this->transport->getOwnerId()) {
            if (!$tmpData->item)
                return $this->outDoorFree($tmpData);
            else
                return $this->outDoorPaid($tmpData);
        }
        // Если ничего - не обрабатываем
        return false;
    }

    /**
     * Сохранение неоплачиваемой покупки
     * @param CommandTransferData $data Параметры команды
     * @return bool
     */
    protected function incomingFree(CommandTransferData $data)
    {
        // Увеличим в бд
        $tmpHave = $this->database->getInt($data->sourceId, $data->type);
        $this->database->setParam($data->sourceId, $data->type, $tmpHave + $data->count);
        // Поблагодарим
        $this->transport->writeChat($this->getAccountTag($data->targetId, $data->targetName) . ', ' . $data->type
            . ' взято на хранение', $data->message, false);
        return true;
    }

    /**
     * Сохранение оплачиваемой покупки
     * @param CommandTransferData $data Параметры команды
     * @return bool
     */
    protected function incomingPaid(CommandTransferData $data)
    {
        // Увеличим в бд
        $tmpHave = $this->database->getInt($data->sourceId, $data->item->id);
        $this->database->setParam($data->sourceId, $data->item->id, $tmpHave + $data->count);
        // Пополним баланс
        $tmpHave = $this->database->getInt($data->targetId, self::GOLD);
        $this->database->setParam($data->targetId, self::GOLD, $tmpHave + $data->item->cost * $data->count);
        // Уведомим
        $this->transport->writeChat($this->getAccountTag($data->targetId, $data->targetName) . ' ' . $data->item->id
            . ' принято за 🌕' . ($data->item->cost * $data->count)
            . '. Ваш баланс 🌕' . $this->database->getInt($data->targetId, self::GOLD), $data->message, false);
        return true;
    }

    /**
     * Сохранение неоплачиваемой продажи
     * @param CommandTransferData $data Параметры команды
     * @return bool
     */
    protected function outDoorFree(CommandTransferData $data)
    {
        // Увеличим в бд
        $tmpHave = $this->database->getInt($data->targetId, $data->type);
        $this->database->setParam($data->targetId, $data->type, $tmpHave - $data->count);
        // Поблагодарим
        $this->transport->writeChat($this->getAccountTag($data->sourceId, $data->sourceName)
            . ', ' . $data->count . ' ' . $data->type . ' взято с хранения', $data->message, false);
        return true;
    }

    /**
     * Сохранение оплачиваемой продажи
     * @param CommandTransferData $data Параметры команды
     * @return bool
     */
    protected function outDoorPaid(CommandTransferData $data)
    {
        // Увеличим в бд
        $tmpHave = $this->database->getInt($data->targetId, $data->item->id);
        $this->database->setParam($data->targetId, $data->item->id, $tmpHave - $data->count);
        // Уменьшим баланс
        $tmpHave = $this->database->getInt($data->sourceId, self::GOLD);
        $this->database->setParam($data->sourceId, self::GOLD, $tmpHave - $data->item->cost * $data->count);
        // Уведомим
        $this->transport->writeChat($this->getAccountTag($data->sourceId, $data->sourceName)
            . ' за ' . $data->item->id . ' оплачено 🌕' . ($data->item->cost * $data->count)
            . ". Ваш баланс: 🌕" . $this->database->getInt($data->sourceId, self::GOLD), $data->message, false);
        return true;
    }
}