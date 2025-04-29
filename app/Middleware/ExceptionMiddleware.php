<?php 

declare(strict_types=1);

namespace App\Middleware;

use SergiX44\Nutgram\Nutgram;
use App\Services\ErrorHandlerService;

/**
 * Class ExceptionMiddleware
 *
 * This middleware handles exceptions that occur during the execution of the bot.
 * It catches any exceptions thrown and reports them using the ErrorHandlerService.
 */
class ExceptionMiddleware {
    
    /**
     * The ErrorHandlerService instance.
     *
     * @var ErrorHandlerService
     */
    private ErrorHandlerService $errorHandler;

    /**
     * Constructor for the ExceptionMiddleware class.
     *
     * @param ErrorHandlerService $errorHandler
     */
    public function __construct(ErrorHandlerService $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Handle the exception middleware.
     *
     * @param Nutgram $bot
     * @param callable $next
     * @return void
     */
    public function __invoke(Nutgram $bot, $next): void
    {
        try {
            $next($bot);
        } catch (\Throwable $e) {
            $this->errorHandler->report($e, $bot, 'Произошла ошибка');
        }
    }
}