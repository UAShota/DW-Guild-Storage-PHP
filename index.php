<?

include "src/engine.php";

use VKBot\Engine;

/* хостинг не пускает массивы, хардкордим
// Определим токен
$tmpToken = $_GET['token'];
if (!isset($tmpToken))
    die('No token');
// Определим идентификатор
$tmpId = $_GET['id'];
if (!isset($tmpId))
    die('No id');
// Определим канал
$tmpChannel = $_GET['channel'];
if (!isset($tmpChannel))
    die('No channel');
// Определим модули
$tmpModules =  $_GET['modules'];*/

if ($_GET['mod'] == 1) {
    $tmpToken = '';
    $tmpId = 384297286;
    $tmpChannel = 2000000014;
    $tmpModules = [
        'storage',
        'transferitem',
        'transfergold',
        'getitem',
        'balance'];
}

if ($_GET['mod'] == 2) {
    $tmpToken = '';
    $tmpId = 470698;
    $tmpChannel = 2000000149;
    $tmpModules = [
        'getbaf',
        'transfergold'];
}

// Запустим
$tmpBot = new Engine(__DIR__, $tmpToken, $tmpId, $tmpChannel, $tmpModules);
$tmpBot->work();
?>
<script>  setTimeout(function () {
        location.reload(true);
    }, 1000);</script>
