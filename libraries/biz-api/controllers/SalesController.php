<?php

namespace biz\api\controllers;

use Yii;
use biz\api\base\AdvanceController;
use biz\api\models\sales\Sales as MSales;

/**
 * Description of SalesController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 3.0
 */
class SalesController extends AdvanceController
{
    /**
     * @inheritdoc
     */
    public $modelClass = 'biz\api\models\sales\Sales';
    public $modelSearchClass = 'biz\api\models\sales\searchs\Sales';

    /**
     * @inheritdoc
     */
    public $prefixEventName = 'eSales';

    /**
     * @var array
     */
    protected $patchingStatus = [
        [MSales::STATUS_DRAFT, MSales::STATUS_PROCESS, 'process', 'processed'],
        [MSales::STATUS_PROCESS, MSales::STATUS_DRAFT, 'reject', 'rejected'],
    ];

    /**
     * @param \dee\base\Event $event
     */
    public function ePatch($event)
    {
        /* @var $model MSales */
        list($model, $dirty, $olds) = $event->params;
        // status changed
        if (isset($dirty['status'])) {
            foreach ($this->patchingStatus as $change) {
                if ($olds['status'] == $change[0] && $dirty['status'] == $change[1]) {
                    $this->fire($change[2], [$model]);
                }
            }
        }
    }

    /**
     * @param \dee\base\Event $event
     */
    public function ePatched($event)
    {
        /* @var $model MSales */
        list($model, $dirty, $olds) = $event->params;
        // status changed
        if (isset($dirty['status'])) {
            foreach ($this->patchingStatus as $change) {
                if ($olds['status'] == $change[0] && $dirty['status'] == $change[1]) {
                    $this->fire($change[3], [$model]);
                }
            }
        }
    }
}