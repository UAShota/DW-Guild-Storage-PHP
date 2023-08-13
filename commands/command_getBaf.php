<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс запроса бафа командой "Хочу баф [N]"
 */
class CommandGetBaf extends CommandCustom
{
    /*
     * Экшен действия
     */
    protected const CMD_ACTION = 'action';

    /*
     * Экшен бафа
     */
    protected const CMD_BAF = 'baf';

    /*
     * Экшен оплаты
     */
    protected const CMD_PAY = 'pay';

    /*
     * Цена оплаты
     */
    protected const BAF_COST = 300;

    /*
     * Доступный кредит для покупки
     */
    protected const CREDIT = 5000;

    /*
     * Накладывание бафа
     */
    protected function useBaf(Message $message, $data)
    {
        $tmpHave = $this->database->getInt($message->user_id, self::GOLD);
        if (self::BAF_COST > $tmpHave + self::CREDIT)
            $this->transport->writeChat($this->getAccountTag($message->user_id, $message->name) . ', на вашем счету недостаточно золота', $message, false);
        else
            $this->transport->writeChat('Благословение ' . $data[1], $message, true);
        return true;
    }

    /*
     * Сбор оплаты
     */
    protected function usePay(Message $message, $data)
    {
        // Проверим что отписал сам хранитель
        if ($message->user_id != self::DW_ID)
            return false;
        // Сменим баланс
        $tmpHave = $this->database->getInt($data[1], self::GOLD);
        $this->database->setParam($data[1], self::GOLD, $tmpHave - self::BAF_COST);
        // Уведомим
        $this->transport->writeChat($this->getAccountTag($data[1], $data[2])
            . ", Ваш баланс: 🌕" . $this->database->getInt($data[1], self::GOLD), $message, false);
        return true;
    }

    protected function validate(Message $message)
    {
        // Проверим просьбу бафа
        if (preg_match('/^хочу баф (.+)/ui', $message->text, $tmpMatched)) {
            $tmpMatched[self::CMD_ACTION] = self::CMD_BAF;
            return $tmpMatched;
        }
        // Проверим оплату бафа
        if (preg_match('/^✨\[id(\d+)\|(.+?)], на Вас наложено благословение /', $message->text, $tmpMatched)) {
            $tmpMatched[self::CMD_ACTION] = self::CMD_PAY;
            return $tmpMatched;
        }
        // Ничего не подошло
        return false;
    }

    protected function work(Message $message, $data)
    {
        if ($data[self::CMD_ACTION] == self::CMD_BAF)
            return $this->useBaf($message, $data);
        if ($data[self::CMD_ACTION] == self::CMD_PAY)
            return $this->usePay($message, $data);
        // Ничего не подошло
        return false;
    }
}