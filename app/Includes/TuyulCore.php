<?php

namespace App\Includes;

use App\Controller\TuyulCron;
use App\Controller\TuyulSetting;

class TuyulCore
{

    /**
     * Init all dependency classes
     */
    public function __construct()
    {
        new TuyulInit();
        new TuyulMenu();
        new TuyulSetting();
        new TuyulCron();
    }


}