<?php
namespace SamIT\Yii2\Bootstrap;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Response;

class CsvResponseFormatter implements BootstrapInterface
{
    const FORMAT_CSV = 'csv';
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\web\Application) {
            $responseConfig = $app->getComponents()['response'];
            $responseConfig['formatters'][self::FORMAT_CSV] = \SamIT\Yii2\Formatters\CsvResponseFormatter::class;
            $app->set('response', $responseConfig);
        }
    }
}