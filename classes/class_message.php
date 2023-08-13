<?

namespace VKBot;

/**
 * Класс сообщения
 */
class Message
{

    /**
     * Номер сообщения
     */
    public int $id;

    /**
     * Канал сообщения
     */
    public int $channel_id;

    /**
     * Аккаунт отправителя
     */
    public int $user_id;

    /**
     * Имя отправителя
     */
    public string $name;

    /**
     * Текст сообщения
     */
    public string $text;
}