<?

namespace VKBot;

use Exception;
use VK\Exceptions\VKClientException;

/** @noinspection PhpIncludeInspection */
include "ext/vendor/autoload.php";
include "classes/class_logger.php";
include "classes/class_transport.php";
include "classes/class_database.php";
include "commands/command_storage.php";
include "commands/command_balance.php";
include "commands/command_transfer_gold.php";
include "commands/command_transfer_item.php";
include "commands/command_getItem.php";
include "commands/command_getBaf.php";

/**
 * Класс работы с базой, отправками и обработками команд
 */
class Engine
{
    /**
     * Ссылка на почтальона
     */
    protected Transport $transport;

    /**
     * Ссылка на БД
     */
    protected Database $database;

    /**
     * Ссылка логгер
     */
    protected Logger $logger;

    /*
     * Путь к хранилищу данных
     */
    protected string $datapath;

    /**
     * Зарегистрированные команды
     */
    protected array $commands;

    /**
     * Подключаемые модули
     */
    protected ?array $modules;

    /**
     * Конструктор
     * @param string $dir Каталог скрипта
     * @param string $token Токен владельца
     * @param int $ownerId Идентификатор владельца токена
     * @param int $channelId Идентификтаор канала, который нужно слушать (0 - не учитывать)
     * @param ?array $modules Идентификаторы подключаемых модулей
     * @throws Exception
     */
    public function __construct(string $dir, string $token, int $ownerId, int $channelId, ?array $modules = null)
    {
        // Путь хранения данных
        $this->datapath = $dir . '/data/' . $ownerId . '/';
        if (!is_dir($this->datapath))
            mkdir($this->datapath);
        // Создадим логгер
        $this->logger = new Logger($this->datapath);
        try {
            $this->database = new Database($this->datapath);
            $this->transport = new Transport($this->database, $token, $ownerId, $channelId);
            $this->commands = [];
            $this->modules = $modules;
        } catch (Exception $e) {
            $this->logger->LogException($e);
        }
    }

    /**
     * Регистрация класса обработки команды
     * @param string $classType Класс команды
     * @param string $name Имя модуля
     */
    protected function registerCommand(string $classType, string $name)
    {
        array_push($this->commands, new $classType(
                $this->database,
                $this->transport,
                !isset($this->modules) || in_array($name, $this->modules))
        );
    }

    /**
     * Регистрация доступных команд бота
     */
    protected function registerCommands()
    {
        $this->registerCommand(CommandBalance::class, 'balance');
        $this->registerCommand(CommandStorage::class, 'storage');
        $this->registerCommand(CommandTransferGold::class, 'transfergold');
        $this->registerCommand(CommandTransferItem::class, 'transferitem');
        $this->registerCommand(CommandGetBaf::class, 'getbaf');
        $this->registerCommand(CommandGetItem::class, 'getitem');
    }

    /**
     * Чтение и обработка команд чата определенного канала
     * @throws VKClientException
     */
    protected function readChannels()
    {
        // В первый раз зарегистрируем обработчики
        if (!$this->commands)
            $this->registerCommands();
        // Прочитаем сообщения
        $tmpMessages = $this->transport->readChannel();
        if (count($tmpMessages) == 0)
            return;
        // Переберем сообщения
        foreach ($tmpMessages as $tmpMsg) {
            // Переберем команды
            foreach ($this->commands as $command) {
                // Если сообщение обработано - дальше не передаем
                if ($command->processing($tmpMsg)) {
                    $this->database->save();
                    break;
                }
            }
        }
        // Сохраним БД
        $this->database->save();
    }

    /**
     * Запуск бесконечного чтения
     * @throws Exception
     */
    public function work()
    {
        $tmpBreak = $this->datapath . 'break.lock';
        // Запишем что надо завершиться
        file_put_contents($tmpBreak, rand());
        // Встанем в очередь
        $tmpLock = fopen($this->datapath . '.lock', "w");
        if (!$tmpLock)
            $this->logger->LogText('Unable to create lock');
        // Пока не встали - ожидаем
        while (!flock($tmpLock, LOCK_EX))
            sleep(1);
        // Когда взяли - удалим файл блока
        unlink($tmpBreak);
        // Покрутим цикл
        while (!is_file($tmpBreak)) {
            try {
                $this->readChannels();
            } catch (Exception $e) {
                $this->logger->LogException($e);
            }
        }
        // Отпустим лок
        fclose($tmpLock);
    }
}