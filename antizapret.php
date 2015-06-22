<?php

error_reporting(0);
ini_set('display_errors', 0);
/*
 * 
 * E-mail: contact@antizapret.info
 * URL: http://antizapret.info
 */
/*
 * версия от 31 июля 2014
 * - Исправлена проверка кэша в функции updateBaseupdateBase
 * - $_SERVER['DOCUMENT_ROOT'] . "/" . $this->cache_dir . "/" выведено в отдельную переменную $this->cache_path
 */

/*
 * @class AntiZapret
 * @brief Класс для работы с сервисом antizapret.info
 * 
 *  @see example.php
 * Осуществляет блокировку сотрудников Роскомнадзора и других нехороших гос. структур
 */

class AntiZapret {

// время кэширования файла с массивом блокируемых подсетей
    private $cache_time = 60;
// директория с временными файлами
    private $cache_dir = ".antizapret";
    //включение кэша
    private $cache_enable = 0;
    //файл с базой
    private $cache_file = ".htzapret";
    //url api
    private $base_url = "http://api.antizapret.info/cats.php";
    public $cache_path;

    public function __construct() {
        $this->cache_path = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->cache_dir;
    }

    /*
     * @brief Загрузка  и сохранение базы заблокированных подсетей
     * 
     */

    public function downloadBase() {
        file_put_contents($this->cache_path . "/" . $this->cache_file, file_get_contents($this->base_url));
        if (!is_file($this->cache_path . "/" . $this->cache_file))
            die('Ошибка записи файла ' . $this->cache_file);
    }

    /*
     * @brief Проверка  базы подсетей на предмет устаревания (параметр $cache_time в минутах), при первом запуске создается директория кэша
     * 
     */

    public function updateBase() {
        if (!is_dir($this->cache_path)) {
            if (!mkdir($this->cache_path, 0777, true)) {
                die('Ошибка создания директории ' . $this->cache_dir);
            } else
                file_put_contents($this->cache_path . "/.htaccess", "deny from all");
        }


        if (!is_file($this->cache_path . "/" . $this->cache_file) or (filemtime($this->cache_path . "/" . $this->cache_file) < (time() + $this->cache_time * 60)))
            self::downloadBase();
    }

    /*
     * @brief определение принадлежности ip диапазону 
     * @param network - подсеть
     * @param ip - адрес пользователя
     * @return массив или false, если не найдено
     */

    public function match($network, $ip) {
        $ip_arr = explode('/', $network);
        $network_long = ip2long($ip_arr[0]);
        $x = ip2long($ip_arr[1]);
        $mask = long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
        $ip_long = ip2long($ip);
        return ($ip_long & $mask) == ($network_long & $mask);
    }

    /*
     * @brief выводит информационный блок и прерывает работу, по умолчанию, сто котиков с антизапрет показываются случайным порядком
     * 
     */

    public function goBlock() {
        echo "<html><head><style>#block { height: 25em; line-height: 25em; text-align: center; background: white;}#block img { vertical-align: middle;}</style></head><body><div id=\"block\"><img src=\"http://api.antizapret.info/cats.jpg\"></div></body></html>";
        exit();
    }

    /*
     * @brief производится загрузка базы и запись ip адреса блокируемого при включеном файловом кэшировании (с целью снижения нагрузки на разбор массива, так как is_file быстрее)
     * 
     */

    public function parseBase($ip, $store = 0) {
        $networks = explode("::", file_get_contents($this->cache_path . "/" . $this->cache_file));
        foreach ($networks AS $network) {
            if (self::match($network, $ip)) {
                if ($store)
                    file_put_contents($this->cache_path . "/" . $ip, "");
                self::goBlock();
            }
        }
    }

    /*
     * @brief производится проверка прилетаемого ip, отметаются ipv6, в случае кэша проверяется файл с ip клиента или поиск по массиву заблокированных подсетей и блокировка, или пропуск.
     * 
     */

    public function banRsoc($ip) {
        if ($ip != long2ip(ip2long($ip)))
            return;
        self::updateBase();
        if ($this->cache_enable) {
            if (is_file($this->cache_path . "/" . $ip))
                self::goBlock();
            else
                self::parseBase($ip, $this->cache_enable);
        } else
            self::parseBase($ip);
    }

}

?>