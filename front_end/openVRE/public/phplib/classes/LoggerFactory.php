<?php

namespace OpenVRE;

require_once __DIR__ . "/../db.inc.php";

use Monolog\Handler\MongoDBHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\PsrLogMessageProcessor;

class LoggerFactory
{
    private static array $loggers = [];

    public static function getLogger(string $channel = 'app-stdout'): Logger
    {
        if (isset(self::$loggers[$channel])) {
            return self::$loggers[$channel];
        }

        $logger = new Logger($channel);

        $handler = new StreamHandler('php://stdout', Level::Debug);

        $formatter = new LineFormatter(
            null,          // use default format
            null,          // use default date format
            false,         // don't allow multiline
            true           // ignore empty context/extra
        );

        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        self::$loggers[$channel] = $logger;

        return $logger;
    }


    public static function getPersistentLogger(): Logger
    {
        if (isset(self::$loggers['app-mongodb'])) {
            return self::$loggers['app-mongodb'];
        }

        $logger = new Logger('app-mongodb');
        $logsCollection = 'action_logs';
        $mongodb = new MongoDBHandler($GLOBALS['mongodbClient'], getenv('MONGO_MAIN_DB'), $logsCollection, level::Info);
        $logger->pushHandler($mongodb);
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $logger->pushProcessor(function ($record) {
            $record['extra']['userId'] = $_SESSION['User']['id'];
            return $record;
        });
        self::$loggers['app-mongodb'] = $logger;

        return $logger;
    }
}
