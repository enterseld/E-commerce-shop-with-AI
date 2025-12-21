<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use stdClass;

class NovaPostService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('novaposhta.api_key');
        $this->apiUrl = config('novaposhta.api_url');
    }

    public function makeRequestCities(string $modelName, string $calledMethod, string $findBy)
    {
        $methodProperties = new stdClass();
        
        $methodProperties->FindByString = $findBy;

        $response = Http::withOptions(['verify' => false])->post($this->apiUrl, [
            'apiKey' => $this->apiKey,
            'modelName' => $modelName,
            'calledMethod' => $calledMethod,
            'methodProperties' => (array)$methodProperties,
        ]);

        return $response->json();
    }

    public function makeRequestWarehouses(string $modelName, string $calledMethod, string $city, string $findBy)
    {
        $methodProperties = new stdClass();
        
        $methodProperties->CityName = $city;
        if($findBy != "0"){
            $methodProperties->FindByString = $findBy;
            $response = Http::withOptions(['verify' => false])->post($this->apiUrl, [
                'apiKey' => $this->apiKey,
                'modelName' => $modelName,
                'calledMethod' => $calledMethod,
                'methodProperties' => (array)$methodProperties,
            ]);

            return $response->json();
        }
        else{
            $response = Http::withOptions(['verify' => false])->post($this->apiUrl, [
                'apiKey' => $this->apiKey,
                'modelName' => $modelName,
                'calledMethod' => $calledMethod,
                'methodProperties' => (array)$methodProperties,
            ]);

            return $response->json();
        }
    }

    public function makeRequestAllWarehouses(string $modelName, string $calledMethod, string $findBy)
    {
        $methodProperties = new stdClass();
        $methodProperties->Limit = "10";
        $methodProperties->FindByString = $findBy;
        
    
        $response = Http::withOptions(['verify' => false])->post($this->apiUrl, [
            'apiKey' => $this->apiKey,
            'modelName' => $modelName,
            'calledMethod' => $calledMethod,
            'methodProperties' => (array)$methodProperties,
        ]);

        return $response->json();
    }
}
