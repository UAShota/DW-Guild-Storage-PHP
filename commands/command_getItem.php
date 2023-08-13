<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс запроса предмета командой "хочу [N] адский гриб"
 */
class CommandGetItem extends CommandCustom
{
    /*
     * Доступный кредит для покупки
     */
    protected const CREDIT = 5000;

    protected function validate(Message $message)
    {
        if (preg_match('/^хочу (\d+)?(\D+)$/ui', $message->text, $tmpMatched))
            return $tmpMatched;
        else
            return false;
    }

    protected function work(Message $message, $data)
    {
        // Количество товара
        $tmpCount = $data[1] ? $data[1] : 1;
        $tmpType = strtolower(trim($data[2]));
        $tmpItem = $this->findItem($tmpType);
        $tmpType = $tmpItem ? $tmpItem->id : $tmpType;
        $tmpIsGold = $tmpType == self::GOLD;
        // Посмотрим сколько ресурса есть в хранилище
        if ($tmpIsGold)
            $tmpHave = $this->database->getInt($message->user_id, $tmpType);
        else
            $tmpHave = $this->database->getInt($this->transport->getOwnerId(), $tmpType);
        // Определим минимум товара
        $tmpCount = min($tmpCount, $tmpHave);
        // Товара нет
        if (($tmpCount == 0) || ($tmpIsGold && $tmpCount < 100)) {
            if ($tmpIsGold)
                $this->transport->writeChat($this->getAccountTag($message->user_id, $message->name) . ', на вашем счету недостаточно золота', $message, false);
            else
                $this->transport->writeChat('👝 ' . $tmpType . ' нет в наличии', $message, false);
            return true;
        }
        // Товар бесплатный
        if (!$tmpItem) {
            if ($tmpIsGold)
                $this->transport->writeChat('Передать ' . $tmpCount . ' ' . $tmpType, $message, true);
            else
                $this->transport->writeChat('Передать ' . $tmpType . ' - ' . $tmpCount . ' штук', $message, true);
            return true;
        }
        // Товар платный
        $tmpHave = $this->database->getInt($message->user_id, self::GOLD);
        $tmpCost = $tmpCount * $tmpItem->cost;
        if ($tmpCost > $tmpHave + self::CREDIT) {
            $this->transport->writeChat($this->getAccountTag($message->user_id, $message->name) .
                ', нехватка средств 🌕' . $tmpHave . ' для покупки ' . $tmpCount . ' ' . $tmpItem->id
                . ' за 🌕' . $tmpCost . ' (' . $tmpItem->cost . ' за шт)', $message, false);
            return true;
        }
        // Купим предмет
        $this->transport->writeChat('Передать ' . $tmpItem->id . ' - ' . $tmpCount . ' штук', $message, true);
        return true;
    }
}