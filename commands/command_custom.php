<?

namespace VKBot;

/**
 * Товар хранилиша
 */
class CommandStore
{
    /**
     * Идентификатор
     */
    public string $id;

    /**
     * Короткое название
     */
    public string $short;

    /**
     * Цена
     */
    public int $cost;

    /**
     * Конструктор
     * @param string $id Идентификатор
     * @param string $short
     * @param int $cost
     */
    public function __construct(string $id, string $short, int $cost)
    {
        $this->id = $id;
        $this->short = $short;
        $this->cost = $cost;
    }
}

/**
 * Класс параметров передачи данных
 */
class CommandTransferData
{
    /*
     * Исходное сообщение
     */
    public Message $message;

    /*
     * Ссылка бросающего
     */
    public string $sourceId;

    /*
     * Имя бросающего
     */
    public string $sourceName;

    /*
     * Ссылка принимающего
     */
    public string $targetId;

    /*
     * Имя принимающего
     */
    public string $targetName;

    /*
     * Вещь
     */
    public string $type;

    /*
     * Количество
     */
    public int $count;

    /*
     * Ссылка на предмет
     */
    public ?CommandStore $item;
}

/**
 * Базовый класс обработки команд
 */
abstract class CommandCustom
{
    /**
     * Золото
     */
    public const GOLD = 'золота';

    /**
     * Чистая вода
     */
    public const CHISTVODA = 'чистая первозданная вода';

    /**
     * Первозданная вода
     */
    public const PERVVODA = 'первозданная вода';

    /**
     * Пещерный корень
     */
    public const PESHKOREN = 'пещерный корень';

    /**
     * Рыбий жир
     */
    public const RIBIYJIR = 'рыбий жир';

    /**
     * Камнецвет
     */
    public const KAMNECVET = 'камнецвет';

    /**
     * Сквернолист
     */
    public const SKVERNOLIST = 'сквернолист';

    /**
     * Адский корень
     */
    public const HELLKOREN = 'адский корень';

    /**
     * Адский гриб
     */
    public const HELLGRIB = 'адский гриб';

    /**
     * Болотник
     */
    public const BOLOTNIK = 'болотник';

    /*
     * Идентификатор бота игры
     */
    public const DW_ID = -183040898;

    /**
     * Ссылка на БД
     */
    protected Database $database;

    /**
     * Ссылка на почтальона
     */
    protected Transport $transport;

    /*
     * Признак включенного модуля
     */
    protected bool $enabled;

    /**
     * Массив доступного хранилища
     * @var CommandStore[]
     */
    protected array $store;

    /**
     * Форматирование тега для аккаунта
     * @param int $id Идентификатор
     * @param string $name Отображаемое имя
     * @return string
     */
    protected function getAccountTag(int $id, string $name)
    {
        if ($id > 0)
            $tmpPrefix = '@id';
        else
            $tmpPrefix = '@club';
        return $tmpPrefix . $id . ' (' . $name . ')';
    }

    /**
     * Поиск ресурса по его имени
     * @param string $name Имя ресурса
     * @return CommandStore|null
     */
    protected function findItem(string $name)
    {
        foreach ($this->store as $item)
            if (($item->id == $name) || ($item->short == $name))
                return $item;
        return null;
    }

    /**
     * Конструктор
     * @param Database $database Ссылка на БД
     * @param Transport $transport Ссылка на почтальона
     * @param bool $enabled Признак включенного модуля
     */
    public function __construct(Database $database, Transport $transport, bool $enabled)
    {
        // Сохраним ссылки
        $this->database = $database;
        $this->transport = $transport;
        $this->enabled = $enabled;
        $this->store = [];
        // Запишем массив
        array_push($this->store, new CommandStore(self::PERVVODA, 'воды', 20));
        array_push($this->store, new CommandStore(self::CHISTVODA, 'чистой', 150));
        array_push($this->store, new CommandStore(self::PESHKOREN, 'корень', 60));
        array_push($this->store, new CommandStore(self::RIBIYJIR, 'жир', 60));
        array_push($this->store, new CommandStore(self::KAMNECVET, 'камнецвет', 450));
        array_push($this->store, new CommandStore(self::SKVERNOLIST, 'сквер', 170));
        array_push($this->store, new CommandStore(self::HELLKOREN, 'акорень', 430));
        array_push($this->store, new CommandStore(self::HELLGRIB, 'агриб', 230));
        array_push($this->store, new CommandStore(self::BOLOTNIK, 'болото', 150));
    }

    /**
     * Запрос на разрешение обработки команды
     * @param Message $message Сообщение сервера
     * @return boolean|mixed Regex match если команда подходит
     */
    protected abstract function validate(Message $message);

    /*
    * Обработка команды
    * @param Message $message Сообщение сервера
    * @return boolean True если команда была обработана
    */
    protected abstract function work(Message $message, $data);

    /**
     * Запрос на разрешение обработки команды
     * @param Message $message Сообщение сервера
     * @return boolean True если команда подходит
     */
    public function processing(Message $message)
    {
        // Проверим доступность
        $tmpMixed = $this->validate($message);
        if (!$tmpMixed)
            return false;
        // Выполним или скипнем
        return !$this->enabled || $this->work($message, $tmpMixed);
    }
}