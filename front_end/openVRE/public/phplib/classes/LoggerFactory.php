<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

class LoggerFactory
{
    private static array $loggers = [];

    public static function getLogger(string $channel = 'app'): Logger
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
}
