<?

namespace VKBot;

use Exception;
use VK\CallbackApi\LongPoll\VKCallbackApiLongPollExecutor;
use VK\CallbackApi\VKCallbackApiHandler;
use VK\Client\VKApiClient;
use VK\Exceptions\VKClientException;
use VK\TransportClient\TransportClientResponse;
use VK\TransportClient\TransportRequestException;

/**
 * Заглушка хендлера
 */
class PollHandler extends VKCallbackApiHandler
{
}

/**
 * Класс Poll функционала личных сообщений для этого глюкалова из SDK
 */
class PollExecutor extends VKCallbackApiLongPollExecutor
{
    /**
     * Параметр режима загрузки истории
     */
    protected const PARAM_MODE = 'mode';

    /**
     * PTS параметр запроса
     */
    protected const PARAM_PTS = 'pts';

    /**
     * Server параметр запроса
     */
    protected const PARAM_SERVER = 'server';

    /**
     * Значение параметра режима загрузки истории
     */
    protected const MODE_PTS = 32;

    /**
     * Пользователь потерялся (логаут?)
     */
    protected const ERROR_CODE_USER_LOST = 3;

    /**
     * Указан неизвестный протокол
     */
    protected const ERROR_CODE_UNKNOWN_VERSION = 4;

    /**
     * Перегруженный конструктор
     * @param VKApiClient $api_client Ссылка на клиента
     * @param string $access_token Токен доступа
     * @param int $wait Таймаут ожидания
     */
    public function __construct(VKApiClient $api_client, string $access_token, int $wait = self::DEFAULT_WAIT)
    {
        $tmpHandler = new PollHandler();
        parent::__construct($api_client, $access_token, 0, $tmpHandler, $wait);
    }

    /**
     * Запрос на Poll сервер
     * @param bool $updateTs Признак необходимости обновить TS
     * @throws VKClientException
     */
    protected function setLongPollServer(bool $updateTs)
    {
        // Нам нужен pts
        $tmpParams = [
            'need_pts' => 1
        ];
        // Запросим
        try {
            $tmpResponse = $this->api_client->messages()->getLongPollServer($this->access_token, $tmpParams);
        } catch (Exception $e) {
            throw new VKClientException($e);
        }
        // Установим
        $this->server[self::PARAM_SERVER] = $tmpResponse[self::PARAM_SERVER];
        $this->server[self::PARAM_KEY] = $tmpResponse[self::PARAM_KEY];
        if ($updateTs) {
            $this->server[self::PARAM_TS] = $tmpResponse[self::PARAM_TS];
            $this->server[self::PARAM_PTS] = $tmpResponse[self::PARAM_PTS];
        }
    }

    /**
     * Получение событий с Poll сервера за указанный таймстапм
     *
     * @return array|null
     * @throws VKClientException
     */
    protected function getEventsExt()
    {
        $params = array(
            static::PARAM_KEY => $this->server[self::PARAM_KEY],
            static::PARAM_TS => $this->server[self::PARAM_TS],
            static::PARAM_WAIT => $this->wait,
            static::PARAM_ACT => static::VALUE_ACT,
            static::PARAM_MODE => self::MODE_PTS
        );
        try {
            $response = $this->http_client->get("https://" . $this->server[self::PARAM_SERVER], $params);
        } catch (TransportRequestException $e) {
            throw new VKClientException($e);
        }
        return $this->parseResponseExt($response);
    }

    /**
     * Разбор ответа и проверка на наличие ошибок
     *
     * @param TransportClientResponse $response Ответ сервера
     * @return array|false
     * @throws VKClientException
     */
    protected function parseResponseExt(TransportClientResponse $response)
    {
        // Проверим статус последнего сообщения
        $this->checkHttpStatus($response);
        // Вытащим тело
        $decode_body = $this->decodeBody($response->getBody());
        // Если ошибки нет - вернем сразу
        if (!isset($decode_body[static::EVENTS_FAILED]))
            return $decode_body;
        // Поищем ошибку
        switch ($decode_body[static::EVENTS_FAILED]) {
            case static::ERROR_CODE_INCORRECT_TS_VALUE:
                $this->server[static::PARAM_TS] = $decode_body[static::PARAM_TS];
                return false;
            case static::ERROR_CODE_TOKEN_EXPIRED:
                $this->setLongPollServer(false);
                return false;
            case static::ERROR_CODE_USER_LOST:
                $this->setLongPollServer(true);
                return false;
            default:
                throw new VKClientException('Event failed', $decode_body[static::EVENTS_FAILED]);
        }
    }

    /**
     * Возвращение параметров сервера
     * @return array
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Запуск ожидания события
     *
     * @param array $server Кэш параметров сервера
     * @param int $channelId Идентификтаор канала, который нужно слушать (0 - не учитывать)
     * @return array
     * @throws VKClientException
     */
    public function monitor(array $server, int $channelId)
    {
        // Если параметроов нет, запросим с сервера
        if (count($server) == 0)
            $this->setLongPollServer(true);
        else
            $this->server = $server;
        // Запросим события
        $tmpResponse = $this->getEventsExt();
        $tmpHistory = [];
        // Если устарел ключ или что то еще - перезапросим
        if (!$tmpResponse)
            return $tmpHistory;
        // Сперва поищем приходящие сообщения
        $tmpMsgs = [];
        foreach ($tmpResponse[static::EVENTS_UPDATES] as $tmpEvent) {
            // Скипнем если нет нового сообщения
            if (($tmpEvent[0] == 4) && ($tmpEvent[3] == $channelId))
                array_push($tmpMsgs, $tmpEvent[1]);
        }
        // Если сообщения есть - запросим детали
        if (count($tmpMsgs) > 0) {
            try {
                $tmpHistory = $this->api_client->messages()->getById($this->access_token, [
                    'message_ids' => implode(',', $tmpMsgs),
                    'extended' => '1'
                ]);
            } catch (Exception $e) {
                throw new VKClientException($e);
            }
        }
        // Затем сохраним эти параметры
        $this->server[static::PARAM_TS] = $tmpResponse[static::PARAM_TS];
        $this->server[static::PARAM_PTS] = $tmpResponse[static::PARAM_PTS];
        // Если сообщения нет - делать нам нечего
        return $tmpHistory;
    }
}