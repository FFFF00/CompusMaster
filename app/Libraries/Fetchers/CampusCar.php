<?php

namespace App\Libraries\Fetchers;


class CampusCar extends Spider
{
    private $url = "http://115.28.149.153:8096/hust/bus/ajax/interface/xyt";

    public function convert()
    {
        $lists = array();
        try{
            $carObjects = $this->get($this->url);
            foreach($carObjects as $car){
                $lists[] = array(
                    "mac" => intval($car->mac),
                    "path" => intval($car->path),
                    "nowSite" => intval($car->nowSite),
                    "direction" => intval($car->direction),
                    "lat" => empty($car->lat) ? 0.0 : floatval($car->lat),
                    "lng" => empty($car->lng) ? 0.0 : floatval($car->lng)
                );
            }
        }catch(Exception $e){
            return false;
        }
        return $lists;
    }

}
