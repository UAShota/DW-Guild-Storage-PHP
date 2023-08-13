<?

namespace VKBot;

/**
 * Управление сессионными переменными
 */
class Database
{
    /**
     * JSON массив данных
     */
    private array $config;

    /**
     * Файл хранилища
     */
    private string $fileName = 'database.txt';

    /**
     * Конструктор
     * @param string $dataPath Путь к каталогу хранения
     */
    function __construct(string $dataPath)
    {
        $this->fileName = $dataPath . $this->fileName;
        $this->load();
    }

    /**
     * Деструктор
     */
    function __destruct()
    {
        $this->save();
    }

    /**
     * Загрузка базы данных
     */
    public function load()
    {
        if (file_exists($this->fileName))
            $this->config = json_decode(file_get_contents($this->fileName), true);
        else
            $this->config = array();
    }

    /**
     * Сохранение базы данных
     */
    public function save()
    {
        file_put_contents($this->fileName, json_encode($this->config, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Операция запроса параметров в хранилище
     * @param string $node Каталог
     * @return false|array Хранимое значение
     */
    public function getParams(string $node)
    {
        $tmpValue = $this->config[$node];
        return $tmpValue ? $tmpValue : false;
    }

    /**
     * Операция запроса параметра в хранилище
     * @param string $node Каталог
     * @param string $tag Параметр
     * @return int Хранимое значение
     */
    public function getInt(string $node, string $tag)
    {
        $tmpValue = $this->config[$node][$tag];
        return $tmpValue ? $tmpValue : 0;
    }

    /**
     * Операция запроса массива в хранилище
     * @param string $node Каталог
     * @param string $tag Параметр
     * @return array Хранимое значение
     */
    public function getArray(string $node, string $tag)
    {
        $tmpValue = $this->config[$node][$tag];
        return $tmpValue ? $tmpValue : [];
    }

    /**
     * Операция установки параметра в хранилище
     * @param string $node Каталог
     * @param string $tag Параметр
     * @param mixed $value Значение
     */
    public function setParam(string $node, string $tag, $value)
    {
        $this->config[$node][$tag] = $value;
    }
}