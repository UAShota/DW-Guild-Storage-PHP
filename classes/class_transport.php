<?

namespace VKBot;

require_once 'class_message.php';
require_once 'class_executor.php';

use Exception;
use VK\Client\VKApiClient;
use VK\Exceptions\VKClientException;

/**
 * Класс управления сообщениями
 */
class Transport
{
    /**
     * Тег рутовой ноды в базе
     */
    protected const TAG_NAME = 'owner';

    /**
     * Тег ноды хранения данных Poll сервера
     */
    protected const TAG_POLL = 'poll';

    /**
     * Токен страницы
     */
    protected string $token;

    /**
     * VK API клиент
     */
    protected VKApiClient $client;

    /**
     * Ссылка на базу данных
     */
    protected Database $database;

    /**
     * Идентификатор владельца токена
     */
    protected int $ownerId;

    /*
     * Идентификтаор канала, который нужно слушать (0 - не учитывать)
     */
    protected int $channelId;

    /**
     * Таймаут запроса
     */
    protected int $timeout = 25;

    /**
     * Конструктор
     * @param Database $database Ссылка на БД
     * @param string $token Токен
     * @param int $ownerId Идентификатор владельца
     * @param int $channelId Идентификтаор канала, который нужно слушать (0 - не учитывать)
     */
    public function __construct(Database $database, string $token, int $ownerId, int $channelId)
    {
        $this->database = $database;
        $this->token = $token;
        $this->ownerId = $ownerId;
        $this->channelId = $channelId;
    }

    /**
     * Определение идентификатора бота
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Чтение последних сообщений канала
     * @return Message[] Массив прочитанных сообщений
     * @throws VKClientException
     */
    public function readChannel()
    {
        $this->client = new VKApiClient();
        // Запросим параметры с базы, если нету - с сервера
        $tmpServer = $this->database->getArray(self::TAG_NAME, self::TAG_POLL);
        // Создадим класс листенера
        $executor = new PollExecutor($this->client, $this->token, $this->timeout);
        $tmpData = $executor->monitor($tmpServer, $this->channelId);
        // Если данных нет - продолжим слежку
        // Загрузим сообщения
        $tmpMessages = [];
        if ($tmpData) {
            // Есть два типа - профиль игрока и отрицательный - сообщества
            $tmpUsers = [];
            $tmpGroups = [];
            // Разберем профиль игрока
            if (isset($tmpData['profiles'])) {
                foreach ($tmpData['profiles'] as $tmpProfile)
                    $tmpUsers[$tmpProfile['id']] = $tmpProfile['first_name'] . ' ' . $tmpProfile['last_name'];
            }
            // Разберем профиль сообщества
            if (isset($tmpData['groups'])) {
                foreach ($tmpData['groups'] as $tmpGroup)
                    $tmpGroups[-$tmpGroup['id']] = $tmpGroup['name'];
            }
            // Прочитаем сообщения
            foreach ($tmpData['items'] as $tmpMsg) {
                $tmpMessage = new Message();
                $tmpMessage->id = $tmpMsg['id'];
                $tmpMessage->channel_id = $tmpMsg['peer_id'];
                $tmpMessage->user_id = $tmpMsg['from_id'];
                $tmpMessage->text = $tmpMsg['text'];
                if ($tmpMessage->user_id > 0)
                    $tmpMessage->name = $tmpUsers[$tmpMsg['from_id']];
                else
                    $tmpMessage->name = $tmpGroups[$tmpMsg['from_id']];
                // Запулим в массив
                array_push($tmpMessages, $tmpMessage);
            }
        }
        // Запишем последние параметры в базу
        $this->database->setParam(self::TAG_NAME, self::TAG_POLL, $executor->getServer());
        // Вернем
        return $tmpMessages;
    }

    /**
     * Отправка сообщения в канал
     * @param string $text Текст отправляемого сообщения
     * @param Message|null $message Исходное сообщение
     * @param bool $reply Признак ответа на запрос
     * @throws VKClientException
     */
    public function writeChat(string $text, Message $message, bool $reply)
    {
        // Основной ответ
        $tmpParams = [
            'peer_id' => $message->channel_id,
            'message' => $text,
            'random_id' => rand()
        ];
        // Цитирование
        if ($reply)
            $tmpParams['reply_to'] = $message->id;
        // Отправка
        try {
            $this->client->messages()->send($this->token, $tmpParams);
        } catch (Exception $e) {
            throw new VKClientException($e);
        }
        // Пауза чтобы не получить капчу
        sleep(1);
    }
}