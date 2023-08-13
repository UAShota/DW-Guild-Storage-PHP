<?

namespace VKBot;

use Exception;

/**
 * Класс логирования исключений
 */
class Logger
{
    /**
     * Класс логирования ошибок
     */
    protected string $fileName = 'errors.txt';

    /**
     * Конструктор
     * @param string $path Путь к каталогу данных
     */
    public function __construct(string $path)
    {
        $this->fileName = $path . $this->fileName;
    }

    /**
     * Логирование текста
     * @param string $text Текст сообщения
     * @throws Exception
     */
    public function LogText(string $text)
    {
        $tmpData = date('Y-m-d H:i:s') . ' ' . $text . PHP_EOL;
        file_put_contents($this->fileName, $tmpData, FILE_APPEND);
        throw new Exception($text);
    }

    /**
     * Логирование исключения
     * @param Exception $exception Объект исключения
     * @throws Exception
     */
    public function LogException(Exception $exception)
    {
        self::LogText(print_r($exception, true));
    }
}