<?

namespace VKBot;

require_once('command_custom.php');

/**
 * Класс запроса хранилища
 */
class CommandStorage extends CommandCustom
{
    protected function validate(Message $message)
    {
        if (preg_match('/^хочу склад$/ui', $message->text, $tmpMatched))
            return $tmpMatched;
        else
            return false;
    }

    protected function work(Message $message, $data)
    {
        $tmpParams = $this->database->getParams($this->transport->getOwnerId());
        if (!$tmpParams) {
            $tmpParams = [];
            $tmpData = 'Склад пустой :(';
        } else {
            ksort($tmpParams);
            $tmpData = '';
        }
        // переберем склад
        foreach ($tmpParams as $tmpKey => $tmpValue) {
            // Пропустим пустые
            if ($tmpValue <= 0)
                continue;
            // Определим наш ли это элемент
            $tmpItem = $this->findItem($tmpKey);
            if ($tmpItem)
                $tmpData .= '🛒' . ucfirst($tmpItem->id) . ': ' . $tmpValue . ' по ' . $tmpItem->cost . ' (' . $tmpItem->short . ')' . PHP_EOL;
            else
                $tmpData .= '🛒' . ucfirst($tmpKey). ': ' . $tmpValue . ' без цены' . PHP_EOL;
        }
        $this->transport->writeChat($tmpData, $message, false);
        return true;
    }
}